<?php

namespace Tests\Feature;

use App\Models\Aporte;
use App\Models\Pago;
use App\Models\Saldo;
use Carbon\Carbon;
use Database\Factories\PagoFactory;
use Tests\Concerns\BuildsDomainRecords;
use Tests\TestCase;

class LoteConfirmationTest extends TestCase
{
    use BuildsDomainRecords;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-03-15 12:00:00'));
    }

    public function test_preview_rejects_an_empty_payment_selection(): void
    {
        $admin = $this->createUser('ADMIN');
        $batch = $this->createBatch(null, [
            'estado' => 'EN_VERIFICACION',
        ]);

        $this->actingAs($admin)
            ->postJson("/lotes/{$batch->id}/preview-aportes", [
                'pago_ids' => [],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pago_ids');
    }

    public function test_confirmation_requires_an_observation_for_each_observed_payment(): void
    {
        [$admin, $batch, $payment] = $this->reviewFixture();

        $this->actingAs($admin)
            ->postJson("/lotes/{$batch->id}/confirmar-aportes", [
                'pago_ids' => [$payment->id],
                'pagos_observados' => [$payment->id],
                'observaciones_pagos' => [$payment->id => '   '],
            ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                "Debes ingresar una observación para el pago {$payment->id}."
            );

        $this->assertSame('EN_LOTE', $payment->fresh()->estado);
    }

    public function test_confirmation_verifies_payment_generates_periods_and_is_not_repeatable(): void
    {
        [$admin, $batch, $payment] = $this->reviewFixture([
            'monto_total' => '250.00',
            'monto_mensual' => '100.00',
            'cantidad_periodos' => 2,
        ]);

        $response = $this->actingAs($admin)->postJson(
            "/lotes/{$batch->id}/confirmar-aportes",
            ['pago_ids' => [$payment->id]]
        );

        $response->assertOk()
            ->assertJsonPath('resultado.aportes_generados', 2)
            ->assertJsonPath('resultado.saldos_generados', 1)
            ->assertJsonPath('resultado.lote_verificado', true);

        $this->assertSame('VERIFICADO', $payment->fresh()->estado);
        $this->assertSame('VERIFICADO', $batch->fresh()->estado);
        $this->assertDatabaseCount('aportes', 2);
        $this->assertDatabaseHas('saldos', [
            'afiliado_id' => $payment->afiliado_id,
            'pago_id' => $payment->id,
            'monto' => '50.00',
            'monto_disponible' => '50.00',
        ]);

        $this->postJson(
            "/lotes/{$batch->id}/confirmar-aportes",
            ['pago_ids' => [$payment->id]]
        )->assertForbidden();

        $this->assertSame(2, Aporte::query()->count());
        $this->assertSame(1, Saldo::query()->count());
    }

    public function test_confirmation_marks_observed_payment_and_records_its_note(): void
    {
        [$admin, $batch, $payment] = $this->reviewFixture();
        $note = 'El comprobante requiere revisión.';

        $this->actingAs($admin)->postJson(
            "/lotes/{$batch->id}/confirmar-aportes",
            [
                'pago_ids' => [$payment->id],
                'pagos_observados' => [$payment->id],
                'observaciones_pagos' => [$payment->id => $note],
            ]
        )->assertOk()
            ->assertJsonPath('resultado.pagos_observados', 1);

        $this->assertSame('OBSERVADO', $payment->fresh()->estado);
        $this->assertSame($note, $payment->fresh()->observaciones);
        $this->assertDatabaseCount('aportes', 0);
    }

    private function reviewFixture(array $paymentAttributes = []): array
    {
        $college = $this->createCollege();
        $admin = $this->createUser('ADMIN', $college);
        $affiliate = $this->createAffiliate($college, [
            'fecha_afiliacion' => '2025-01-17',
        ]);
        $batch = $this->createBatch($college, [
            'estado' => 'EN_VERIFICACION',
            'mes' => 2,
            'year' => 2026,
        ]);

        $payment = PagoFactory::new()->create(array_merge([
            'afiliado_id' => $affiliate->id,
            'lote_verificacion_id' => $batch->id,
            'registrado_por' => $admin->id,
            'monto_total' => '100.00',
            'monto_mensual' => '100.00',
            'cantidad_periodos' => 1,
            'estado' => 'EN_LOTE',
        ], $paymentAttributes));

        return [$admin, $batch, $payment];
    }
}
