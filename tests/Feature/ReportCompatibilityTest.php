<?php

namespace Tests\Feature;

use App\Models\Aporte;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\Concerns\BuildsDomainRecords;
use Tests\TestCase;

class ReportCompatibilityTest extends TestCase
{
    use BuildsDomainRecords;

    public function test_affiliate_report_works_with_the_configured_mysql_test_database(): void
    {
        $admin = $this->createUser('ADMIN');

        $this->actingAs($admin)
            ->get('/reports/afiliados')
            ->assertOk();
    }

    public function test_affiliate_report_paginates_results_and_keeps_search_filters_in_links(): void
    {
        $college = $this->createCollege();
        $admin = $this->createUser('ADMIN', $college);

        foreach (range(1, 51) as $numero) {
            $this->createAffiliate($college, [
                'nombres' => 'Afiliada',
                'apellidos' => sprintf('Registro %03d', $numero),
            ]);
        }

        $response = $this->actingAs($admin)
            ->get('/reports/afiliados?buscar=Afiliada');

        $response->assertOk()
            ->assertViewHas(
                'afiliados',
                fn ($afiliados) => $afiliados instanceof LengthAwarePaginator
                    && $afiliados->perPage() === 50
                    && $afiliados->total() === 51
            )
            ->assertSee('page=2');

        $this->get('/reports/afiliados?buscar=Afiliada&page=2')
            ->assertOk()
            ->assertSee('Registro 051')
            ->assertDontSee('Registro 001');
    }

    public function test_aporte_report_with_selected_year_only_shows_affiliates_with_verified_contributions_that_year(): void
    {
        $college = $this->createCollege();
        $admin = $this->createUser('ADMIN', $college);

        $withSelectedYearContribution = $this->createAffiliate($college, [
            'nombres' => 'Incluida',
            'apellidos' => 'Gestion',
        ]);
        $withOtherYearContribution = $this->createAffiliate($college, [
            'nombres' => 'Otra',
            'apellidos' => 'Gestion',
        ]);
        $this->createAffiliate($college, [
            'nombres' => 'Sin',
            'apellidos' => 'Aportes',
        ]);

        $batch = $this->createBatch($college);
        $paymentInSelectedYear = $this->createPayment([
            'afiliado_id' => $withSelectedYearContribution->id,
            'lote_verificacion_id' => $batch->id,
            'registrado_por' => $admin->id,
        ]);
        Aporte::query()->create([
            'afiliado_id' => $withSelectedYearContribution->id,
            'colegio_id' => $college->id,
            'pago_id' => $paymentInSelectedYear->id,
            'year' => 2025,
            'mes' => 1,
            'monto' => '100.00',
            'estado' => 'VERIFICADO',
            'registrado_por' => $admin->id,
            'fecha_registro' => now(),
        ]);

        $paymentInOtherYear = $this->createPayment([
            'afiliado_id' => $withOtherYearContribution->id,
            'lote_verificacion_id' => $batch->id,
            'registrado_por' => $admin->id,
        ]);
        Aporte::query()->create([
            'afiliado_id' => $withOtherYearContribution->id,
            'colegio_id' => $college->id,
            'pago_id' => $paymentInOtherYear->id,
            'year' => 2024,
            'mes' => 1,
            'monto' => '100.00',
            'estado' => 'VERIFICADO',
            'registrado_por' => $admin->id,
            'fecha_registro' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/reports/aportes?year=2025')
            ->assertOk()
            ->assertViewHas(
                'afiliados',
                fn ($afiliados) => $afiliados instanceof LengthAwarePaginator
                    && $afiliados->total() === 1
            )
            ->assertSee('Gestion, Incluida')
            ->assertDontSee('Gestion, Otra')
            ->assertDontSee('Aportes, Sin');
    }

    public function test_aporte_report_paginates_large_filtered_results_and_keeps_filters_in_links(): void
    {
        $college = $this->createCollege();
        $admin = $this->createUser('ADMIN', $college);
        $batch = $this->createBatch($college);

        foreach (range(1, 51) as $numero) {
            $affiliate = $this->createAffiliate($college, [
                'nombres' => 'Aportes',
                'apellidos' => sprintf('Registro %03d', $numero),
            ]);
            $payment = $this->createPayment([
                'afiliado_id' => $affiliate->id,
                'lote_verificacion_id' => $batch->id,
                'registrado_por' => $admin->id,
            ]);

            Aporte::query()->create([
                'afiliado_id' => $affiliate->id,
                'colegio_id' => $college->id,
                'pago_id' => $payment->id,
                'year' => 2025,
                'mes' => 1,
                'monto' => '35.00',
                'estado' => 'VERIFICADO',
                'registrado_por' => $admin->id,
                'fecha_registro' => now(),
            ]);
        }

        $response = $this->actingAs($admin)
            ->get('/reports/aportes?year=2025');

        $response->assertOk()
            ->assertViewHas(
                'afiliados',
                fn ($afiliados) => $afiliados instanceof LengthAwarePaginator
                    && $afiliados->perPage() === 50
                    && $afiliados->total() === 51
            )
            ->assertSee('page=2');

        $this->get('/reports/aportes?year=2025&page=2')
            ->assertOk()
            ->assertSee('Registro 051')
            ->assertDontSee('Registro 001');
    }
}
