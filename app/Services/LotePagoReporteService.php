<?php

namespace App\Services;

use App\Models\LoteVerificacion;
use App\Models\Usuario;

class LotePagoReporteService
{
    /**
     * @return array{
     *     colegio: string,
     *     codigo_lote: string,
     *     fecha_generacion: string,
     *     usuario_generador: string,
     *     filas: array<int, array{
     *         aportante: string,
     *         ci: string,
     *         gestiones: array<int, array<int, float>>,
     *         saldo_disponible: float
     *     }>
     * }
     */
    public function construir(
        LoteVerificacion $lote,
        Usuario $usuario
    ): array {
        $lote->load([
            'colegio',
            'pagos' => fn ($query) => $query
                ->whereHas('aportes', fn ($aportes) => $aportes
                    ->where('estado', 'VERIFICADO'))
                ->with([
                    'afiliado',
                    'aportes' => fn ($aportes) => $aportes
                        ->where('estado', 'VERIFICADO')
                        ->orderBy('year')
                        ->orderBy('mes'),
                    'saldos' => fn ($saldos) => $saldos
                        ->where('estado', 'ACTIVO')
                        ->where('monto_disponible', '>', 0),
                ])
                ->orderBy('id'),
        ]);

        return [
            'colegio' => $lote->colegio?->nombre ?? '—',
            'codigo_lote' => $lote->codigo_lote,
            'fecha_generacion' => now()->format('d/m/Y H:i'),
            'usuario_generador' => trim(
                "{$usuario->nombres} {$usuario->apellidos}"
            ) ?: $usuario->username,
            'filas' => $lote->pagos
                ->map(function ($pago): array {
                    $aportesPorGestion = $pago->aportes
                        ->groupBy('year')
                        ->sortKeys();

                    $gestiones = [];

                    foreach ($aportesPorGestion as $gestion => $aportes) {
                        foreach ($aportes as $aporte) {
                            $mes = (int) $aporte->mes;
                            $gestiones[(int) $gestion][$mes] =
                                (float) $aporte->monto;
                        }
                    }

                    $saldoCentavos = $pago->saldos->sum(
                        fn ($saldo) => (int) round(
                            (float) $saldo->monto_disponible * 100
                        )
                    );

                    return [
                        'aportante' => trim(
                            ($pago->afiliado?->nombres ?? '')
                            .' '
                            .($pago->afiliado?->apellidos ?? '')
                        ) ?: '—',
                        'ci' => $pago->afiliado?->ci ?? '—',
                        'gestiones' => $gestiones,
                        'saldo_disponible' => $saldoCentavos / 100,
                    ];
                })
                ->all(),
        ];
    }
}
