<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Tests\Concerns\BuildsDomainRecords;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use BuildsDomainRecords;

    public function test_guest_is_redirected_from_the_dashboard_to_login(): void
    {
        $this->get('/dashboard')
            ->assertRedirect(route('login'));
    }

    public function test_successful_login_authenticates_and_logs_the_user(): void
    {
        $user = $this->createUser('USUARIO', null, [
            'username' => 'qa.user',
            'password' => Hash::make('Password123!'),
        ]);

        $this->post('/login', [
            'username' => 'qa.user',
            'password' => 'Password123!',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('login_logs', [
            'usuario_id' => $user->id,
            'resultado' => 'EXITOSO',
        ]);
    }

    public function test_login_regenerates_the_session_id(): void
    {
        $user = $this->createUser('USUARIO', null, [
            'username' => 'session.user',
            'password' => Hash::make('Password123!'),
        ]);
        $this->get('/login');
        $oldSessionId = session()->getId();

        $this->post('/login', [
            'username' => 'session.user',
            'password' => 'Password123!',
        ])->assertRedirect(route('dashboard'));

        $this->assertNotSame($oldSessionId, session()->getId());
        $this->assertAuthenticatedAs($user);
    }

    public function test_logout_invalidates_the_authenticated_session(): void
    {
        $user = $this->createUser('USUARIO');

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = $this->createUser('USUARIO', null, [
            'username' => 'inactive.user',
            'password' => Hash::make('Password123!'),
            'estado' => 'INACTIVO',
        ]);

        $this->from('/login')->post('/login', [
            'username' => 'inactive.user',
            'password' => 'Password123!',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors('username');

        $this->assertGuest();
        $this->assertDatabaseHas('login_logs', [
            'usuario_id' => $user->id,
            'resultado' => 'FALLIDO',
            'motivo' => 'USUARIO_INACTIVO',
        ]);
    }

    public function test_login_is_throttled_after_five_attempts(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from('/login')->post('/login', [
                'username' => 'unknown.user',
                'password' => 'wrong-password',
            ])->assertRedirect('/login');
        }

        $this->post('/login', [
            'username' => 'unknown.user',
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }

    public function test_password_is_hashed_and_hidden_from_model_serialization(): void
    {
        $user = $this->createUser('USUARIO');
        $serialized = $user->toArray();

        $this->assertNotSame('Password123!', $user->password);
        $this->assertTrue(Hash::check('Password123!', $user->password));
        $this->assertArrayNotHasKey('password', $serialized);
    }
}
