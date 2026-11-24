<?php

namespace App\Policies;

use App\Models\DevengadoLote;
use App\Models\Usuario;

class DevengadoLotePolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return in_array($usuario->rol?->nombre, [
            'SUPER ADMIN',
            'ADMIN',
            'USUARIO',
        ], true);
    }

    public function view(Usuario $usuario, DevengadoLote $lote): bool
    {
        if (in_array($usuario->rol?->nombre, ['ADMIN', 'SUPER ADMIN'], true)) {
            return true;
        }

        return $usuario->rol?->nombre === 'USUARIO'
            && $this->puedeGestionarColegio($usuario, $lote->colegio_id);
    }

    public function create(Usuario $usuario): bool
    {
        return $usuario->rol?->nombre === 'USUARIO';
    }

    public function submit(Usuario $usuario, DevengadoLote $lote): bool
    {
        return $usuario->rol?->nombre === 'USUARIO'
            && $lote->estado === 'ABIERTO'
            && $this->puedeGestionarColegio($usuario, $lote->colegio_id);
    }

    public function manage(Usuario $usuario, DevengadoLote $lote): bool
    {
        return $usuario->rol?->nombre === 'USUARIO'
            && in_array($lote->estado, ['ABIERTO', 'ENVIADO'], true)
            && $this->puedeGestionarColegio($usuario, $lote->colegio_id);
    }

    public function review(Usuario $usuario, DevengadoLote $lote): bool
    {
        return $usuario->rol?->nombre === 'ADMIN'
            && in_array($lote->estado, ['ENVIADO', 'EN_VERIFICACION'], true);
    }

    public function process(Usuario $usuario, DevengadoLote $lote): bool
    {
        return $usuario->rol?->nombre === 'ADMIN'
            && $lote->estado === 'EN_VERIFICACION';
    }

    public function viewReport(Usuario $usuario, DevengadoLote $lote): bool
    {
        if (! in_array($lote->estado, [
            'ENVIADO',
            'EN_VERIFICACION',
            'VERIFICADO',
        ], true)) {
            return false;
        }

        if (in_array($usuario->rol?->nombre, ['SUPER ADMIN', 'ADMIN'], true)) {
            return true;
        }

        return $usuario->rol?->nombre === 'USUARIO'
            && $this->puedeGestionarColegio($usuario, $lote->colegio_id);
    }

    private function puedeGestionarColegio(Usuario $usuario, int $colegioId): bool
    {
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
}
