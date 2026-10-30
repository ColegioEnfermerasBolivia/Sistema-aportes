<?php

namespace Tests\Concerns;

use App\Models\Afiliado;
use App\Models\Aporte;
use App\Models\Colegio;
use App\Models\LoteVerificacion;
use App\Models\Pago;
use App\Models\Saldo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\TestResponse;

/**
 * Shared builders for batch / payment / balance scenarios.
 *
 * ADAPT HERE ONLY: makeCollege(), makeAffiliate(), makeLote() and makePayment()
 * use forceFill() with the columns visible in the controllers. If your tables
 * have more NOT NULL columns, add them in these four methods (or point them to
 * the helpers/factories you already use in LoteConfirmationTest).
 */
trait BuildsLoteScenarios
{
    private int $scenarioSequence = 0;
    private mixed $scenarioRegistrar = null;
    private mixed $scenarioReviewer = null;

    private function persist(Model $model, array $attributes): Model
    {
        $model->forceFill($attributes)->save();

        return $model;
    }

    protected function makeCollege(string $codigo = 'CBBA'): Colegio
    {
        return $this->persist(new Colegio, [
            'nombre' => "Colegio {$codigo}",
            'codigo' => $codigo,
            'tipo' => 'DEPARTAMENTAL',
            'estado' => 'ACTIVO',
        ]);
    }

    protected function makeAffiliate(Colegio $college, string $joined = '2026-01-10'): Afiliado
    {
        $n = ++$this->scenarioSequence;

        return $this->persist(new Afiliado, [
            'colegio_id' => $college->id,
            'registro_departamental' => "RD-{$n}",
            'matricula_profesional' => "MP-{$n}",
            'ci' => (string) (1000000 + $n),
            'nombres' => 'Ana',
            'apellidos' => "Prueba {$n}",
            'celular' => '70000000',
            'fecha_afiliacion' => $joined,
            'estado' => 'ACTIVO',
        ]);
    }

    protected function makeLote(
        Colegio $college,
        string $estado = 'EN_VERIFICACION',
        int $mes = 3
    ): LoteVerificacion {
        return $this->persist(new LoteVerificacion, [
            'colegio_id' => $college->id,
            'mes' => $mes,
            'year' => 2026,
            'codigo_lote' => $college->codigo.'-2026-'.str_pad((string) $mes, 2, '0', STR_PAD_LEFT),
            'cantidad_aportes' => 0,
            'monto_total' => 0,
            'estado' => $estado,
        ]);
    }

    protected function makePayment(
        LoteVerificacion $lote,
        Afiliado $afiliado,
        string $mensual,
        string $total,
        string $estado = 'EN_LOTE'
    ): Pago {
        $this->scenarioRegistrar ??= $this->createUser('USUARIO', $lote->colegio);

        $mensualCents = $this->cents($mensual);
        $periods = $mensualCents > 0
            ? max(0, intdiv($this->cents($total), $mensualCents))
            : 0;

        $pago = $this->persist(new Pago, [
            'lote_verificacion_id' => $lote->id,
            'afiliado_id' => $afiliado->id,
            'monto_mensual' => $mensual,
            'monto_total' => $total,
            'cantidad_periodos' => $periods,
            'estado' => $estado,
            'registrado_por' => $this->scenarioRegistrar->id,
        ]);

        $lote->increment('cantidad_aportes', $periods);

        return $pago;
    }

    /** Este rol es el que la policy de lotes usa para revisar/confirmar pagos. */
    protected function reviewer()
    {
        return $this->scenarioReviewer ??= $this->createUser('ADMIN');
    }

    protected function confirmAs(
        $user,
        LoteVerificacion $lote,
        array $pagoIds,
        array $observed = [],
        array $notes = []
    ): TestResponse {
        return $this->actingAs($user)->postJson("/lotes/{$lote->id}/confirmar-aportes", [
            'pago_ids' => $pagoIds,
            'pagos_observados' => $observed,
            'observaciones_pagos' => $notes,
        ]);
    }

    protected function previewAs($user, LoteVerificacion $lote, array $pagoIds): TestResponse
    {
        return $this->actingAs($user)->postJson("/lotes/{$lote->id}/preview-aportes", [
            'pago_ids' => $pagoIds,
        ]);
    }

    /* ---------- money helpers: always compare in integer cents ---------- */

    protected function cents(string|int|float|null $decimal): int
    {
        return (int) round(((float) $decimal) * 100);
    }

    protected function contributionsCents(Afiliado $afiliado): int
    {
        return $this->cents(Aporte::query()->where('afiliado_id', $afiliado->id)->sum('monto'));
    }

    protected function activeBalanceCents(Afiliado $afiliado): int
    {
        return $this->cents(
            Saldo::query()
                ->where('afiliado_id', $afiliado->id)
                ->where('estado', 'ACTIVO')
                ->sum('monto_disponible')
        );
    }
}