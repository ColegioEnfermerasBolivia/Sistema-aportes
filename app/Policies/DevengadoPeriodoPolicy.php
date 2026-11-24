<?php

namespace App\Policies;

use App\Models\DevengadoPeriodo;
use App\Models\Usuario;

class DevengadoPeriodoPolicy
{
    public function pay(Usuario $usuario, DevengadoPeriodo $periodo): bool
    {
        if ($periodo->estado !== 'REGISTRADO') {
            return false;
        }

        if (! in_array($usuario->rol->nombre, ['ADMIN', 'USUARIO'], true)) {
            return false;
        }

        if ($usuario->rol->nombre === 'ADMIN') {
            return true;
        }

        $colegioId = $periodo->afiliado?->colegio_id;
        if ($colegioId === null) {
            return false;
        }

        if ($usuario->colegio_id === $colegioId) {
            return true;
        }

        $colegio = $usuario->colegio;

        return $colegio?->tipo === 'DEPARTAMENTAL'
            && $colegio->hijos()
                ->whereKey($colegioId)
                ->where('estado', 'ACTIVO')
                ->exists();
    }

    public function exonerate(Usuario $usuario, DevengadoPeriodo $periodo): bool
    {
        return $usuario->rol->nombre === 'ADMIN'
            && $periodo->estado === 'REGISTRADO';
    }
}
