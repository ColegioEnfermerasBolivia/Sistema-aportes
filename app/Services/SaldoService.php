<?php

namespace App\Services;

use App\Models\Saldo;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class SaldoService
{
    public function activosParaAfiliado(
        int $afiliadoId,
        bool $bloquear = false
    ): Collection {
        $consulta = Saldo::query()
            ->where('afiliado_id', $afiliadoId)
            ->where('estado', 'ACTIVO')
            ->where('monto_disponible', '>', 0)
            ->orderBy('id');

        if ($bloquear) {
            $consulta->lockForUpdate();
        }

        return $consulta->get();
    }

    public function distribuir(
        Collection $saldos,
        int $montoPagoCentavos,
        int $montoMensualCentavos
    ): array {
        if ($montoMensualCentavos <= 0) {
            throw new InvalidArgumentException(
                'El monto mensual debe ser mayor que cero.'
            );
        }

        $saldoAnteriorCentavos = $saldos->sum(
            fn (Saldo $saldo) => (int) round(
                (float) $saldo->monto_disponible * 100
            )
        );
        $totalDisponibleCentavos =
            $saldoAnteriorCentavos + $montoPagoCentavos;
        $cantidadPeriodos = intdiv(
            $totalDisponibleCentavos,
            $montoMensualCentavos
        );
        $costoPeriodosCentavos =
            $cantidadPeriodos * $montoMensualCentavos;

        return [
            'saldo_anterior_centavos' => $saldoAnteriorCentavos,
            'cantidad_periodos' => $cantidadPeriodos,
            'costo_periodos_centavos' => $costoPeriodosCentavos,
            'saldo_final_centavos' =>
                $totalDisponibleCentavos - $costoPeriodosCentavos,
            'consumo_pago_centavos' => max(
                0,
                $costoPeriodosCentavos - $saldoAnteriorCentavos
            ),
        ];
    }

    public function consumir(
        Collection $saldos,
        int $montoCentavos
    ): void {
        foreach ($saldos as $saldo) {
            if ($montoCentavos <= 0) {
                break;
            }

            $disponibleCentavos = (int) round(
                (float) $saldo->monto_disponible * 100
            );
            $consumirCentavos = min(
                $disponibleCentavos,
                $montoCentavos
            );
            $nuevoDisponibleCentavos =
                $disponibleCentavos - $consumirCentavos;

            $saldo->update([
                'monto_disponible' => $nuevoDisponibleCentavos / 100,
                'estado' => $nuevoDisponibleCentavos > 0
                    ? 'ACTIVO'
                    : 'APLICADO',
            ]);

            $montoCentavos -= $consumirCentavos;
        }
    }

    public function registrarRemanente(
        int $afiliadoId,
        int $pagoId,
        int $montoCentavos
    ): ?Saldo {
        if ($montoCentavos <= 0) {
            return null;
        }

        return Saldo::create([
            'afiliado_id' => $afiliadoId,
            'pago_id' => $pagoId,
            'monto' => $montoCentavos / 100,
            'monto_disponible' => $montoCentavos / 100,
            'estado' => 'ACTIVO',
            'observacion' =>
                "Saldo a favor generado al verificar el pago {$pagoId}.",
        ]);
    }
}
