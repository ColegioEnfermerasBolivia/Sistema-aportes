<?php

namespace App\Policies;

use App\Models\DevengadoPago;
use App\Models\Usuario;

class DevengadoPagoPolicy
{
    public function update(Usuario $usuario, DevengadoPago $pago): bool
    {
        if ($usuario->rol?->nombre !== 'USUARIO' || $pago->estado !== 'EN_LOTE') {
            return false;
        }

        $lote = $pago->lote;
        if (! $lote || in_array($lote->estado, ['EN_VERIFICACION', 'VERIFICADO'], true)) {
            return false;
        }

        if ($usuario->colegio_id === $lote->colegio_id) {
            return true;
        }

        $colegio = $usuario->colegio;

        return $colegio?->tipo === 'DEPARTAMENTAL'
            && $colegio->hijos()
                ->whereKey($lote->colegio_id)
                ->where('estado', 'ACTIVO')
                ->exists();
    }
}
