<?php

namespace Tests\Feature\Security;

use Tests\Concerns\BuildsDomainRecords;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use BuildsDomainRecords;

    public function test_regular_user_cannot_access_user_administration_or_imports(): void
    {
        $user = $this->createUser('USUARIO');

        $this->actingAs($user)
            ->get('/usuarios')
            ->assertForbidden();

        $this->get('/transferencias')
            ->assertForbidden();
    }

    public function test_admin_cannot_access_super_admin_transfer_import_routes(): void
    {
        $admin = $this->createUser('ADMIN');

        $this->actingAs($admin)
            ->get('/transferencias')
            ->assertForbidden();

        $this->post('/transferencias/confirmar')
            ->assertForbidden();
    }
}
