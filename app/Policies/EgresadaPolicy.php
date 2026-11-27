<?php

namespace App\Policies;

use App\Models\Departamento;
use App\Models\Colegio;
use App\Models\Egresada;
use App\Models\EgresadaFormacion;
use App\Models\Usuario;
use Illuminate\Support\Str;

class EgresadaPolicy
{
    private const ROLES_LECTURA = [
        'SUPER ADMIN',
        'ADMIN',
        'USUARIO',
    ];

    public function viewAny(Usuario $usuario): bool
    {
        return in_array($usuario->rol?->nombre, self::ROLES_LECTURA, true);
    }

    public function view(Usuario $usuario, Egresada $egresada): bool
    {
        return $this->viewAny($usuario);
    }

    public function create(Usuario $usuario): bool
    {
        return $this->viewAny($usuario);
    }

    public function update(Usuario $usuario, Egresada $egresada): bool
    {
        return $this->viewAny($usuario)
            && !$egresada->estaConvertida();
    }

    public function convert(Usuario $usuario, Egresada $egresada): bool
    {
        return $this->viewAny($usuario)
            && !$egresada->estaConvertida();
    }

    public function convertToCollege(
        Usuario $usuario,
        Egresada $egresada,
        int $colegioId
    ): bool {
        if ($egresada->estaConvertida()) {
            return false;
        }

        if ($usuario->rol?->nombre === 'USUARIO') {
            return $usuario->colegio_id !== null
                && (int) $usuario->colegio_id === $colegioId
                && $usuario->colegio?->estado === 'ACTIVO';
        }

        return in_array($usuario->rol?->nombre, [
            'ADMIN',
            'SUPER ADMIN',
        ], true)
            && Colegio::query()
                ->whereKey($colegioId)
                ->where('estado', 'ACTIVO')
                ->exists();
    }

    public function import(Usuario $usuario): bool
    {
        return $usuario->rol?->nombre === 'SUPER ADMIN';
    }

    public function createFormation(
        Usuario $usuario,
        int $departamentoId
    ): bool {
        if ($usuario->rol?->nombre === 'SUPER ADMIN') {
            return true;
        }

        if (!in_array($usuario->rol?->nombre, ['ADMIN', 'USUARIO'], true)) {
            return false;
        }

        return $this->usuarioPerteneceAlDepartamento(
            $usuario,
            $departamentoId
        );
    }

    public function updateFormation(
        Usuario $usuario,
        EgresadaFormacion $formacion
    ): bool {
        return $this->createFormation(
            $usuario,
            (int) $formacion->departamento_id
        );
    }

    private function usuarioPerteneceAlDepartamento(
        Usuario $usuario,
        int $departamentoId
    ): bool {
        $colegio = $usuario->colegio;

        if ($colegio?->tipo === 'REGIONAL') {
            $colegio = $colegio->padre;
        }

        if ($colegio?->tipo !== 'DEPARTAMENTAL') {
            return false;
        }

        $prefijo = 'Colegio Departamental de ';

        if (!Str::startsWith(
            Str::lower($colegio->nombre),
            Str::lower($prefijo)
        )) {
            return false;
        }

        $nombreDepartamento = $this->normalizarNombre(
            trim(Str::substr($colegio->nombre, strlen($prefijo)))
        );

        if ($nombreDepartamento === '') {
            return false;
        }

        return Departamento::query()
            ->get(['id', 'nombre'])
            ->contains(function (Departamento $departamento) use (
                $departamentoId,
                $nombreDepartamento
            ): bool {
                return (int) $departamento->id === $departamentoId
                    && $this->normalizarNombre($departamento->nombre)
                        === $nombreDepartamento;
            });
    }

    private function normalizarNombre(string $nombre): string
    {
        return Str::upper(
            Str::ascii(
                preg_replace('/\s+/u', ' ', trim($nombre)) ?? ''
            )
        );
    }
}
