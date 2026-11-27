<?php

namespace App\Policies;

use App\Models\Universidad;
use App\Models\Usuario;

class UniversidadPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return in_array($usuario->rol?->nombre, [
            'SUPER ADMIN',
            'ADMIN',
            'USUARIO',
        ], true);
    }

    public function view(Usuario $usuario, Universidad $universidad): bool
    {
        return $this->viewAny($usuario);
    }

    public function create(Usuario $usuario): bool
    {
        return $usuario->rol?->nombre === 'SUPER ADMIN';
    }

    public function update(Usuario $usuario, Universidad $universidad): bool
    {
        return $usuario->rol?->nombre === 'SUPER ADMIN';
    }
}
