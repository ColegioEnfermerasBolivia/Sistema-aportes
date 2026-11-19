<?php

namespace Tests\Feature;

use App\Models\Colegio;
use Tests\Concerns\BuildsLoteScenarios;
use Tests\TestCase;

class PublicApiTest extends TestCase
{
    use BuildsLoteScenarios;

    private function headers(string $key = 'test-key'): array
    {
        return ['X-API-Key' => $key, 'Accept' => 'application/json'];
    }

    private function affiliate(Colegio $college, string $nombres, string $apellidos)
    {
        $afiliada = $this->makeAffiliate($college);
        $afiliada->forceFill(['nombres' => $nombres, 'apellidos' => $apellidos])->save();

        return $afiliada;
    }

    public function test_request_without_or_with_wrong_key_is_rejected(): void
    {
        $url = '/api/v1/afiliados?nombres=maria&apellidos=quispe';

        $this->getJson($url)->assertUnauthorized();
        $this->getJson($url, $this->headers('incorrecta'))->assertUnauthorized();
    }

    public function test_names_and_surnames_are_both_required(): void
    {
        foreach ([
            '',
            '?nombres=maria',
            '?apellidos=quispe',
            '?departamento=la paz',
            '?nombres=maria&departamento=la paz',
            '?nombres=ma&apellidos=quispe',
        ] as $consulta) {
            $this->getJson('/api/v1/afiliados'.$consulta, $this->headers())
                ->assertUnprocessable();
        }

        $this->getJson('/api/v1/afiliados?nombres=maria&apellidos=quispe', $this->headers())
            ->assertOk();
    }

    public function test_response_only_contains_data_and_never_the_ci(): void
    {
        $college = $this->makeCollege('CBBA');
        $this->affiliate($college, 'María Elena', 'Quispe');

        $respuesta = $this->getJson('/api/v1/afiliados?nombres=maria&apellidos=quispe', $this->headers())
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertSame(['data'], array_keys($respuesta->json()));
        $this->assertSame(
            ['nombres', 'apellidos', 'especialidad', 'departamento'],
            array_keys($respuesta->json('data.0'))
        );
        $this->assertStringNotContainsString('"ci"', $respuesta->getContent());
        $this->assertStringNotContainsString('path', $respuesta->getContent());
    }

    public function test_names_and_surnames_are_searched_in_their_own_column(): void
    {
        $college = $this->makeCollege('CBBA');
        $this->affiliate($college, 'María Elena', 'Quispe Mamani');
        $this->affiliate($college, 'Quispe', 'Rojas');

        $this->getJson('/api/v1/afiliados?nombres=maria&apellidos=quispe', $this->headers())
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.nombres', 'María Elena');

        $this->getJson('/api/v1/afiliados?nombres=quispe&apellidos=rojas', $this->headers())
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.apellidos', 'Rojas');

        // Invertidos no deben encontrar a nadie
        $this->getJson('/api/v1/afiliados?nombres=quispe&apellidos=quispe', $this->headers())
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_inactive_affiliates_are_never_returned(): void
    {
        $college = $this->makeCollege('CBBA');
        $this->affiliate($college, 'Ana', 'Inactiva')->forceFill(['estado' => 'INACTIVO'])->save();

        $this->getJson('/api/v1/afiliados?nombres=ana&apellidos=inactiva', $this->headers())
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_department_is_optional_and_includes_regional_colleges(): void
    {
        $padre = $this->makeCollege('LPZ'); // nombre: "Colegio LPZ"
        $regional = (new Colegio)->forceFill([
            'nombre' => 'Regional El Alto', 'codigo' => 'ALTO', 'tipo' => 'REGIONAL',
            'colegio_padre_id' => $padre->id, 'estado' => 'ACTIVO',
        ]);
        $regional->save();
        $otro = $this->makeCollege('SCZ');

        $this->affiliate($padre, 'Maria', 'Perez Uno');
        $this->affiliate($regional, 'Maria', 'Perez Dos');
        $this->affiliate($otro, 'Maria', 'Perez Tres');

        // Sin departamento: encuentra a las tres
        $this->getJson('/api/v1/afiliados?nombres=maria&apellidos=perez', $this->headers())
            ->assertOk()->assertJsonCount(3, 'data');

        // Con departamento: la del colegio y la de su regional
        $this->getJson('/api/v1/afiliados?nombres=maria&apellidos=perez&departamento=LPZ', $this->headers())
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.departamento', 'Colegio LPZ')
            ->assertJsonPath('data.1.departamento', 'Colegio LPZ');
    }

    public function test_wildcards_are_treated_as_literal_text(): void
    {
        $college = $this->makeCollege('CBBA');
        $this->affiliate($college, 'Ana', 'Perez');

        $this->getJson('/api/v1/afiliados?nombres=%25%25%25&apellidos=perez', $this->headers())
            ->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/afiliados?nombres=ana&apellidos=___', $this->headers())
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_per_page_above_the_limit_is_rejected(): void
    {
        $this->getJson('/api/v1/afiliados?nombres=maria&apellidos=quispe&per_page=500', $this->headers())
            ->assertUnprocessable();
    }
}