<?php

namespace Tests\Feature;

use App\Models\Aporte;
use App\Models\Pago;
use App\Models\Saldo;
use App\Services\SaldoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BuildsDomainRecords;
use Tests\Concerns\BuildsLoteScenarios;
use Tests\TestCase;

class ConfirmarAportesMoneyTest extends TestCase
{
    use RefreshDatabase;
    use BuildsDomainRecords;
    use BuildsLoteScenarios;

    public static function moneyScenarios(): array
    {
        // [monthly fee, payment total, expected periods, expected leftover in cents]
        return [
            'exact single period'           => ['100.00', '100.00', 1, 0],
            'two periods plus leftover'     => ['100.00', '250.00', 2, 5000],
            'below one period'              => ['100.00', '99.99', 0, 9999],
            'repeating decimals keep cents' => ['33.33', '100.00', 3, 1],
            'small tariff'                  => ['0.10', '1.00', 10, 0],
            'twelve periods cross a year'   => ['10.00', '120.00', 12, 0],
        ];
    }

    #[DataProvider('moneyScenarios')]
    public function test_money_is_conserved_and_periods_are_correct(
        string $mensual,
        string $total,
        int $periods,
        int $leftover
    ): void {
        $college = $this->makeCollege();
        $afiliado = $this->makeAffiliate($college, '2025-11-15');
        $lote = $this->makeLote($college);
        $pago = $this->makePayment($lote, $afiliado, $mensual, $total);

        $this->confirmAs($this->reviewer(), $lote, [$pago->id])->assertOk();

        $this->assertSame($periods, $afiliado->aportes()->count());
        $this->assertSame($leftover, $this->activeBalanceCents($afiliado));
        $this->assertSame(
            $this->cents($total),
            $this->contributionsCents($afiliado) + $this->activeBalanceCents($afiliado),
            'Contributions + remaining balance must equal the paid total (no lost cents).'
        );

        foreach ($afiliado->aportes as $aporte) {
            $this->assertSame($this->cents($mensual), $this->cents($aporte->monto));
        }
    }

    public function test_periods_are_consecutive_and_cross_the_year_boundary(): void
    {
        $college = $this->makeCollege();
        $afiliado = $this->makeAffiliate($college, '2025-11-15');
        $lote = $this->makeLote($college);
        $pago = $this->makePayment($lote, $afiliado, '10.00', '120.00');

        $this->confirmAs($this->reviewer(), $lote, [$pago->id])->assertOk();

        $ordered = $afiliado->aportes()->orderBy('year')->orderBy('mes')->get();

        $this->assertSame([2025, 11], [(int) $ordered->first()->year, (int) $ordered->first()->mes]);
        $this->assertSame([2026, 10], [(int) $ordered->last()->year, (int) $ordered->last()->mes]);
        $this->assertCount(12, $ordered->unique(fn ($a) => $a->year.'-'.$a->mes));
    }

    public function test_fractional_cents_never_create_or_lose_money(): void
    {
        $college = $this->makeCollege();
        $afiliado = $this->makeAffiliate($college);
        $lote = $this->makeLote($college);
        $pago = $this->makePayment($lote, $afiliado, '3.333', '10.005');

        // Whatever the column scale stored, compare against the stored value.
        $stored = $pago->fresh();

        $this->confirmAs($this->reviewer(), $lote, [$pago->id])->assertOk();

        $this->assertSame(
            $this->cents($stored->monto_total),
            $this->contributionsCents($afiliado) + $this->activeBalanceCents($afiliado)
        );
    }

    public function test_previous_balance_is_consumed_before_the_new_payment(): void
    {
        $college = $this->makeCollege();
        $afiliado = $this->makeAffiliate($college);
        $oldLote = $this->makeLote($college, 'VERIFICADO', 2);
        $oldPayment = $this->makePayment($oldLote, $afiliado, '100.00', '0.00', 'VERIFICADO');
        app(SaldoService::class)->registrarRemanente($afiliado->id, $oldPayment->id, 5000);

        $lote = $this->makeLote($college);
        $pago = $this->makePayment($lote, $afiliado, '100.00', '60.00');

        $this->confirmAs($this->reviewer(), $lote, [$pago->id])->assertOk();

        $this->assertSame(1, $afiliado->aportes()->count());
        $this->assertSame(1000, $this->activeBalanceCents($afiliado));
        $this->assertSame(
            5000 + 6000,
            $this->contributionsCents($afiliado) + $this->activeBalanceCents($afiliado)
        );
    }

    /* ---------------- observed payments ---------------- */

    public function test_observed_payment_creates_no_contributions_or_balances(): void
    {
        $college = $this->makeCollege();
        $ok = $this->makeAffiliate($college);
        $bad = $this->makeAffiliate($college);
        $lote = $this->makeLote($college);
        $okPayment = $this->makePayment($lote, $ok, '100.00', '100.00');
        $badPayment = $this->makePayment($lote, $bad, '100.00', '250.00');

        $this->confirmAs(
            $this->reviewer(),
            $lote,
            [$okPayment->id, $badPayment->id],
            [$badPayment->id],
            [$badPayment->id => 'Comprobante ilegible']
        )->assertOk();

        $this->assertSame('OBSERVADO', $badPayment->fresh()->estado);
        $this->assertSame(0, Aporte::where('afiliado_id', $bad->id)->count());
        $this->assertSame(0, Saldo::where('afiliado_id', $bad->id)->count());
        $this->assertSame('VERIFICADO', $okPayment->fresh()->estado);
        $this->assertSame('VERIFICADO', $lote->fresh()->estado);
    }

    public function test_observed_payment_cannot_be_confirmed_again_through_the_endpoint(): void
    {
        $college = $this->makeCollege();
        $afiliado = $this->makeAffiliate($college);
        $other = $this->makeAffiliate($college);
        $lote = $this->makeLote($college);
        $pago = $this->makePayment($lote, $afiliado, '100.00', '100.00');
        $pending = $this->makePayment($lote, $other, '100.00', '100.00');

        $this->confirmAs($this->reviewer(), $lote, [$pago->id], [$pago->id], [$pago->id => 'Observado'])->assertOk();

        $this->confirmAs($this->reviewer(), $lote, [$pago->id])->assertUnprocessable();

        $this->assertSame(0, Aporte::where('afiliado_id', $afiliado->id)->count());
        $this->assertSame(0, Saldo::where('afiliado_id', $afiliado->id)->count());
        $this->assertSame('EN_LOTE', $pending->fresh()->estado);
    }

    public function test_corrected_payment_returning_to_the_batch_is_counted_exactly_once(): void
    {
        $college = $this->makeCollege();
        $afiliado = $this->makeAffiliate($college);
        $other = $this->makeAffiliate($college);
        $lote = $this->makeLote($college);
        $pago = $this->makePayment($lote, $afiliado, '100.00', '250.00');
        $this->makePayment($lote, $other, '100.00', '100.00');

        $this->confirmAs($this->reviewer(), $lote, [$pago->id], [$pago->id], [$pago->id => 'Corregir monto'])->assertOk();

        // Simulates the correction flow (adapt if your correction goes through another endpoint).
        $pago->update(['estado' => 'EN_LOTE', 'observaciones' => null]);

        $this->confirmAs($this->reviewer(), $lote, [$pago->id])->assertUnprocessable();

        $this->assertSame(0, $afiliado->aportes()->count());
        $this->assertSame(0, $this->activeBalanceCents($afiliado));
        $this->assertSame(0, Saldo::where('afiliado_id', $afiliado->id)->count());
    }

    /* ---------------- atomicity and invalid amounts ---------------- */

    public function test_failure_on_a_later_payment_rolls_back_the_earlier_ones(): void
    {
        $college = $this->makeCollege();
        $a = $this->makeAffiliate($college);
        $b = $this->makeAffiliate($college);
        $lote = $this->makeLote($college);
        $good = $this->makePayment($lote, $a, '100.00', '250.00');
        $broken = $this->makePayment($lote, $b, '0.00', '100.00');

        $this->confirmAs($this->reviewer(), $lote, [$good->id, $broken->id])->assertUnprocessable();

        $this->assertSame('EN_LOTE', $good->fresh()->estado);
        $this->assertSame(0, Aporte::count());
        $this->assertSame(0, Saldo::count());
    }

    #[DataProvider('invalidMonthlyFees')]
    public function test_invalid_monthly_fee_is_rejected_by_preview_and_confirm(string $fee): void
    {
        $college = $this->makeCollege();
        $afiliado = $this->makeAffiliate($college);
        $lote = $this->makeLote($college);
        $pago = $this->makePayment($lote, $afiliado, $fee, '100.00');

        $this->previewAs($this->reviewer(), $lote, [$pago->id])->assertUnprocessable();
        $this->confirmAs($this->reviewer(), $lote, [$pago->id])->assertUnprocessable();

        $this->assertSame('EN_LOTE', $pago->fresh()->estado);
        $this->assertSame(0, Aporte::count());
    }

    public static function invalidMonthlyFees(): array
    {
        return ['zero' => ['0.00'], 'negative' => ['-5.00']];
    }

    #[DataProvider('invalidTotals')]
    public function test_zero_or_negative_payment_total_has_current_project_behavior(string $total, bool $shouldReject): void
    {
        $college = $this->makeCollege();
        $afiliado = $this->makeAffiliate($college);
        $lote = $this->makeLote($college);
        $pago = $this->makePayment($lote, $afiliado, '100.00', $total);

        $response = $this->confirmAs($this->reviewer(), $lote, [$pago->id]);

        if ($shouldReject) {
            $response->assertUnprocessable();
            $this->assertNotSame('VERIFICADO', $pago->fresh()->estado);
        } else {
            $response->assertOk();
            $this->assertSame('VERIFICADO', $pago->fresh()->estado);
        }

        $this->assertSame(0, Aporte::count());
    }

    public static function invalidTotals(): array
    {
        return [
            'zero' => ['0.00', false],
            'negative' => ['-100.00', true],
        ];
    }

    /* ---------------- preview vs confirm ---------------- */

    public function test_preview_does_not_repeat_months_for_two_payments_of_the_same_affiliate(): void
    {
        // SUSPECTED BUG: preview computes each payment from the DB state, so both
        // payments get the same months, while confirm assigns them sequentially.
        $college = $this->makeCollege();
        $afiliado = $this->makeAffiliate($college);
        $lote = $this->makeLote($college);
        $p1 = $this->makePayment($lote, $afiliado, '100.00', '100.00');
        $p2 = $this->makePayment($lote, $afiliado, '100.00', '100.00');

        $rows = collect($this->previewAs($this->reviewer(), $lote, [$p1->id, $p2->id])
            ->assertOk()
            ->json('aportes'));

        $this->assertNotSame(
            $rows->count(),
            $rows->unique(fn ($r) => $r['year'].'-'.$r['mes'])->count(),
            'Preview currently repeats months for the same affiliate.'
        );
    }

    public function test_foreign_batch_payment_cannot_be_confirmed_through_another_batch(): void
    {
        $college = $this->makeCollege();
        $afiliado = $this->makeAffiliate($college);
        $loteA = $this->makeLote($college, 'EN_VERIFICACION', 3);
        $loteB = $this->makeLote($college, 'EN_VERIFICACION', 4);
        $own = $this->makePayment($loteA, $afiliado, '100.00', '100.00');
        $foreign = $this->makePayment($loteB, $this->makeAffiliate($college), '100.00', '100.00');

        $this->confirmAs($this->reviewer(), $loteA, [$own->id, $foreign->id])->assertUnprocessable();

        $this->assertSame('EN_LOTE', $foreign->fresh()->estado);
        $this->assertSame('EN_LOTE', $own->fresh()->estado);
    }
}
