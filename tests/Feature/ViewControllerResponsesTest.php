<?php

namespace Tests\Feature;

use Tests\Concerns\BuildsDomainRecords;
use Tests\TestCase;

class ViewControllerResponsesTest extends TestCase
{
    use BuildsDomainRecords;

    public function test_lote_creation_route_renders_its_form_page(): void
    {
        $college = $this->createCollege();
        $user = $this->createUser('USUARIO', $college);

        $this->actingAs($user)
            ->get(route('lotes.create'))
            ->assertOk()
            ->assertViewIs('aportes.lotes.create');
    }

    public function test_user_edit_route_renders_the_edit_form(): void
    {
        $admin = $this->createUser('ADMIN');
        $user = $this->createUser('USUARIO');

        $this->actingAs($admin)
            ->get(route('usuarios.edit', $user))
            ->assertOk()
            ->assertViewIs('usuarios.edit')
            ->assertSee('Editar usuario');
    }

    public function test_labor_information_route_renders_the_affiliate_records(): void
    {
        $college = $this->createCollege();
        $user = $this->createUser('USUARIO', $college);
        $affiliate = $this->createAffiliate($college, [
            'nombres' => 'Ana',
            'apellidos' => 'Prueba',
        ]);

        $this->actingAs($user)
            ->get(route('informacion-laboral.index', $affiliate))
            ->assertOk()
            ->assertViewIs('informacion-laboral.index')
            ->assertSee('Ana Prueba');
    }
}
