<?php

namespace Tests\Feature;

use App\Models\DevengadoLote;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\Concerns\BuildsDomainRecords;
use Tests\TestCase;

class AfiliadoDatosIncompletosTest extends TestCase
{
    use BuildsDomainRecords;

    public function test_mass_import_saves_blank_identifiers_as_null(): void
    {
        $colegio = $this->createCollege();
        $superAdmin = $this->createUser('SUPER ADMIN', $colegio);
        $archivo = UploadedFile::fake()->createWithContent(
            'afiliadas.csv',
            implode(';', [
                'registro_departamental',
                'matricula_profesional',
                'ci',
                'colegio',
                'nombres',
                'apellidos',
                'celular',
                'fecha_afiliacion',
            ])."\n;;;{$colegio->nombre};Ana;Quiroga;71234567;2026-01-15\n"
                .";;;{$colegio->nombre};Beatriz;Rojas;71234568;2026-01-16\n"
        );

        $this->actingAs($superAdmin)
            ->post(route('carga-datos.afiliados.validar'), [
                'archivo_afiliadas' => $archivo,
            ])
            ->assertRedirect(route('carga-datos.index'))
            ->assertSessionHas('mostrar_previsualizacion');

        $this->post(route('carga-datos.afiliados.confirmar'))
            ->assertRedirect(route('carga-datos.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('afiliados', [
            'nombres' => 'Ana',
            'apellidos' => 'Quiroga',
            'ci' => null,
            'registro_departamental' => null,
            'matricula_profesional' => null,
        ]);
        $this->assertDatabaseCount('afiliados', 2);
    }

    public function test_mass_import_rejects_an_invalid_affiliation_date(): void
    {
        $colegio = $this->createCollege();
        $superAdmin = $this->createUser('SUPER ADMIN', $colegio);
        $archivo = UploadedFile::fake()->createWithContent(
            'afiliadas.csv',
            implode(';', [
                'registro_departamental',
                'matricula_profesional',
                'ci',
                'colegio',
                'nombres',
                'apellidos',
                'celular',
                'fecha_afiliacion',
            ])."\nRD-1;MP-1;CI-1;{$colegio->nombre};Ana;Quiroga;71234567;0000-01-13\n"
        );

        $this->actingAs($superAdmin)
            ->post(route('carga-datos.afiliados.validar'), [
                'archivo_afiliadas' => $archivo,
            ])
            ->assertRedirect(route('carga-datos.index'))
            ->assertSessionHas('carga_datos.afiliados', fn (array $carga): bool =>
                $carga['errores'] === 1
                && $carga['filas'][0]['estado'] === 'ERROR'
            );

        $this->assertDatabaseCount('afiliados', 0);
    }

    public function test_mass_import_shows_a_generic_message_when_persistence_fails(): void
    {
        $colegio = $this->createCollege();
        $superAdmin = $this->createUser('SUPER ADMIN', $colegio);
        $existente = $this->createAffiliate($colegio);
        $fila = [
            'estado' => 'VALIDA',
            'registro_departamental' => $existente->registro_departamental,
            'matricula_profesional' => 'MP-NUEVA',
            'ci' => 'CI-NUEVO',
            'colegio_id' => $colegio->id,
            'nombres' => 'Ana',
            'apellidos' => 'Quiroga',
            'celular' => '71234567',
            'fecha_afiliacion' => '2026-01-15',
        ];

        $this->actingAs($superAdmin)
            ->withSession([
                'carga_datos.afiliados' => [
                    'total' => 1,
                    'errores' => 0,
                    'filas' => [$fila],
                ],
            ])
            ->post(route('carga-datos.afiliados.confirmar'))
            ->assertSessionHasErrors([
                'archivo_afiliadas' =>
                    'Ocurrió un error durante la carga. Inténtalo nuevamente.',
            ]);

        $this->assertDatabaseCount('afiliados', 1);
    }

    public function test_normal_creation_requires_all_three_identifiers(): void
    {
        $colegio = $this->createCollege();
        $admin = $this->createUser('ADMIN', $colegio);

        $this->actingAs($admin)
            ->post(route('afiliados.store'), [
                'registro_departamental' => '',
                'matricula_profesional' => '',
                'ci' => '',
                'colegio_id' => $colegio->id,
                'nombres' => 'Ana',
                'apellidos' => 'Quiroga',
                'celular' => '71234567',
                'fecha_afiliacion' => '2026-01-15',
            ])
            ->assertSessionHasErrors([
                'registro_departamental',
                'matricula_profesional',
                'ci',
            ]);

        $this->assertDatabaseCount('afiliados', 0);
    }

    public function test_normal_creation_rejects_an_invalid_affiliation_date(): void
    {
        $colegio = $this->createCollege();
        $admin = $this->createUser('ADMIN', $colegio);

        $this->actingAs($admin)
            ->post(route('afiliados.store'), [
                'registro_departamental' => 'RD-VALIDO',
                'matricula_profesional' => 'MP-VALIDA',
                'ci' => 'CI-VALIDO',
                'colegio_id' => $colegio->id,
                'nombres' => 'Ana',
                'apellidos' => 'Quiroga',
                'celular' => '71234567',
                'fecha_afiliacion' => '0000-01-13',
            ])
            ->assertSessionHasErrors('fecha_afiliacion');

        $this->assertDatabaseCount('afiliados', 0);
    }

    public function test_incomplete_affiliate_can_be_completed_but_identifiers_must_be_unique(): void
    {
        $colegio = $this->createCollege();
        $admin = $this->createUser('ADMIN', $colegio);
        $incompleta = $this->createAffiliate($colegio, [
            'ci' => null,
            'registro_departamental' => null,
            'matricula_profesional' => null,
        ]);
        $existente = $this->createAffiliate($colegio);
        $datos = [
            'registro_departamental' => $existente->registro_departamental,
            'matricula_profesional' => $existente->matricula_profesional,
            'ci' => $existente->ci,
            'nombres' => $incompleta->nombres,
            'apellidos' => $incompleta->apellidos,
            'celular' => $incompleta->celular,
            'fecha_afiliacion' => $incompleta->fecha_afiliacion->toDateString(),
        ];

        $this->actingAs($admin)
            ->put(route('afiliados.update', $incompleta), $datos)
            ->assertSessionHasErrors([
                'registro_departamental',
                'matricula_profesional',
                'ci',
            ]);

        $datos['registro_departamental'] = 'RD-COMPLETADO';
        $datos['matricula_profesional'] = 'MP-COMPLETADO';
        $datos['ci'] = 'CI-COMPLETADO';

        $this->put(route('afiliados.update', $incompleta), $datos)
            ->assertRedirect(route('afiliados.index'));

        $this->assertDatabaseHas('afiliados', [
            'id' => $incompleta->id,
            'registro_departamental' => 'RD-COMPLETADO',
            'matricula_profesional' => 'MP-COMPLETADO',
            'ci' => 'CI-COMPLETADO',
        ]);
    }

    public function test_ordinary_payment_is_blocked_when_required_identifiers_are_missing(): void
    {
        $colegio = $this->createCollege();
        $admin = $this->createUser('ADMIN', $colegio);
        $afiliada = $this->createAffiliate($colegio, [
            'ci' => null,
            'registro_departamental' => null,
            'matricula_profesional' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('pagos.store'), [
                'afiliado_id' => $afiliada->id,
                'monto_mensual' => '15.00',
                'monto_total' => '15.00',
            ])
            ->assertSessionHasErrors([
                'afiliado_id' => 'Completa estos datos antes de registrar un pago: CI, registro departamental, matrícula profesional.',
            ]);

        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_ordinary_payment_still_succeeds_for_complete_affiliate(): void
    {
        $colegio = $this->createCollege();
        $admin = $this->createUser('ADMIN', $colegio);
        $afiliada = $this->createAffiliate($colegio);

        $this->actingAs($admin)
            ->post(route('pagos.store'), [
                'afiliado_id' => $afiliada->id,
                'monto_mensual' => '15.00',
                'monto_total' => '15.00',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('pagos', [
            'afiliado_id' => $afiliada->id,
            'estado' => 'VERIFICADO',
        ]);
    }

    public function test_devengado_payment_is_blocked_when_required_identifiers_are_missing(): void
    {
        $colegio = $this->createCollege();
        $usuario = $this->createUser('USUARIO', $colegio);
        $afiliada = $this->createAffiliate($colegio, [
            'ci' => null,
            'registro_departamental' => null,
            'matricula_profesional' => null,
        ]);

        $this->actingAs($usuario)
            ->post(route('devengados.store'), [
                'colegio_id' => $colegio->id,
                'mes' => now()->month,
            ])
            ->assertRedirect(route('lotes.index'));

        $lote = DevengadoLote::query()->sole();

        $this->post(route('devengados.pagos.store'), [
            'devengado_lote_id' => $lote->id,
            'afiliado_id' => $afiliada->id,
            'clave_idempotencia' => (string) Str::uuid(),
            'monto_total' => '10.00',
            'monto_mensual' => '10.00',
            'metodo_determinacion' => 'MINIMO_VIGENTE',
            'periodos' => [],
        ])->assertSessionHasErrors([
            'afiliado_id' => 'Completa estos datos antes de registrar un pago: CI, registro departamental, matrícula profesional.',
        ]);

        $this->assertDatabaseCount('devengado_pagos', 0);
    }

    public function test_affiliate_list_marks_missing_identity_data_in_orange(): void
    {
        $colegio = $this->createCollege();
        $admin = $this->createUser('ADMIN', $colegio);
        $incompleta = $this->createAffiliate($colegio, [
            'ci' => null,
            'nombres' => '',
            'apellidos' => '',
            'registro_departamental' => null,
            'matricula_profesional' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('afiliados.index'));

        $response->assertOk()
            ->assertSee('text-orange-700')
            ->assertSee('Datos incompletos');

        $this->assertNotEmpty($incompleta->fresh()->camposIncompletos());
    }
}
