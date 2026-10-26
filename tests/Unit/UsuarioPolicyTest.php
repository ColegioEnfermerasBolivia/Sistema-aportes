<?php

namespace Tests\Unit;

use App\Models\Rol;
use App\Models\Usuario;
use App\Policies\UsuarioPolicy;
use PHPUnit\Framework\TestCase;

class UsuarioPolicyTest extends TestCase
{
    public function test_admin_cannot_assign_super_admin_role(): void
    {
        $policy = new UsuarioPolicy();

        $this->assertFalse($policy->assignRole(
            $this->usuario(1, 'ADMIN'),
            new Rol(['nombre' => 'SUPER ADMIN'])
        ));
        $this->assertFalse($policy->changeRole(
            $this->usuario(1, 'ADMIN'),
            $this->usuario(2, 'USUARIO'),
            new Rol(['nombre' => 'SUPER ADMIN'])
        ));
    }

    public function test_super_admin_can_assign_super_admin_role(): void
    {
        $policy = new UsuarioPolicy();

        $this->assertTrue($policy->assignRole(
            $this->usuario(1, 'SUPER ADMIN'),
            new Rol(['nombre' => 'SUPER ADMIN'])
        ));
    }

    public function test_admin_cannot_change_or_reset_super_admin_account(): void
    {
        $policy = new UsuarioPolicy();
        $admin = $this->usuario(1, 'ADMIN');
        $superAdmin = $this->usuario(2, 'SUPER ADMIN');
        $adminRole = new Rol(['nombre' => 'ADMIN']);

        $this->assertFalse($policy->update($admin, $superAdmin));
        $this->assertFalse($policy->changeRole($admin, $superAdmin, $adminRole));
        $this->assertFalse($policy->changeEstado($admin, $superAdmin));
        $this->assertFalse($policy->resetPassword($admin, $superAdmin));
    }

    public function test_admin_can_manage_non_super_admin_accounts(): void
    {
        $policy = new UsuarioPolicy();
        $admin = $this->usuario(1, 'ADMIN');
        $otroUsuario = $this->usuario(2, 'USUARIO');

        $this->assertTrue($policy->update($admin, $otroUsuario));
        $this->assertTrue($policy->changeRole(
            $admin,
            $otroUsuario,
            new Rol(['nombre' => 'ADMIN'])
        ));
        $this->assertTrue($policy->changeEstado($admin, $otroUsuario));
        $this->assertTrue($policy->resetPassword($admin, $otroUsuario));
    }

    private function usuario(int $id, string $rol): Usuario
    {
        $usuario = new Usuario();
        $usuario->id = $id;
        $usuario->setRelation('rol', new Rol(['nombre' => $rol]));

        return $usuario;
    }
}
