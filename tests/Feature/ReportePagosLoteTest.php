<?php

namespace Tests\Feature;

use App\Exports\ReportePagosLoteExport;
use App\Models\Saldo;
use App\Services\LotePagoReporteService;
use Database\Factories\AporteFactory;
use Database\Factories\PagoFactory;
use Tests\Concerns\BuildsDomainRecords;
use Tests\TestCase;

class ReportePagosLoteTest extends TestCase
{
    use BuildsDomainRecords;

    public function test_preview_and_excel_share_the_payment_report_data(): void
    {
        $colegio = $this->createCollege(['nombre' => 'Colegio de La Paz']);
        $usuario = $this->createUser('ADMIN', $colegio, [
            'nombres' => 'Ana',
            'apellidos' => 'Reporte',
        ]);
        $afiliado = $this->createAffiliate($colegio, [
            'nombres' => 'María',
            'apellidos' => 'Flores',
            'ci' => '1234567',
        ]);
        $lote = $this->createBatch($colegio, [
            'estado' => 'ENVIADO',
            'codigo_lote' => 'LP-2026-03',
        ]);
        $pago = PagoFactory::new()->create([
            'afiliado_id' => $afiliado->id,
            'lote_verificacion_id' => $lote->id,
            'registrado_por' => $usuario->id,
        ]);
        $afiliadoSinAportes = $this->createAffiliate($colegio, [
            'nombres' => 'Afiliada',
            'apellidos' => 'SinAportes',
        ]);
        PagoFactory::new()->create([
            'afiliado_id' => $afiliadoSinAportes->id,
            'lote_verificacion_id' => $lote->id,
            'registrado_por' => $usuario->id,
        ]);

        foreach ([
            [2025, 10],
            [2025, 11],
            [2026, 1],
            [2026, 2],
            [2026, 3],
        ] as [$year, $mes]) {
            AporteFactory::new()->create([
                'afiliado_id' => $afiliado->id,
                'colegio_id' => $colegio->id,
                'pago_id' => $pago->id,
                'registrado_por' => $usuario->id,
                'year' => $year,
                'mes' => $mes,
            ]);
        }

        Saldo::query()->create([
            'afiliado_id' => $afiliado->id,
            'pago_id' => $pago->id,
            'monto' => '25.50',
            'monto_disponible' => '25.50',
            'estado' => 'ACTIVO',
        ]);

        $this->actingAs($usuario)
            ->get(route('lotesReports.preview', $lote))
            ->assertOk()
            ->assertSee('REPORTE DETALLADO DE PAGOS POR LOTE')
            ->assertSee('Colegio de La Paz')
            ->assertSee('María Flores')
            ->assertSee('1234567')
            ->assertDontSee('Afiliada SinAportes')
            ->assertSee('Gestión')
            ->assertSee('Octubre')
            ->assertSee('Diciembre')
            ->assertSee('2025')
            ->assertSee('2026')
            ->assertSee('rowspan="2"', false)
            ->assertSee('Bs 25,50');

        $reporte = app(LotePagoReporteService::class)->construir(
            $lote,
            $usuario
        );
        $filasExcel = (new ReportePagosLoteExport($reporte))->array();

        $this->assertCount(16, $filasExcel[6]);
        $this->assertSame('Gestión', $filasExcel[6][2]);
        $this->assertSame('Enero', $filasExcel[6][3]);
        $this->assertSame('Diciembre', $filasExcel[6][14]);
        $this->assertCount(2, \array_slice($filasExcel, 7));
        $this->assertSame('María Flores', $filasExcel[7][0]);
        $this->assertSame(2025, $filasExcel[7][2]);
        $this->assertSame(100.0, $filasExcel[7][12]);
        $this->assertSame(100.0, $filasExcel[7][13]);
        $this->assertSame(2026, $filasExcel[8][2]);
        $this->assertSame(100.0, $filasExcel[8][3]);
        $this->assertSame(100.0, $filasExcel[8][4]);
        $this->assertSame(100.0, $filasExcel[8][5]);
        $this->assertNull($filasExcel[8][0]);
        $this->assertNull($filasExcel[8][1]);
        $this->assertNull($filasExcel[8][15]);

        $this->get(route('lotesReports.excel', $lote))
            ->assertDownload("reporte-pagos-lote-{$lote->id}.xlsx");
    }

    public function test_user_can_only_list_own_college_batches(): void
    {
        $colegioUsuario = $this->createCollege();
        $usuario = $this->createUser('USUARIO', $colegioUsuario);
        $lotePermitido = $this->createBatch($colegioUsuario, [
            'codigo_lote' => 'LOTE-PROPIO',
            'estado' => 'ENVIADO',
            'year' => 2025,
        ]);
        $loteAjeno = $this->createBatch(null, [
            'codigo_lote' => 'LOTE-AJENO',
            'estado' => 'ENVIADO',
        ]);
        $loteOtraGestion = $this->createBatch($colegioUsuario, [
            'codigo_lote' => 'LOTE-PROPIO-ANTERIOR',
            'estado' => 'ENVIADO',
            'year' => 2024,
        ]);

        $this->actingAs($usuario)
            ->get(route('lotesReports.index'))
            ->assertOk()
            ->assertSee($lotePermitido->codigo_lote)
            ->assertDontSee($loteAjeno->codigo_lote);

        $this->get(route('lotesReports.index', [
            'codigo_lote' => 'LOTE-PROPIO',
            'gestion' => 2025,
        ]))
            ->assertOk()
            ->assertSee($lotePermitido->codigo_lote)
            ->assertDontSee($loteOtraGestion->codigo_lote)
            ->assertDontSee($loteAjeno->codigo_lote);

        $admin = $this->createUser('ADMIN');

        $this->actingAs($admin)
            ->get(route('lotesReports.index'))
            ->assertOk()
            ->assertSee($lotePermitido->codigo_lote)
            ->assertSee($loteAjeno->codigo_lote);
    }

    public function test_departmental_user_can_filter_child_college_batches(): void
    {
        $parent = $this->createCollege([
            'nombre' => 'Colegio Padre',
            'tipo' => 'DEPARTAMENTAL',
        ]);
        $child = $this->createCollege([
            'nombre' => 'Colegio Hijo',
            'colegio_padre_id' => $parent->id,
        ]);
        $unrelated = $this->createCollege(['nombre' => 'Colegio Ajeno']);
        $usuario = $this->createUser('USUARIO', $parent);
        $lotePadre = $this->createBatch($parent, [
            'codigo_lote' => 'LOTE-PADRE',
            'estado' => 'ENVIADO',
        ]);
        $loteHijo = $this->createBatch($child, [
            'codigo_lote' => 'LOTE-HIJO',
            'estado' => 'ENVIADO',
        ]);
        $loteAjeno = $this->createBatch($unrelated, [
            'codigo_lote' => 'LOTE-AJENO',
            'estado' => 'ENVIADO',
        ]);

        $this->actingAs($usuario)
            ->get(route('lotesReports.index'))
            ->assertOk()
            ->assertSee($lotePadre->codigo_lote)
            ->assertSee($loteHijo->codigo_lote)
            ->assertDontSee($loteAjeno->codigo_lote)
            ->assertSee('Colegio Hijo');

        $this->get(route('lotesReports.index', [
            'colegio_id' => $child->id,
        ]))
            ->assertOk()
            ->assertSee($loteHijo->codigo_lote)
            ->assertDontSee($lotePadre->codigo_lote)
            ->assertDontSee($loteAjeno->codigo_lote);

        $this->get(route('lotesReports.preview', $loteHijo))
            ->assertOk()
            ->assertSee('Colegio Hijo');

        $this->from(route('lotesReports.index'))
            ->get(route('lotesReports.index', [
                'colegio_id' => $unrelated->id,
            ]))
            ->assertRedirect(route('lotesReports.index'))
            ->assertSessionHasErrors('colegio_id');
    }

    public function test_open_or_other_college_batches_cannot_be_reported_by_user(): void
    {
        $colegioUsuario = $this->createCollege();
        $usuario = $this->createUser('USUARIO', $colegioUsuario);
        $loteAbierto = $this->createBatch($colegioUsuario, [
            'estado' => 'ABIERTO',
        ]);
        $loteAjeno = $this->createBatch(null, [
            'estado' => 'ENVIADO',
        ]);

        $this->actingAs($usuario)
            ->get(route('lotesReports.preview', $loteAbierto))
            ->assertForbidden();

        $this->get(route('lotesReports.excel', $loteAbierto))
            ->assertForbidden();

        $this->get(route('lotesReports.preview', $loteAjeno))
            ->assertForbidden();

        $this->get(route('lotesReports.excel', $loteAjeno))
            ->assertForbidden();
    }
}
