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
