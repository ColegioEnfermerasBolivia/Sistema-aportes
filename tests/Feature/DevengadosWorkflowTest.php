<?php

namespace Tests\Feature;

use App\Models\Aporte;
use App\Models\DevengadoLote;
use App\Models\DevengadoPago;
use App\Models\Usuario;
use App\Services\DevengadoService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsDomainRecords;
use Tests\TestCase;

class DevengadosWorkflowTest extends TestCase
{
    use BuildsDomainRecords;

    public function test_one_lot_of_each_type_can_share_a_period_but_same_type_duplicates_are_blocked(): void
    {
        $colegio = $this->createCollege();
        $usuario = $this->createUser('USUARIO', $colegio);
        $mes = now()->month;

        $this->actingAs($usuario)
            ->post(route('devengados.store'), [
                'colegio_id' => $colegio->id,
                'mes' => $mes,
            ])
            ->assertRedirect(route('lotes.index'));

        $loteDevengado = DevengadoLote::query()->sole();
        $this->assertSame(
            'DEV-'.$colegio->codigo.'-'.now()->year.'-'.str_pad((string) $mes, 2, '0', STR_PAD_LEFT),
            $loteDevengado->codigo_lote
        );
        $this->assertDatabaseMissing('lote_verificacion', [
            'colegio_id' => $colegio->id,
            'mes' => $mes,
            'year' => now()->year,
        ]);

        $this->from(route('lotes.create'))
            ->post(route('lotes.store'), [
                'colegio_id' => $colegio->id,
                'mes' => $mes,
            ])
            ->assertRedirect(route('lotes.index'));

        $this->assertDatabaseCount('lote_verificacion', 1);
        $this->assertDatabaseCount('devengado_lotes', 1);

        $this->from(route('devengados.create'))
            ->post(route('devengados.store'), [
                'colegio_id' => $colegio->id,
                'mes' => $mes,
            ])
            ->assertRedirect(route('devengados.create'))
            ->assertSessionHasErrors('lote');

        $this->from(route('lotes.create'))
            ->post(route('lotes.store'), [
                'colegio_id' => $colegio->id,
                'mes' => $mes,
            ])
            ->assertRedirect(route('lotes.create'))
            ->assertSessionHasErrors('lote');

        $otroColegio = $this->createCollege();
        $otroUsuario = $this->createUser('USUARIO', $otroColegio);
        $this->actingAs($otroUsuario)
            ->post(route('lotes.store'), [
                'colegio_id' => $otroColegio->id,
                'mes' => $mes,
            ])
            ->assertRedirect(route('lotes.index'));
        $this->from(route('devengados.create'))
            ->post(route('devengados.store'), [
                'colegio_id' => $otroColegio->id,
                'mes' => $mes,
            ])
            ->assertRedirect(route('lotes.index'));
        $this->assertDatabaseCount('lote_verificacion', 2);

        $this->actingAs($otroUsuario)
            ->post(route('devengados.store'), [
                'colegio_id' => $otroColegio->id,
                'mes' => $mes,
            ])
            ->assertRedirect(route('devengados.create'))
            ->assertSessionHasErrors('lote');
        $this->assertDatabaseCount('devengado_lotes', 2);
    }

    public function test_devengado_search_lists_name_matches_and_skips_unused_payment_detail_queries(): void
    {
        $colegio = $this->createCollege();
        $usuario = $this->createUser('USUARIO', $colegio);
        $afiliada = $this->createAffiliate($colegio, [
            'ci' => '2175678',
            'nombres' => 'Lucy',
            'apellidos' => 'Achu Yahuita',
            'fecha_afiliacion' => '1978-09-14',
        ]);
        $this->createAffiliate($colegio, [
            'ci' => null,
            'nombres' => 'Lucy',
            'apellidos' => 'Mamani',
        ]);

        foreach (['Achu Lucy', 'Lucy Yahuita'] as $termino) {
            $this->actingAs($usuario)
                ->getJson(route('devengados.buscar-afiliada', [
                    'buscar' => $termino,
                ]))
                ->assertOk()
                ->assertJsonPath('lista', true)
                ->assertJsonCount(1, 'resultados')
                ->assertJsonPath('resultados.0.id', $afiliada->id);
        }

        $this->getJson(route('devengados.buscar-afiliada', [
            'buscar' => 'Lucy',
        ]))
            ->assertOk()
            ->assertJsonPath('lista', true)
            ->assertJsonCount(2, 'resultados');

        $this->getJson(route('devengados.buscar-afiliada', [
            'afiliado_id' => $afiliada->id,
        ]))
            ->assertOk()
            ->assertJsonPath('id', $afiliada->id)
            ->assertJsonStructure(['gestiones', 'lotes'])
            ->assertJsonPath('gestiones.0.year', 1978)
            ->assertJsonPath('gestiones.0.meses.0.mes', 9);

        $consultas = [];
        DB::listen(function (QueryExecuted $consulta) use (&$consultas): void {
            $consultas[] = $consulta->sql;
        });

        $this->getJson(route('devengados.buscar-afiliada', [
            'ci' => $afiliada->ci,
        ]))
            ->assertOk()
            ->assertJsonPath('id', $afiliada->id)
            ->assertJsonStructure(['gestiones', 'lotes'])
            ->assertJsonPath('gestiones.0.year', 1978)
            ->assertJsonPath('gestiones.0.meses.0.mes', 9);

        $this->assertFalse(
            collect($consultas)->contains(
                fn (string $sql): bool => str_contains(
                    strtolower($sql),
                    'devengado_pago_detalles'
                )
            )
        );
    }

    public function test_affiliate_with_invalid_affiliation_date_is_blocked_with_a_correction_message(): void
    {
        $colegio = $this->createCollege();
        $usuario = $this->createUser('USUARIO', $colegio);
        $afiliada = $this->createAffiliate($colegio, [
            'ci' => '2175678',
            'fecha_afiliacion' => '0000-01-13',
        ]);
        $mensaje = 'La fecha de afiliación no es válida. Corrígela en los datos de la afiliada antes de registrar pagos devengados.';

        $this->actingAs($usuario)
            ->getJson(route('devengados.buscar-afiliada', [
                'ci' => $afiliada->ci,
            ]))
            ->assertUnprocessable()
            ->assertJsonPath('message', $mensaje);

        $lote = $this->crearLoteDevengado($colegio, $usuario);
        $this->post(route('devengados.pagos.store'), [
            'devengado_lote_id' => $lote->id,
            'afiliado_id' => $afiliada->id,
            'clave_idempotencia' => (string) Str::uuid(),
            'monto_total' => '15.00',
            'monto_mensual' => '15.00',
            'metodo_determinacion' => 'MINIMO_VIGENTE',
            'periodos' => [now()->format('Y-m')],
        ])
            ->assertSessionHasErrors('afiliado_id');

        $this->assertDatabaseCount('devengado_pagos', 0);
    }

    public function test_payment_records_selected_affiliation_gaps_and_becomes_a_canonical_verified_aporte(): void
    {
        $colegio = $this->createCollege();
        $usuario = $this->createUser('USUARIO', $colegio);
        $admin = $this->createUser('ADMIN', $colegio);
        $inicio = now()->startOfMonth()->subMonths(3);
        $afiliada = $this->createAffiliate($colegio, [
            'fecha_afiliacion' => $inicio->toDateString(),
        ]);
        $periodos = collect(range(0, 2))
            ->map(fn (int $offset) => $inicio->copy()->addMonths($offset))
            ->map(fn ($fecha): string => $fecha->format('Y-m'));

        $this->actingAs($usuario)
            ->getJson(route('devengados.buscar-afiliada', ['ci' => $afiliada->ci]))
            ->assertOk()
            ->assertJsonPath('gestiones.0.meses.0.estado', 'LAGUNA');

        $this->post(route('devengados.store'), [
            'colegio_id' => $colegio->id,
            'mes' => now()->month,
        ])->assertRedirect(route('lotes.index'));
        $lote = DevengadoLote::query()->sole();
        $clave = (string) Str::uuid();
        $seleccion = $periodos->all();
        $payload = [
            'devengado_lote_id' => $lote->id,
            'afiliado_id' => $afiliada->id,
            'clave_idempotencia' => $clave,
            'monto_total' => '40.00',
            'monto_mensual' => '10.00',
            'metodo_determinacion' => 'MINIMO_VIGENTE',
            'periodos' => $seleccion,
        ];

        $this->post(route('devengados.pagos.store'), $payload)
            ->assertRedirect(route('devengados.index'));
        $this->post(route('devengados.pagos.store'), $payload)
            ->assertRedirect(route('devengados.index'))
            ->assertSessionHas('success');

        $pago = DevengadoPago::query()->sole();
        $this->assertSame('10.00', $pago->saldo_generado);
        $this->assertSame(3, $pago->cantidad_periodos);
        $this->assertDatabaseCount('devengado_pago_detalles', 3);

        $this->get(route('devengados.index'))
            ->assertOk()
            ->assertSee(route('devengados.pagos.edit', $pago));

        $this->actingAs($usuario)
            ->put(route('devengados.pagos.update', $pago), [
                'monto_total' => '50.00',
                'monto_mensual' => '15.00',
                'metodo_determinacion' => 'BOLETAS_SALARIALES',
                'periodos' => $seleccion,
            ])
            ->assertRedirect(route('devengados.show', $lote));
        $this->assertSame('5.00', $pago->fresh()->saldo_generado);
        $this->assertSame('15.00', $pago->detalles()->firstOrFail()->periodo->monto);

        $this->patch(route('devengados.submit', $lote))
            ->assertRedirect();
        $this->get(route('devengados.index'))
            ->assertOk()
            ->assertDontSee(route('devengados.pagos.edit', $pago));
        $this->get(route('devengados.pagos.edit', $pago))
            ->assertForbidden();
        $this->actingAs($admin)
            ->get(route('devengados.show', $lote))
            ->assertOk()
            ->assertSee('Aportes y saldos a registrar');
        $this->assertSame('EN_VERIFICACION', $lote->fresh()->estado);

        $this->post(route('devengados.confirmar-pagos', $lote), [
            'pago_ids' => [$pago->id],
        ])->assertRedirect(route('lotes.index'));

        $this->assertDatabaseCount('aportes', 3);
        $this->assertDatabaseHas('aportes', [
            'afiliado_id' => $afiliada->id,
            'mes' => $inicio->month,
            'year' => $inicio->year,
            'monto' => '15.00',
            'pago_id' => null,
            'estado' => 'VERIFICADO',
        ]);
        $this->assertSame('VERIFICADO', $lote->fresh()->estado);
        $this->assertSame('VERIFICADO', $pago->fresh()->estado);
        $this->assertDatabaseHas('saldos', [
            'afiliado_id' => $afiliada->id,
            'pago_id' => null,
            'monto' => '5.00',
            'monto_disponible' => '5.00',
        ]);

        $this->actingAs($usuario)
            ->put(route('devengados.pagos.update', $pago), [
                'monto_total' => '50.00',
                'monto_mensual' => '15.00',
                'metodo_determinacion' => 'BOLETAS_SALARIALES',
                'periodos' => $seleccion,
            ])
            ->assertForbidden();
    }

    public function test_existing_aporte_in_any_state_blocks_a_devengado_period_without_overwriting_it(): void
    {
        $colegio = $this->createCollege();
        $usuario = $this->createUser('USUARIO', $colegio);
        $afiliada = $this->createAffiliate($colegio, [
            'fecha_afiliacion' => now()->startOfMonth()->subYear()->toDateString(),
        ]);
        $fecha = now()->startOfMonth()->subMonth();
        $existente = $this->createAporte($afiliada, $fecha->month, $fecha->year, '88.00', 'OBSERVADO');
        $lote = $this->crearLoteDevengado($colegio, $usuario);

        $this->actingAs($usuario)
            ->from(route('devengados.index'))
            ->post(route('devengados.pagos.store'), [
                'devengado_lote_id' => $lote->id,
                'afiliado_id' => $afiliada->id,
                'clave_idempotencia' => (string) Str::uuid(),
                'monto_total' => '15.00',
                'monto_mensual' => '15.00',
                'metodo_determinacion' => 'MINIMO_VIGENTE',
                'periodos' => [$fecha->format('Y-m')],
            ])
            ->assertRedirect(route('devengados.index'))
            ->assertSessionHasErrors('periodos');

        $this->assertSame('OBSERVADO', $existente->fresh()->estado);
        $this->assertSame('88.00', $existente->fresh()->monto);
        $this->assertDatabaseCount('devengado_pagos', 0);
        $this->assertDatabaseCount('aportes', 1);
    }

    public function test_partial_payment_without_a_full_period_is_saved_as_saldo_only(): void
    {
        $colegio = $this->createCollege();
        $usuario = $this->createUser('USUARIO', $colegio);
        $admin = $this->createUser('ADMIN', $colegio);
        $afiliada = $this->createAffiliate($colegio);
        $lote = $this->crearLoteDevengado($colegio, $usuario);

        $this->actingAs($usuario)
            ->post(route('devengados.pagos.store'), [
                'devengado_lote_id' => $lote->id,
                'afiliado_id' => $afiliada->id,
                'clave_idempotencia' => (string) Str::uuid(),
                'monto_total' => '5.00',
                'monto_mensual' => '15.00',
                'metodo_determinacion' => 'MINIMO_VIGENTE',
            ])
            ->assertRedirect(route('devengados.index'));

        $pago = DevengadoPago::query()->sole();
        $this->assertSame(0, $pago->cantidad_periodos);
        $this->assertSame('5.00', $pago->saldo_generado);
        $this->assertDatabaseCount('devengado_pago_detalles', 0);

        $this->patch(route('devengados.submit', $lote));
        $this->actingAs($admin)->get(route('devengados.show', $lote))->assertOk();
        $this->post(route('devengados.confirmar-pagos', $lote), [
            'pago_ids' => [$pago->id],
        ])->assertRedirect(route('lotes.index'));

        $this->assertDatabaseCount('aportes', 0);
        $this->assertDatabaseHas('saldos', [
            'afiliado_id' => $afiliada->id,
            'pago_id' => null,
            'monto' => '5.00',
            'monto_disponible' => '5.00',
        ]);
        $this->assertSame('VERIFICADO', $pago->fresh()->estado);
        $this->assertSame('VERIFICADO', $lote->fresh()->estado);
    }

    public function test_central_confirmation_rechecks_for_a_concurrent_ordinary_aporte(): void
    {
        $colegio = $this->createCollege();
        $usuario = $this->createUser('USUARIO', $colegio);
        $admin = $this->createUser('ADMIN', $colegio);
        $fecha = now()->startOfMonth()->subMonth();
        $afiliada = $this->createAffiliate($colegio, [
            'fecha_afiliacion' => $fecha->copy()->subYear()->toDateString(),
        ]);
        $lote = $this->crearLoteDevengado($colegio, $usuario);
        $this->actingAs($usuario)->post(route('devengados.pagos.store'), [
            'devengado_lote_id' => $lote->id,
            'afiliado_id' => $afiliada->id,
            'clave_idempotencia' => (string) Str::uuid(),
            'monto_total' => '15.00',
            'monto_mensual' => '15.00',
            'metodo_determinacion' => 'MINIMO_VIGENTE',
            'periodos' => [$fecha->format('Y-m')],
        ])->assertRedirect(route('devengados.index'));
        $pago = DevengadoPago::query()->sole();

        $this->actingAs($usuario)->patch(route('devengados.submit', $lote));
        $this->actingAs($admin)->get(route('devengados.show', $lote))->assertOk();
        $existente = $this->createAporte($afiliada, $fecha->month, $fecha->year, '27.00');

        $this->from(route('devengados.show', $lote))
            ->post(route('devengados.confirmar-pagos', $lote), [
                'pago_ids' => [$pago->id],
            ])
            ->assertRedirect(route('devengados.show', $lote))
            ->assertSessionHasErrors('pago_ids');

        $this->assertSame('VERIFICADO', $existente->fresh()->estado);
        $this->assertSame('27.00', $existente->fresh()->monto);
        $this->assertSame('EN_VERIFICACION', $lote->fresh()->estado);
        $this->assertSame('EN_LOTE', $pago->fresh()->estado);
        $this->assertDatabaseCount('aportes', 1);
    }

    public function test_presidential_exoneration_is_audited_and_is_not_a_payment(): void
    {
        $colegio = $this->createCollege();
        $usuario = $this->createUser('USUARIO', $colegio);
        $admin = $this->createUser('ADMIN', $colegio);
        $afiliada = $this->createAffiliate($colegio);
        $fecha = now()->startOfMonth()->subYear();
        $aporte = $this->createAporte($afiliada, $fecha->month, $fecha->year, '50.00');
        $lote = $this->crearLoteDevengado($colegio, $usuario);
        $periodo = $lote->periodos()->create([
            'afiliado_id' => $afiliada->id,
            'mes' => $fecha->month,
            'year' => $fecha->year,
            'monto' => '50.00',
            'metodo_determinacion' => 'MINIMO_VIGENTE',
            'estado' => 'REGISTRADO',
            'aporte_id' => $aporte->id,
        ]);

        $this->actingAs($admin)
            ->post(route('devengados.exoneraciones.store', $periodo), [
                'monto' => '20.00',
                'motivo' => 'Autorización expresa de presidencia.',
                'presidente_nombre' => 'Presidenta autorizante',
                'autorizacion_referencia' => 'Resolución CNEB-2026-01',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('devengado_exoneraciones', [
            'devengado_periodo_id' => $periodo->id,
            'monto' => '20.00',
            'presidente_nombre' => 'Presidenta autorizante',
            'autorizacion_referencia' => 'Resolución CNEB-2026-01',
        ]);
        $this->assertDatabaseCount('devengado_pagos', 0);
        $this->assertSame(
            3000,
            app(DevengadoService::class)->saldoPendiente($periodo->fresh())
        );
        $this->assertDatabaseHas('auditoria', [
            'tabla_afectada' => 'devengado_exoneraciones',
            'accion' => 'EXONERAR',
        ]);
    }

    private function crearLoteDevengado($colegio, Usuario $usuario): DevengadoLote
    {
        return DevengadoLote::create([
            'colegio_id' => $colegio->id,
            'mes' => now()->month,
            'year' => now()->year,
            'codigo_lote' => 'DEV-'.$colegio->codigo.'-'.now()->year.'-'.str_pad((string) now()->month, 2, '0', STR_PAD_LEFT),
            'estado' => 'ABIERTO',
            'creado_por' => $usuario->id,
        ]);
    }

    private function createAporte(
        $afiliada,
        int $mes,
        int $year,
        string $monto,
        string $estado = 'VERIFICADO'
    ): Aporte {
        return Aporte::create([
            'afiliado_id' => $afiliada->id,
            'colegio_id' => $afiliada->colegio_id,
            'pago_id' => null,
            'mes' => $mes,
            'year' => $year,
            'monto' => $monto,
            'estado' => $estado,
            'registrado_por' => Usuario::query()->firstOrFail()->id,
        ]);
    }
}
