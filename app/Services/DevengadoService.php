<?php

namespace App\Services;

use App\Models\DevengadoPeriodo;

class DevengadoService
{
    public function montoPagado(DevengadoPeriodo $periodo): int
    {
        $monto = $periodo->relationLoaded('pagosDetalles')
            ? $periodo->pagosDetalles->sum('monto')
            : $periodo->pagosDetalles()->sum('monto');

        return $this->aCentavos($monto);
    }

    public function montoExonerado(DevengadoPeriodo $periodo): int
    {
        $monto = $periodo->relationLoaded('exoneraciones')
            ? $periodo->exoneraciones->sum('monto')
            : $periodo->exoneraciones()->sum('monto');

        return $this->aCentavos($monto);
    }

    public function saldoPendiente(DevengadoPeriodo $periodo): int
    {
        $montoCentavos = (int) round((float) $periodo->monto * 100);

        return max(
            0,
            $montoCentavos
                - $this->montoPagado($periodo)
                - $this->montoExonerado($periodo)
        );
    }

    private function aCentavos(string|int|float $monto): int
    {
        $partes = explode('.', number_format((float) $monto, 2, '.', ''));

        return ((int) $partes[0] * 100) + (int) ($partes[1] ?? 0);
    }
}
