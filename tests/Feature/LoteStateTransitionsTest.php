<?php

namespace Tests\Feature;

use App\Models\Aporte;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BuildsDomainRecords;
use Tests\Concerns\BuildsLoteScenarios;
use Tests\TestCase;

class LoteStateTransitionsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsDomainRecords;
    use BuildsLoteScenarios;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public static function statesThatCannotBeReviewed(): array
    {
        return [
            'open batch'     => ['ABIERTO'],
            'sent batch'     => ['ENVIADO'],
            'verified batch' => ['VERIFICADO'],
        ];
    }

    #[DataProvider('statesThatCannotBeReviewed')]
    public function test_confirm_is_rejected_unless_the_batch_is_under_verification(string $state): void
    {
        $college = $this->makeCollege();
        $afiliado = $this->makeAffiliate($college);
        $lote = $this->makeLote($college, $state);
        $pago = $this->makePayment($lote, $afiliado, '100.00', '100.00');

        $response = $this->confirmAs($this->reviewer(), $lote, [$pago->id]);

        $this->assertContains($response->getStatusCode(), [403, 422]);
        $this->assertSame('EN_LOTE', $pago->fresh()->estado);
        $this->assertSame(0, Aporte::count());
        $this->assertSame($state, $lote->fresh()->estado);
    }

    #[DataProvider('statesThatCannotBeReviewed')]
    public function test_preview_is_rejected_unless_the_batch_is_under_verification(string $state): void
    {
        $college = $this->makeCollege();
        $afiliado = $this->makeAffiliate($college);
        $lote = $this->makeLote($college, $state);
        $pago = $this->makePayment($lote, $afiliado, '100.00', '100.00');

        $this->assertContains(
            $this->previewAs($this->reviewer(), $lote, [$pago->id])->getStatusCode(),
            [403, 422]
        );
    }

    public function test_college_user_cannot_confirm_their_own_batch(): void
    {
        $college = $this->makeCollege();
        $afiliado = $this->makeAffiliate($college);
        $lote = $this->makeLote($college);
        $pago = $this->makePayment($lote, $afiliado, '100.00', '100.00');

        $this->confirmAs($this->createUser('USUARIO', $college), $lote, [$pago->id])->assertForbidden();

        $this->assertSame('EN_LOTE', $pago->fresh()->estado);
    }

    public static function statesThatCannotBeClosed(): array
    {
        return [
            'already sent'      => ['ENVIADO'],
            'under review'      => ['EN_VERIFICACION'],
            'already verified'  => ['VERIFICADO'],
        ];
    }

    #[DataProvider('statesThatCannotBeClosed')]
    public function test_close_is_forbidden_unless_the_batch_is_open(string $state): void
    {
        $college = $this->makeCollege();
        $user = $this->createUser('USUARIO', $college);
        $lote = $this->makeLote($college, $state);
        $this->makePayment($lote, $this->makeAffiliate($college), '100.00', '100.00');

        $this->actingAs($user)->patch("/lotes/{$lote->id}/cerrar")->assertForbidden();

        $this->assertSame($state, $lote->fresh()->estado);
    }

    public function test_double_submit_of_close_does_not_overwrite_the_first_close(): void
    {
        $college = $this->makeCollege();
        $user = $this->createUser('USUARIO', $college);
        $lote = $this->makeLote($college, 'ABIERTO');
        $this->makePayment($lote, $this->makeAffiliate($college), '100.00', '100.00');

        Carbon::setTestNow('2026-03-01 10:00:00');
        $this->actingAs($user)->patch("/lotes/{$lote->id}/cerrar")->assertRedirect();
        $firstClose = $lote->fresh()->fecha_cierre_departamental;

        Carbon::setTestNow('2026-03-01 10:05:00');
        $this->actingAs($user)->patch("/lotes/{$lote->id}/cerrar")->assertForbidden();

        $this->assertEquals($firstClose, $lote->fresh()->fecha_cierre_departamental);
    }

    public function test_empty_batch_can_still_be_closed_by_its_owner(): void
    {
        $college = $this->makeCollege();
        $user = $this->createUser('USUARIO', $college);
        $lote = $this->makeLote($college, 'ABIERTO');

        $this->actingAs($user)
            ->patch("/lotes/{$lote->id}/cerrar")
            ->assertRedirect();

        $this->assertSame('ENVIADO', $lote->fresh()->estado);
        $this->assertSame($user->id, $lote->fresh()->enviado_por);
    }

    /* ---------------- confirm payload hardening ---------------- */

    public function test_observed_payment_outside_the_selection_is_rejected(): void
    {
        $college = $this->makeCollege();
        $lote = $this->makeLote($college);
        $a = $this->makePayment($lote, $this->makeAffiliate($college), '100.00', '100.00');
        $b = $this->makePayment($lote, $this->makeAffiliate($college), '100.00', '100.00');

        $this->confirmAs($this->reviewer(), $lote, [$a->id], [$b->id], [$b->id => 'x'])->assertUnprocessable();

        $this->assertSame('EN_LOTE', $a->fresh()->estado);
        $this->assertSame('EN_LOTE', $b->fresh()->estado);
    }

    public function test_duplicate_and_non_integer_payment_ids_are_rejected(): void
    {
        $college = $this->makeCollege();
        $lote = $this->makeLote($college);
        $a = $this->makePayment($lote, $this->makeAffiliate($college), '100.00', '100.00');

        $this->confirmAs($this->reviewer(), $lote, [$a->id, $a->id])->assertUnprocessable();
        $this->confirmAs($this->reviewer(), $lote, ["{$a->id}abc"])->assertUnprocessable();
        $this->confirmAs($this->reviewer(), $lote, [[$a->id]])->assertUnprocessable();

        $this->assertSame(0, Aporte::count());
    }

    public function test_observation_longer_than_1000_characters_is_rejected(): void
    {
        $college = $this->makeCollege();
        $lote = $this->makeLote($college);
        $a = $this->makePayment($lote, $this->makeAffiliate($college), '100.00', '100.00');

        $this->confirmAs($this->reviewer(), $lote, [$a->id], [$a->id], [$a->id => str_repeat('a', 1001)])
            ->assertUnprocessable();

        $this->assertSame('EN_LOTE', $a->fresh()->estado);
    }

    public function test_second_confirmation_of_the_same_selection_changes_nothing(): void
    {
        $college = $this->makeCollege();
        $afiliado = $this->makeAffiliate($college);
        $lote = $this->makeLote($college);
        $pago = $this->makePayment($lote, $afiliado, '100.00', '250.00');

        $this->confirmAs($this->reviewer(), $lote, [$pago->id])->assertOk();
        $contributions = $this->contributionsCents($afiliado);
        $balance = $this->activeBalanceCents($afiliado);

        $this->confirmAs($this->reviewer(), $lote, [$pago->id])->assertForbidden();

        $this->assertSame($contributions, $this->contributionsCents($afiliado));
        $this->assertSame($balance, $this->activeBalanceCents($afiliado));
    }
}
