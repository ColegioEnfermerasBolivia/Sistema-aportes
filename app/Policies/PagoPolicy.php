<?php

namespace App\Policies;

use App\Models\Afiliado;
use App\Models\Pago;
use App\Models\Usuario;

class PagoPolicy
{
    /**
     * Determina si el usuario puede registrar un pago.
     *
     * ADMIN:
     * - Puede registrar pagos directamente para cualquier afiliado.
     *
     * USUARIO:
     * - Puede registrar pagos únicamente para afiliados de su colegio.
     *
     * SUPER ADMIN:
     * - No registra pagos.
     */
    public function store(Usuario $usuario, Afiliado $afiliado): bool
    {
        if ($usuario->rol?->nombre === 'ADMIN') {
            return true;
        }

        if ($usuario->rol?->nombre === 'USUARIO') {
            return $usuario->colegio_id === $afiliado->colegio_id;
        }

        return false;
    }

    /**
     * Determina si el usuario puede modificar un pago.
     *
     * Solo los USUARIO pueden modificar pagos.
     * El pago debe pertenecer a un lote ABIERTO
     * y dicho lote debe corresponder al colegio del usuario.
     */
    public function update(Usuario $usuario, Pago $pago): bool
    {
        if ($usuario->rol?->nombre !== 'USUARIO') {
            return false;
        }

        $lote = $pago->loteVerificacion;

        if (!$lote) {
            return false;
        }

        if ($lote->estado !== 'ABIERTO') {
            return false;
        }

        return $usuario->colegio_id === $lote->colegio_id;
    }

    /**
     * Determina si el ADMIN puede observar un pago.
     *
     * El pago debe:
     * - Estar EN_LOTE.
     * - Pertenecer a un lote que ya se encuentre
     *   EN_VERIFICACION.
     */
    public function observar(Usuario $usuario, Pago $pago): bool
    {
        if ($usuario->rol?->nombre !== 'ADMIN') {
            return false;
        }

        return $pago->estado === 'EN_LOTE'
            && $pago->loteVerificacion?->estado === 'EN_VERIFICACION';
    }
}
