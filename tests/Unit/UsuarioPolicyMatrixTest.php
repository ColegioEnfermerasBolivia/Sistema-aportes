<?php

namespace Tests\Unit;

use App\Models\Rol;
use App\Models\Usuario;
use App\Policies\UsuarioPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UsuarioPolicyMatrixTest extends TestCase
{
    #[DataProvider('roleAccessProvider')]
    public function test_list_and_create_access_for_each_role(
        string $role,
        bool $expected
    ): void {
        $policy = new UsuarioPolicy();
        $usuario = $this->usuario(1, $role);

        $this->assertSame($expected, $policy->viewAny($usuario));
        $this->assertSame($expected, $policy->create($usuario));
    }

    public static function roleAccessProvider(): array
    {
        return [
            'super admin' => ['SUPER ADMIN', true],
            'admin' => ['ADMIN', true],
            'regular user' => ['USUARIO', false],
            'unknown role' => ['OTRO', false],
        ];
    }

    public function test_admin_cannot_manage_super_admin_but_super_admin_can(): void
    {
        $policy = new UsuarioPolicy();
        $admin = $this->usuario(1, 'ADMIN');
        $superAdmin = $this->usuario(2, 'SUPER ADMIN');
        $targetRole = new Rol(['nombre' => 'USUARIO']);

        foreach ([
            fn () => $policy->update($admin, $superAdmin),
            fn () => $policy->changeRole($admin, $superAdmin, $targetRole),
            fn () => $policy->changeEstado($admin, $superAdmin),
            fn () => $policy->resetPassword($admin, $superAdmin),
        ] as $ability) {
            $this->assertFalse($ability());
        }

        $this->assertTrue($policy->update($superAdmin, $superAdmin));
        $this->assertTrue($policy->changeRole(
            $superAdmin,
            $admin,
            new Rol(['nombre' => 'SUPER ADMIN'])
        ));
        $this->assertTrue($policy->changeEstado($superAdmin, $admin));
        $this->assertTrue($policy->changeColegio($superAdmin, $admin));
        $this->assertTrue($policy->resetPassword($superAdmin, $admin));
    }

    public function test_admin_can_manage_non_super_admin_accounts_but_cannot_assign_super_admin(): void
    {
        $policy = new UsuarioPolicy();
        $admin = $this->usuario(1, 'ADMIN');
        $target = $this->usuario(2, 'USUARIO');

        $this->assertTrue($policy->assignRole(
            $admin,
            new Rol(['nombre' => 'ADMIN'])
        ));
        $this->assertFalse($policy->assignRole(
            $admin,
            new Rol(['nombre' => 'SUPER ADMIN'])
        ));
        $this->assertTrue($policy->changeRole(
            $admin,
            $target,
            new Rol(['nombre' => 'ADMIN'])
        ));
        $this->assertFalse($policy->changeRole(
            $admin,
            $target,
            new Rol(['nombre' => 'SUPER ADMIN'])
        ));
        $this->assertTrue($policy->changeEstado($admin, $target));
        $this->assertTrue($policy->resetPassword($admin, $target));
        $this->assertFalse($policy->changeColegio($admin, $target));
    }

    public function test_an_account_cannot_change_its_own_role_state_or_password(): void
    {
        $policy = new UsuarioPolicy();
        $admin = $this->usuario(1, 'ADMIN');
        $adminRole = new Rol(['nombre' => 'ADMIN']);

        $this->assertFalse($policy->changeRole($admin, $admin, $adminRole));
        $this->assertFalse($policy->changeEstado($admin, $admin));
        $this->assertFalse($policy->resetPassword($admin, $admin));
    }

    private function usuario(int $id, string $role): Usuario
    {
        $usuario = new Usuario();
        $usuario->id = $id;
        $usuario->setRelation('rol', new Rol(['nombre' => $role]));

        return $usuario;
    }
}
