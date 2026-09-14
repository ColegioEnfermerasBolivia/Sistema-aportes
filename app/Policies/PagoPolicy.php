<?php

namespace App\Policies;

use App\Models\Afiliado;
use App\Models\Pago;
use App\Models\Usuario;
use function PHPUnit\Framework\returnArgument;

class PagoPolicy{ 
    public function store(Usuario $usuario, Afiliado $afiliado): bool {
        if ($usuario->rol?->nombre === 'ADMIN') {
            return true;
        }
        if ($usuario->rol?->nombre === 'USUARIO') {
            return $usuario->colegio_id === $afiliado->colegio_id;
        }

        return false;
    }

    public function rechazar(Usuario $usuario, Pago $pago): bool {
        return $usuario->rol?->nombre === 'ADMIN';
    }
}