<?php

namespace Tests\Feature\Security;

use App\Models\Afiliado;
use Tests\Concerns\BuildsDomainRecords;
use Tests\TestCase;

class AffiliateAccessTest extends TestCase
{
    use BuildsDomainRecords;

    public function test_user_cannot_search_for_an_affiliate_in_another_college(): void
    {
        $homeCollege = $this->createCollege();
        $otherCollege = $this->createCollege();
        $user = $this->createUser('USUARIO', $homeCollege);
        $affiliate = $this->createAffiliate($otherCollege, [
            'ci' => '12345678',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/afiliados/buscar-ci?ci=12345678')
            ->assertNotFound();

        $this->assertArrayNotHasKey('id', $response->json());

        $this->assertDatabaseHas('afiliados', [
            'id' => $affiliate->id,
            'ci' => '12345678',
        ]);
    }

    public function test_sql_injection_text_is_treated_as_a_literal_ci(): void
    {
        $user = $this->createUser('ADMIN');
        $affiliate = $this->createAffiliate(null, ['ci' => '12345678']);

        $this->actingAs($user)
            ->getJson('/afiliados/buscar-ci?ci='.urlencode("' OR 1=1 --"))
            ->assertNotFound()
            ->assertJsonMissing(['ci' => $affiliate->ci]);
    }

    public function test_affiliate_create_ignores_privileged_and_server_managed_fields(): void
    {
        $college = $this->createCollege();
        $admin = $this->createUser('ADMIN', $college);

        $this->actingAs($admin)->post('/afiliados', [
            'id' => 99999,
            'registro_departamental' => 'RD-SEC-01',
            'matricula_profesional' => 'MP-SEC-01',
            'ci' => '98765432',
            'colegio_id' => $college->id,
            'nombres' => 'Maria',
            'apellidos' => 'Prueba',
            'celular' => '70000000',
            'fecha_afiliacion' => '2024-01-15',
            'estado' => 'INACTIVO',
            'role' => 'SUPER ADMIN',
            'is_admin' => true,
            'created_at' => '2000-01-01 00:00:00',
        ])->assertRedirect(route('afiliados.index'));

        $affiliate = Afiliado::query()
            ->where('ci', '98765432')
            ->firstOrFail();

        $this->assertNotSame(99999, $affiliate->id);
        $this->assertSame('ACTIVO', $affiliate->estado);
        $this->assertSame($college->id, $affiliate->colegio_id);
        $this->assertNull($affiliate->getAttribute('is_admin'));
    }

    public function test_departmental_user_sees_only_own_college_and_active_children_in_create_form(): void
    {
        $parent = $this->createCollege([
            'nombre' => 'Colegio Departamental Padre',
            'tipo' => 'DEPARTAMENTAL',
        ]);
        $child = $this->createCollege([
            'nombre' => 'Colegio Regional Hijo',
            'tipo' => 'REGIONAL',
            'colegio_padre_id' => $parent->id,
        ]);
        $inactiveChild = $this->createCollege([
            'nombre' => 'Colegio Regional Inactivo',
            'tipo' => 'REGIONAL',
            'colegio_padre_id' => $parent->id,
            'estado' => 'INACTIVO',
        ]);
        $otherParent = $this->createCollege([
            'tipo' => 'DEPARTAMENTAL',
        ]);
        $unrelatedChild = $this->createCollege([
            'nombre' => 'Colegio Regional No Permitido',
            'tipo' => 'REGIONAL',
            'colegio_padre_id' => $otherParent->id,
        ]);
        $user = $this->createUser('USUARIO', $parent);

        $this->actingAs($user)
            ->get('/afiliados')
            ->assertOk()
            ->assertViewHas(
                'colegios',
                fn ($colegios) => $colegios->modelKeys()
                    === [$parent->id, $child->id]
            )
            ->assertSee('Colegio Departamental Padre')
            ->assertSee('Colegio Regional Hijo')
            ->assertDontSee('Colegio Regional Inactivo')
            ->assertDontSee('Colegio Regional No Permitido');
    }

    public function test_departmental_user_can_create_affiliates_in_own_or_child_college_only(): void
    {
        $parent = $this->createCollege([
            'tipo' => 'DEPARTAMENTAL',
        ]);
        $child = $this->createCollege([
            'tipo' => 'REGIONAL',
            'colegio_padre_id' => $parent->id,
        ]);
        $otherCollege = $this->createCollege();
        $user = $this->createUser('USUARIO', $parent);

        $datos = [
            'registro_departamental' => 'RD-PADRE-01',
            'matricula_profesional' => 'MP-PADRE-01',
            'ci' => 'CI-PADRE-01',
            'nombres' => 'Afiliada',
            'apellidos' => 'Colegio Hijo',
            'celular' => '70000000',
            'fecha_afiliacion' => '2024-01-15',
        ];

        $this->actingAs($user)
            ->post('/afiliados', $datos + ['colegio_id' => $child->id])
            ->assertRedirect(route('afiliados.index'));

        $this->assertDatabaseHas('afiliados', [
            'ci' => 'CI-PADRE-01',
            'colegio_id' => $child->id,
        ]);

        $this->post('/afiliados', [
            ...$datos,
            'registro_departamental' => 'RD-PADRE-02',
            'matricula_profesional' => 'MP-PADRE-02',
            'ci' => 'CI-PADRE-02',
            'colegio_id' => $parent->id,
        ])->assertRedirect(route('afiliados.index'));

        $this->assertDatabaseHas('afiliados', [
            'ci' => 'CI-PADRE-02',
            'colegio_id' => $parent->id,
        ]);

        $this->post('/afiliados', [
            ...$datos,
            'registro_departamental' => 'RD-PADRE-03',
            'matricula_profesional' => 'MP-PADRE-03',
            'ci' => 'CI-PADRE-03',
            'colegio_id' => $otherCollege->id,
        ])->assertSessionHasErrors('colegio_id');

        $this->assertDatabaseMissing('afiliados', [
            'ci' => 'CI-PADRE-03',
        ]);
    }

    public function test_regional_user_does_not_get_a_college_selector_or_change_assigned_college(): void
    {
        $parent = $this->createCollege([
            'tipo' => 'DEPARTAMENTAL',
        ]);
        $regional = $this->createCollege([
            'tipo' => 'REGIONAL',
            'colegio_padre_id' => $parent->id,
        ]);
        $user = $this->createUser('USUARIO', $regional);

        $this->actingAs($user)
            ->get('/afiliados')
            ->assertOk()
            ->assertDontSee('name="colegio_id"', false);

        $this->post('/afiliados', [
            'registro_departamental' => 'RD-REG-01',
            'matricula_profesional' => 'MP-REG-01',
            'ci' => 'CI-REG-01',
            'nombres' => 'Afiliada',
            'apellidos' => 'Regional',
            'celular' => '70000000',
            'fecha_afiliacion' => '2024-01-15',
            'colegio_id' => $parent->id,
        ])->assertRedirect(route('afiliados.index'));

        $this->assertDatabaseHas('afiliados', [
            'ci' => 'CI-REG-01',
            'colegio_id' => $regional->id,
        ]);
    }

    public function test_search_text_is_rendered_as_escaped_html(): void
    {
        $college = $this->createCollege();
        $admin = $this->createUser('ADMIN', $college);
        $payload = '<script>alert(1)</script>';

        $this->createAffiliate($college, [
            'nombres' => $payload,
        ]);

        $response = $this->actingAs($admin)->get('/afiliados');

        $response->assertOk()
            ->assertSee(e($payload), false)
            ->assertDontSee($payload, false);
    }
}
