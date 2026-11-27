<?php

namespace Tests\Unit;

use App\Models\Egresada;
use App\Models\Rol;
use App\Models\Universidad;
use App\Models\Usuario;
use App\Policies\EgresadaPolicy;
use App\Policies\UniversidadPolicy;
use PHPUnit\Framework\TestCase;

class EgresadaPolicyTest extends TestCase
{
    public function test_all_existing_roles_can_read_and_edit_egresadas(): void
    {
        $policy = new EgresadaPolicy();
        $egresada = new Egresada();

        foreach (['SUPER ADMIN', 'ADMIN', 'USUARIO'] as $rol) {
            $usuario = $this->usuario($rol);

            $this->assertTrue($policy->viewAny($usuario));
            $this->assertTrue($policy->view($usuario, $egresada));
            $this->assertTrue($policy->create($usuario));
            $this->assertTrue($policy->update($usuario, $egresada));
            $this->assertTrue($policy->convert($usuario, $egresada));
        }

        $this->assertFalse($policy->viewAny($this->usuario('OTRO')));
    }

    public function test_only_superadmin_can_import_or_manage_universities(): void
    {
        $egresadaPolicy = new EgresadaPolicy();
        $universidadPolicy = new UniversidadPolicy();
        $universidad = new Universidad();

        foreach (['ADMIN', 'USUARIO'] as $rol) {
            $usuario = $this->usuario($rol);

            $this->assertFalse($egresadaPolicy->import($usuario));
            $this->assertTrue($universidadPolicy->viewAny($usuario));
            $this->assertTrue($universidadPolicy->view($usuario, $universidad));
            $this->assertFalse($universidadPolicy->create($usuario));
            $this->assertFalse($universidadPolicy->update($usuario, $universidad));
        }

        $superAdmin = $this->usuario('SUPER ADMIN');

        $this->assertTrue($egresadaPolicy->import($superAdmin));
        $this->assertTrue($egresadaPolicy->createFormation($superAdmin, 999));
        $this->assertFalse(
            $egresadaPolicy->createFormation($this->usuario('ADMIN'), 999)
        );
        $this->assertTrue($universidadPolicy->create($superAdmin));
        $this->assertTrue($universidadPolicy->update($superAdmin, $universidad));
    }

    private function usuario(string $rol): Usuario
    {
        $usuario = new Usuario();
        $usuario->setRelation('rol', new Rol(['nombre' => $rol]));

        return $usuario;
    }
}
