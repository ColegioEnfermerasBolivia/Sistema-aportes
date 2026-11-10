<?php

namespace App\Policies;

use App\Models\Afiliado;
use App\Models\Usuario;

class AfiliadoPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return in_array($usuario->rol?->nombre, [
            'SUPER ADMIN',
            'ADMIN',
            'USUARIO',
        ]);
    }

    public function create(Usuario $usuario): bool
    {
        return in_array($usuario->rol?->nombre, [
            'ADMIN',
            'USUARIO',
        ]);
    }

    public function update(
        Usuario $usuario,
        Afiliado $afiliado
    ): bool {

        if ($afiliado->estado !== 'ACTIVO') {
            return false;
        }

        if (in_array($usuario->rol?->nombre, [
            'SUPER ADMIN',
            'ADMIN',
        ])) {
            return true;
        }

        if ($usuario->rol?->nombre === 'USUARIO') {

            $colegioUsuario = $usuario->colegio;

            if (! $colegioUsuario) {
                return false;
            }

            if ($colegioUsuario->tipo === 'DEPARTAMENTAL') {

                $colegiosPermitidos = $colegioUsuario->hijos
                    ->pluck('id')
                    ->push($colegioUsuario->id);

                return $colegiosPermitidos
                    ->contains($afiliado->colegio_id);
            }

            return $usuario->colegio_id === $afiliado->colegio_id;
        }

        return false;
    }

    public function changeColegio(
        Usuario $usuario,
        Afiliado $afiliado
    ): bool {

        if ($afiliado->estado !== 'ACTIVO') {
            return false;
        }

        return $usuario->rol?->nombre === 'SUPER ADMIN';
    }

    public function changeEstado(
        Usuario $usuario,
        Afiliado $afiliado
    ): bool {
        return $usuario->rol?->nombre === 'ADMIN';
    }

    public function createInColegio(
        Usuario $usuario,
        int $colegioId
    ): bool {
        if ($usuario->rol?->nombre === 'ADMIN') {
            return true;
        }

        if ($usuario->rol?->nombre === 'USUARIO') {
            $colegioUsuario = $usuario->colegio;

            if (! $colegioUsuario) {
                return false;
            }

            if ($usuario->colegio_id === $colegioId) {
                return true;
            }

            return $colegioUsuario->tipo === 'DEPARTAMENTAL'
                && $colegioUsuario->hijos()
                    ->whereKey($colegioId)
                    ->where('estado', 'ACTIVO')
                    ->exists();
        }

        return false;
    }

    public function viewLaboral(
        Usuario $usuario,
        Afiliado $afiliado
    ): bool {
        if (in_array($usuario->rol?->nombre, [
            'SUPER ADMIN',
            'ADMIN',
        ])) {
            return true;
        }

        if ($usuario->rol?->nombre !== 'USUARIO') {
            return false;
        }

        $colegioUsuario = $usuario->colegio;

        if (! $colegioUsuario) {
            return false;
        }

        if ($colegioUsuario->tipo === 'DEPARTAMENTAL') {
            $colegiosPermitidos = $colegioUsuario->hijos
                ->pluck('id')
                ->push($colegioUsuario->id);

            return $colegiosPermitidos
                ->contains($afiliado->colegio_id);
        }

        return $usuario->colegio_id === $afiliado->colegio_id;
    }

    public function createLaboral(
        Usuario $usuario,
        Afiliado $afiliado
    ): bool {
        if ($afiliado->estado !== 'ACTIVO') {
            return false;
        }

        return $this->viewLaboral($usuario, $afiliado);
    }

    public function updateLaboral(
        Usuario $usuario,
        Afiliado $afiliado
    ): bool {
        if ($afiliado->estado !== 'ACTIVO') {
            return false;
        }

        return $this->viewLaboral($usuario, $afiliado);
    }

    public function viewAcademic(
        Usuario $usuario,
        Afiliado $afiliado
    ): bool {
        return $this->viewLaboral($usuario, $afiliado);
    }

    public function createAcademic(
        Usuario $usuario,
        Afiliado $afiliado
    ): bool {
        if ($afiliado->estado !== 'ACTIVO') {
            return false;
        }

        if ($usuario->rol?->nombre === 'ADMIN') {
            return true;
        }

        if ($usuario->rol?->nombre !== 'USUARIO') {
            return false;
        }

        return $this->viewLaboral($usuario, $afiliado);
    }

    public function updateAcademic(
        Usuario $usuario,
        Afiliado $afiliado
    ): bool {
        return $this->createAcademic($usuario, $afiliado);
    }

    public function deleteAcademic(
        Usuario $usuario,
        Afiliado $afiliado
    ): bool {
        return $this->createAcademic($usuario, $afiliado);
    }
}
