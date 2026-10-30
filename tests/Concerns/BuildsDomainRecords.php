<?php

namespace Tests\Concerns;

use App\Models\Afiliado;
use App\Models\Colegio;
use App\Models\LoteVerificacion;
use App\Models\Pago;
use App\Models\Rol;
use App\Models\Usuario;
use Database\Factories\AfiliadoFactory;
use Database\Factories\ColegioFactory;
use Database\Factories\LoteVerificacionFactory;
use Database\Factories\PagoFactory;
use Database\Factories\RolFactory;
use Database\Factories\UsuarioFactory;

trait BuildsDomainRecords
{
    protected function createRole(string $name): Rol
    {
        return Rol::query()->firstOrCreate([
            'nombre' => $name,
        ]);
    }

    protected function createCollege(array $attributes = []): Colegio
    {
        return ColegioFactory::new()->create($attributes);
    }

    protected function createUser(
        string $role,
        ?Colegio $college = null,
        array $attributes = []
    ): Usuario {
        $roleModel = $this->createRole($role);
        $college ??= $this->createCollege();

        return UsuarioFactory::new()->create(array_merge([
            'rol_id' => $roleModel->id,
            'colegio_id' => $college->id,
        ], $attributes));
    }

    protected function createAffiliate(
        ?Colegio $college = null,
        array $attributes = []
    ): Afiliado {
        $college ??= $this->createCollege();

        return AfiliadoFactory::new()->create(array_merge([
            'colegio_id' => $college->id,
        ], $attributes));
    }

    protected function createBatch(
        ?Colegio $college = null,
        array $attributes = []
    ): LoteVerificacion {
        $college ??= $this->createCollege();

        return LoteVerificacionFactory::new()->create(array_merge([
            'colegio_id' => $college->id,
        ], $attributes));
    }

    protected function createPayment(array $attributes = []): Pago
    {
        return PagoFactory::new()->create($attributes);
    }
}
