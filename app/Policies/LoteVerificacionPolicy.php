<?php

namespace App\Policies;

use App\Models\LoteVerificacion;
use App\Models\Usuario;

class LoteVerificacionPolicy
{
    /**
     * Determina si el usuario puede ver el listado de lotes.
     */
    public function viewAny(Usuario $usuario): bool
    {
        return in_array($usuario->rol?->nombre, [
            'SUPER ADMIN',
            'ADMIN',
            'USUARIO',
        ]);
    }

    /**
     * Determina si el usuario puede ver un lote específico.
     */
    public function view(
        Usuario $usuario,
        LoteVerificacion $lote
    ): bool {
        if (in_array($usuario->rol?->nombre, [
            'SUPER ADMIN',
            'ADMIN',
        ])) {
            return true;
        }

        if ($usuario->rol?->nombre === 'USUARIO') {
            return $usuario->colegio_id === $lote->colegio_id;
        }

        return false;
    }

    /**
     * Determina si puede iniciar o continuar la revisión de un lote.
     */
    public function revisar(
        Usuario $usuario,
        LoteVerificacion $lote
    ): bool {
        return $usuario->rol?->nombre === 'ADMIN';
    }

    /**
     * Determina si puede previsualizar los aportes de un lote.
     */
    public function previewAportes(
        Usuario $usuario,
        LoteVerificacion $lote
    ): bool {
        return $usuario->rol?->nombre === 'ADMIN';
    }

    /**
     * Determina si puede confirmar los aportes de un lote.
     */
    public function confirmarAportes(
        Usuario $usuario,
        LoteVerificacion $lote
    ): bool {
        return $usuario->rol?->nombre === 'ADMIN';
    }
}