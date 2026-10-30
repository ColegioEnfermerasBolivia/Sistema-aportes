<?php

namespace Tests\Feature\Security;

use Tests\Concerns\BuildsDomainRecords;
use Tests\TestCase;

class InactiveAccountRevocationTest extends TestCase
{
    use BuildsDomainRecords;

    public function test_deactivated_account_cannot_continue_using_an_existing_session(): void
    {
        $user = $this->createUser('USUARIO');
        $this->actingAs($user);

        $user->update(['estado' => 'INACTIVO']);

        $this->get('/dashboard')->assertForbidden();

        $this->assertGuest();
    }
}
