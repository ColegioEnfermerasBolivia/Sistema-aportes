<?php

namespace App\Policies;

use App\Models\LoteVerificacion;
use App\Models\Usuario;

class LoteVerificacionPolicy
{
    /**
     * Determina si el usuario puede ver la lista de lotes.
     *
     * Los tres roles pueden consultar los lotes.
     * La restricción de qué lotes puede ver cada usuario
     * se aplicará posteriormente en el controlador.
     */
    public function viewAny(Usuario $usuario): bool
    {
        return \in_array($usuario->rol?->nombre, [
            'SUPER ADMIN',
            'ADMIN',
            'USUARIO',
        ]);
    }

    public function viewReport(
        Usuario $usuario,
        LoteVerificacion $lote
    ): bool {
        $rol = $usuario->rol?->nombre;

        if (! \in_array($rol, ['SUPER ADMIN', 'ADMIN', 'USUARIO'])) {
            return false;
        }

        if ($lote->estado === 'ABIERTO') {
            return false;
        }

        return $rol !== 'USUARIO'
            || $usuario->colegio_id === $lote->colegio_id;
    }

    /**
     * Determina si el usuario puede crear un lote.
     *
     * Solamente los USUARIO pueden crear lotes.
     */
    public function create(Usuario $usuario): bool
    {
        return $usuario->rol?->nombre === 'USUARIO';
    }

    /**
     * Determina si el usuario puede cerrar/enviar un lote.
     *
     * El lote debe:
     * - Estar ABIERTO.
     * - Pertenecer al mismo colegio del usuario.
     * - El usuario debe ser USUARIO.
     */
    public function cerrar(
        Usuario $usuario,
        LoteVerificacion $lote
    ): bool {
        if ($usuario->rol?->nombre !== 'USUARIO') {
            return false;
        }

        if ($lote->estado !== 'ABIERTO') {
            return false;
        }

        return $usuario->colegio_id === $lote->colegio_id;
    }

    /**
     * Determina si el ADMIN puede iniciar la revisión de un lote.
     *
     * La revisión solamente puede iniciarse cuando el lote
     * está ENVIADO.
     *
     * ENVIADO -> EN_VERIFICACION
     */
    public function revisar(
        Usuario $usuario,
        LoteVerificacion $lote
    ): bool {
        return $usuario->rol?->nombre === 'ADMIN'
            && $lote->estado === 'ENVIADO';
    }

    /**
     * Determina si el ADMIN puede previsualizar los aportes
     * que serán generados a partir de los pagos seleccionados.
     *
     * El lote debe encontrarse EN_VERIFICACION.
     */
    public function previewAportes(
        Usuario $usuario,
        LoteVerificacion $lote
    ): bool {
        return $usuario->rol?->nombre === 'ADMIN'
            && $lote->estado === 'EN_VERIFICACION';
    }

    /**
     * Determina si el ADMIN puede confirmar los aportes
     * después de revisar los pagos.
     *
     * El lote debe encontrarse EN_VERIFICACION.
     */
    public function confirmarAportes(
        Usuario $usuario,
        LoteVerificacion $lote
    ): bool {
        return $usuario->rol?->nombre === 'ADMIN'
            && $lote->estado === 'EN_VERIFICACION';
    }

    /**
     * Determina si el SUPER ADMIN puede reabrir un lote.
     *
     * La reapertura solamente se permite para lotes ENVIADOS,
     * por ejemplo, cuando un USUARIO cerró/envió el lote
     * por equivocación.
     *
     * ENVIADO -> ABIERTO
     *
     * No se permite reabrir un lote que ya está EN_VERIFICACION
     * ni uno que ya fue VERIFICADO.
     */
    public function reabrir(
        Usuario $usuario,
        LoteVerificacion $lote
    ): bool {
        return $usuario->rol?->nombre === 'SUPER ADMIN'
            && $lote->estado === 'ENVIADO';
    }
}
