<?php

namespace App\Policies;

use App\Models\Rol;
use App\Models\Usuario;

class UsuarioPolicy
{
    /**
     * Determina si el usuario puede ver el listado de usuarios.
     */
    public function viewAny(Usuario $usuario): bool
    {
        return in_array($usuario->rol?->nombre, [
            'SUPER ADMIN',
            'ADMIN',
        ]);
    }

    /**
     * Determina si el usuario puede crear usuarios.
     */
    public function create(Usuario $usuario): bool
    {
        return in_array($usuario->rol?->nombre, [
            'SUPER ADMIN',
            'ADMIN',
        ]);
    }

    /**
     * Determina si el usuario puede editar un usuario.
     */
    public function update(Usuario $usuario, Usuario $objetivo): bool
    {
        return $usuario->rol?->nombre === 'SUPER ADMIN'
            || (
                $usuario->rol?->nombre === 'ADMIN'
                && $objetivo->rol?->nombre !== 'SUPER ADMIN'
            );
    }

    /**
     * Determina si puede asignar el rol indicado.
     */
    public function assignRole(Usuario $usuario, Rol $rol): bool
    {
        return $usuario->rol?->nombre === 'SUPER ADMIN'
            || (
                $usuario->rol?->nombre === 'ADMIN'
                && $rol->nombre !== 'SUPER ADMIN'
            );
    }

    /**
     * Determina si puede cambiar el rol del usuario objetivo.
     */
    public function changeRole(Usuario $usuario, Usuario $objetivo, Rol $rolObjetivo): bool
    {
        if ($usuario->id === $objetivo->id) {
            return false;
        }

        if ($usuario->rol?->nombre === 'SUPER ADMIN') {
            return true;
        }

        return $usuario->rol?->nombre === 'ADMIN'
            && $objetivo->rol?->nombre !== 'SUPER ADMIN'
            && $this->assignRole($usuario, $rolObjetivo);
    }

    /**
     * Determina si puede cambiar el estado del usuario objetivo.
     */
    public function changeEstado(Usuario $usuario, Usuario $objetivo): bool
    {
        if ($usuario->id === $objetivo->id) {
            return false;
        }

        return $usuario->rol?->nombre === 'SUPER ADMIN'
            || (
                $usuario->rol?->nombre === 'ADMIN'
                && $objetivo->rol?->nombre !== 'SUPER ADMIN'
            );
    }

    /**
     * Determina si puede cambiar el colegio del usuario objetivo.
     */
    public function changeColegio(Usuario $usuario, Usuario $objetivo): bool
    {
        return $usuario->rol?->nombre === 'SUPER ADMIN';
    }

    /**
     * Determina si puede resetear la contraseña del usuario objetivo.
     */
    public function resetPassword(Usuario $usuario, Usuario $objetivo): bool
    {
        if ($usuario->id === $objetivo->id) {
            return false;
        }

        return $usuario->rol?->nombre === 'SUPER ADMIN'
            || (
                $usuario->rol?->nombre === 'ADMIN'
                && $objetivo->rol?->nombre !== 'SUPER ADMIN'
            );
    }
}