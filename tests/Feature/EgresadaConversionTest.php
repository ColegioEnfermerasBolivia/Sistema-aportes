<?php

namespace Tests\Feature;

use App\Models\Afiliado;
use App\Models\Departamento;
use App\Models\Egresada;
use App\Models\EgresadaFormacion;
use App\Models\Universidad;
use App\Models\Usuario;
use App\Exports\EgresadasExport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Excel;
use Tests\Concerns\BuildsDomainRecords;
use Tests\TestCase;

class EgresadaConversionTest extends TestCase
{
    use BuildsDomainRecords;

    public function test_admin_conversion_preserves_egresada_and_academic_history_and_cannot_repeat(): void
    {
        $adminCollege = $this->createCollege();
        $college = $this->createCollege();
        $admin = $this->createUser('ADMIN', $adminCollege);
        $egresada = $this->createEgresada($admin, '778899');
        $formacion = $this->createFormacion($admin, $egresada);

        $this->actingAs($admin)
            ->post(route('egresadas.convertir', $egresada), $this->datosConversion($college->id))
            ->assertRedirect();

        $afiliado = Afiliado::query()
            ->where('egresada_id', $egresada->id)
            ->firstOrFail();

        $this->assertSame($egresada->ci, $afiliado->ci);
        $this->assertDatabaseHas('egresadas', [
            'id' => $egresada->id,
            'estado' => 'AFILIADA',
            'convertida_por' => $admin->id,
            'colegio_asignado_id' => $college->id,
        ]);
        $this->assertDatabaseHas('egresada_formacion', [
            'id' => $formacion->id,
            'egresada_id' => $egresada->id,
        ]);
        $this->assertNotNull($egresada->fresh()->convertida_en);
        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $admin->id,
            'accion' => 'CONVERTIR_AFILIADA',
            'tabla_afectada' => 'egresadas',
            'registro_id' => $egresada->id,
        ]);

        $this->post(
            route('egresadas.convertir', $egresada),
            $this->datosConversion($college->id)
        )->assertForbidden();

        $this->get(route('egresadas.edit', $egresada))
            ->assertForbidden();
    }

    public function test_user_can_only_assign_conversion_to_their_own_college(): void
    {
        $ownCollege = $this->createCollege();
        $otherCollege = $this->createCollege();
        $user = $this->createUser('USUARIO', $ownCollege);
        $egresada = $this->createEgresada($user, '889900');

        $this->actingAs($user)
            ->from(route('egresadas.index'))
            ->post(
                route('egresadas.convertir', $egresada),
                $this->datosConversion($otherCollege->id)
            )
            ->assertSessionHasErrors('colegio_id');

        $this->assertDatabaseCount('afiliados', 0);

        $this->post(
            route('egresadas.convertir', $egresada),
            $this->datosConversion($ownCollege->id)
        )->assertRedirect();

        $this->assertDatabaseHas('afiliados', [
            'egresada_id' => $egresada->id,
            'colegio_id' => $ownCollege->id,
        ]);
    }

    public function test_conversion_rejects_a_ci_already_in_the_affiliate_registry(): void
    {
        $college = $this->createCollege();
        $admin = $this->createUser('ADMIN', $college);
        $this->createAffiliate($college, ['ci' => '990011']);
        $egresada = $this->createEgresada($admin, '990011');

        $this->actingAs($admin)
            ->from(route('egresadas.index'))
            ->post(
                route('egresadas.convertir', $egresada),
                $this->datosConversion($college->id)
            )
            ->assertSessionHasErrors('ci');

        $this->assertDatabaseMissing('afiliados', [
            'egresada_id' => $egresada->id,
        ]);
        $this->assertDatabaseHas('afiliados', [
            'ci' => '990011',
            'egresada_id' => null,
        ]);
        $this->assertDatabaseHas('egresadas', [
            'id' => $egresada->id,
            'estado' => 'ACTIVA',
        ]);
    }

    public function test_conversion_requires_at_least_one_titled_formation(): void
    {
        $college = $this->createCollege();
        $admin = $this->createUser('ADMIN', $college);
        $egresada = $this->createEgresada($admin, '990022');
        $formacion = $this->createFormacion($admin, $egresada);
        $formacion->update(['estado_titulacion' => 'EGRESADA']);

        $this->actingAs($admin)
            ->get(route('egresadas.index'))
            ->assertOk()
            ->assertSee('Requiere titulación');

        $this->actingAs($admin)
            ->from(route('egresadas.index'))
            ->post(
                route('egresadas.convertir', $egresada),
                $this->datosConversion($college->id)
            )
            ->assertSessionHasErrors('egresada');

        $this->assertDatabaseMissing('afiliados', [
            'egresada_id' => $egresada->id,
        ]);
        $this->assertDatabaseHas('egresadas', [
            'id' => $egresada->id,
            'estado' => 'ACTIVA',
        ]);
    }

    public function test_superadmin_can_add_edit_and_remove_formations_in_the_edit_modal(): void
    {
        $college = $this->createCollege();
        $superAdmin = $this->createUser('SUPER ADMIN', $college);
        $egresada = $this->createEgresada($superAdmin, '991122');
        $departamento = Departamento::query()->create([
            'codigo' => 'LP',
            'nombre' => 'La Paz',
        ]);
        $universidadA = Universidad::query()->create([
            'nombre' => 'Universidad A',
            'tipo' => 'PUBLICA',
        ]);
        $universidadB = Universidad::query()->create([
            'nombre' => 'Universidad B',
            'tipo' => 'PRIVADA',
        ]);
        $formacionEditada = EgresadaFormacion::query()->create([
            'egresada_id' => $egresada->id,
            'universidad_id' => $universidadA->id,
            'departamento_id' => $departamento->id,
            'carrera' => 'Enfermería',
            'estado_titulacion' => 'EGRESADA',
            'registrado_por' => $superAdmin->id,
        ]);
        $formacionEliminada = EgresadaFormacion::query()->create([
            'egresada_id' => $egresada->id,
            'universidad_id' => $universidadB->id,
            'departamento_id' => $departamento->id,
            'carrera' => 'Obstetricia',
            'estado_titulacion' => 'EGRESADA',
            'registrado_por' => $superAdmin->id,
        ]);

        $this->actingAs($superAdmin)
            ->put(route('egresadas.update', $egresada), [
                'ci' => '991122',
                'nombres' => 'Nombre actualizado',
                'apellidos' => 'Apellido actualizado',
                'celular' => '70000000',
                'formaciones_present' => '1',
                'formaciones' => [
                    [
                        'id' => $formacionEditada->id,
                        'universidad_id' => $universidadA->id,
                        'departamento_id' => $departamento->id,
                        'carrera' => 'Enfermería actualizada',
                        'fecha_egreso' => '2025-12-01',
                        'estado_titulacion' => 'TITULADA',
                        'numero_titulo' => 'T-1',
                    ],
                    [
                        'universidad_id' => $universidadB->id,
                        'departamento_id' => $departamento->id,
                        'carrera' => 'Salud pública',
                        'fecha_egreso' => '',
                        'estado_titulacion' => 'EGRESADA',
                        'numero_titulo' => '',
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('egresadas', [
            'id' => $egresada->id,
            'nombres' => 'Nombre actualizado',
            'actualizado_por' => $superAdmin->id,
        ]);
        $this->assertDatabaseHas('egresada_formacion', [
            'id' => $formacionEditada->id,
            'carrera' => 'Enfermería actualizada',
            'estado_titulacion' => 'TITULADA',
            'actualizado_por' => $superAdmin->id,
        ]);
        $this->assertDatabaseMissing('egresada_formacion', [
            'id' => $formacionEliminada->id,
        ]);
        $this->assertDatabaseHas('egresada_formacion', [
            'egresada_id' => $egresada->id,
            'carrera' => 'Salud pública',
        ]);
    }

    public function test_excel_download_includes_every_record_matching_the_filters(): void
    {
        Carbon::setTestNow('2026-11-27 12:00:00');

        try {
            $college = $this->createCollege();
            $admin = $this->createUser('ADMIN', $college);

            for ($index = 0; $index < 21; $index++) {
                $this->createEgresada(
                    $admin,
                    '77'.str_pad((string) $index, 4, '0', STR_PAD_LEFT)
                );
            }

            $this->createEgresada($admin, '880000');
            Excel::fake();

            $this->actingAs($admin)
                ->get(route('egresadas.exportar.excel', ['ci' => '77']))
                ->assertOk();

            Excel::assertDownloaded(
                'padron-egresadas-20261127-120000.xlsx',
                function ($export): bool {
                    $this->assertInstanceOf(EgresadasExport::class, $export);
                    $this->assertCount(21, $export->query()->get());

                    return true;
                }
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    private function createEgresada(Usuario $usuario, string $ci): Egresada
    {
        return Egresada::query()->create([
            'ci' => $ci,
            'nombres' => 'Nombre de prueba',
            'apellidos' => 'Apellido de prueba',
            'celular' => '70000000',
            'registrado_por' => $usuario->id,
        ]);
    }

    private function createFormacion(
        Usuario $usuario,
        Egresada $egresada
    ): EgresadaFormacion {
        $departamento = Departamento::query()->create([
            'codigo' => 'LP',
            'nombre' => 'La Paz',
        ]);
        $universidad = Universidad::query()->create([
            'nombre' => 'Universidad de prueba',
            'sigla' => 'UP',
            'tipo' => 'PUBLICA',
        ]);

        return EgresadaFormacion::query()->create([
            'egresada_id' => $egresada->id,
            'universidad_id' => $universidad->id,
            'departamento_id' => $departamento->id,
            'carrera' => 'Enfermería',
            'estado_titulacion' => 'TITULADA',
            'registrado_por' => $usuario->id,
        ]);
    }

    private function datosConversion(int $colegioId): array
    {
        return [
            'registro_departamental' => 'LP-'.str_pad((string) $colegioId, 6, '0', STR_PAD_LEFT),
            'matricula_profesional' => 'MAT-'.str_pad((string) $colegioId, 6, '0', STR_PAD_LEFT),
            'celular' => '70000000',
            'fecha_afiliacion' => now()->toDateString(),
            'colegio_id' => $colegioId,
        ];
    }
}
