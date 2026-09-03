<?php

namespace App\Policies;

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
        return in_array($usuario->rol?->nombre, [
            'SUPER ADMIN',
            'ADMIN',
        ]);
    }

    /**
     * Determina si puede cambiar el rol del usuario objetivo.
     */
    public function changeRole(Usuario $usuario, Usuario $objetivo): bool
    {
        if (!in_array($usuario->rol?->nombre, [
            'SUPER ADMIN',
            'ADMIN',
        ])) {
            return false;
        }

        // Nadie puede cambiar su propio rol.
        return $usuario->id !== $objetivo->id;
    }

    /**
     * Determina si puede cambiar el estado del usuario objetivo.
     */
    public function changeEstado(Usuario $usuario, Usuario $objetivo): bool
    {
        if (!in_array($usuario->rol?->nombre, [
            'SUPER ADMIN',
            'ADMIN',
        ])) {
            return false;
        }

        // Nadie puede cambiar su propio estado.
        return $usuario->id !== $objetivo->id;
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
        if (!in_array($usuario->rol?->nombre, [
            'SUPER ADMIN',
            'ADMIN',
        ])) {
            return false;
        }

        // El reset administrativo será para otros usuarios.
        return $usuario->id !== $objetivo->id;
    }
}