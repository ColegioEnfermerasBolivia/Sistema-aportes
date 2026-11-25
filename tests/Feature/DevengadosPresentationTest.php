<?php

namespace Tests\Feature;

use App\Exports\ReporteDevengadoLoteExport;
use App\Models\DevengadoLote;
use App\Models\LoteVerificacion;
use Illuminate\Support\Facades\Excel;
use Tests\Concerns\BuildsDomainRecords;
use Tests\TestCase;

class DevengadosPresentationTest extends TestCase
{
    use BuildsDomainRecords;

    public function test_active_lot_list_shows_open_ordinary_and_devengado_lots(): void
    {
        $college = $this->createCollege();
        $user = $this->createUser('USUARIO', $college);
        $mesDevengado = now()->month;
        $mesOrdinario = $mesDevengado === 1 ? 2 : 1;

        $this->actingAs($user)
            ->post(route('lotes.store'), [
                'colegio_id' => $college->id,
                'mes' => $mesOrdinario,
            ])
            ->assertRedirect(route('lotes.index'));

        $this->post(route('devengados.store'), [
            'colegio_id' => $college->id,
            'mes' => $mesDevengado,
        ])->assertRedirect(route('lotes.index'));

        $loteOrdinario = LoteVerificacion::query()->firstOrFail();
        $loteDevengado = DevengadoLote::query()->firstOrFail();

        $this->get(route('lotes.index'))
            ->assertOk()
            ->assertSee($loteOrdinario->codigo_lote)
            ->assertSee($loteDevengado->codigo_lote);
    }

    public function test_sent_and_verified_devengado_lots_can_be_downloaded_as_excel(): void
    {
        $college = $this->createCollege();
        $admin = $this->createUser('ADMIN', $college);
        $lotes = collect(['ENVIADO', 'VERIFICADO'])
            ->map(fn (string $estado, int $indice): DevengadoLote => DevengadoLote::create([
                'colegio_id' => $college->id,
                'mes' => $indice + 1,
                'year' => now()->year,
                'codigo_lote' => "DEV-{$college->codigo}-".now()->year.'-'.str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT),
                'estado' => $estado,
                'creado_por' => $admin->id,
            ]));

        Excel::fake();

        $this->actingAs($admin);

        foreach ($lotes as $lote) {
            $filename = "reporte-lote-devengado-{$lote->id}.xlsx";

            $this->get(route('devengados.lotes.excel', $lote))->assertOk();

            Excel::assertDownloaded(
                $filename,
                fn ($export): bool => $export instanceof ReporteDevengadoLoteExport
            );
        }
    }

    public function test_open_and_reviewing_devengado_lots_cannot_be_downloaded_as_excel(): void
    {
        $college = $this->createCollege();
        $admin = $this->createUser('ADMIN', $college);
        $lotes = collect(['ABIERTO', 'EN_VERIFICACION'])
            ->map(fn (string $estado, int $indice): DevengadoLote => DevengadoLote::create([
                'colegio_id' => $college->id,
                'mes' => $indice + 1,
                'year' => now()->year,
                'codigo_lote' => "DEV-{$college->codigo}-".now()->year.'-'.str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT),
                'estado' => $estado,
                'creado_por' => $admin->id,
            ]));

        $this->actingAs($admin);

        foreach ($lotes as $lote) {
            $this->get(route('devengados.lotes.excel', $lote))->assertForbidden();
        }
    }

    public function test_open_lot_actions_are_limited_to_the_college_user(): void
    {
        $college = $this->createCollege();
        $user = $this->createUser('USUARIO', $college);
        $admin = $this->createUser('ADMIN', $college);
        $lote = DevengadoLote::create([
            'colegio_id' => $college->id,
            'mes' => now()->month,
            'year' => now()->year,
            'codigo_lote' => "DEV-{$college->codigo}-".now()->year.'-'.str_pad((string) now()->month, 2, '0', STR_PAD_LEFT),
            'estado' => 'ABIERTO',
            'creado_por' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('devengados.index'))
            ->assertOk()
            ->assertSee('Enviar a revisión')
            ->assertSee('disabled')
            ->assertDontSee(route('devengados.lotes.excel', $lote));

        $this->actingAs($admin)
            ->get(route('devengados.index'))
            ->assertOk()
            ->assertDontSee('Enviar a revisión');

        $this->patch(route('devengados.submit', $lote))->assertForbidden();
        $this->post(route('devengados.confirmar-pagos', $lote), [
            'pago_ids' => [1],
        ])->assertForbidden();
    }
}
