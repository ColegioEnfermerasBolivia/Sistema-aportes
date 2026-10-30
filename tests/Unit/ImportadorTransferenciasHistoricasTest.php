<?php

namespace Tests\Unit;

use App\Services\ImportadorTransferenciasHistoricas;
use Database\Factories\TransferenciaAfiliadoFactory;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Concerns\BuildsDomainRecords;
use Tests\TestCase;

class ImportadorTransferenciasHistoricasTest extends TestCase
{
    use BuildsDomainRecords;

    public function test_it_normalizes_utf8_bom_case_ci_and_transfer_date(): void
    {
        Storage::fake('local');
        $origin = $this->createCollege(['nombre' => 'Cochabamba']);
        $destination = $this->createCollege(['nombre' => 'La Paz']);
        $this->createAffiliate($origin, ['ci' => 'AB12']);
        $path = $this->storeCsv(
            "\xEF\xBB\xBFci,colegio_origen,colegio_destino,fecha_transferencia\n"
            .'ab 12,cochabamba,la paz,29/02/2024'
        );

        $result = (new ImportadorTransferenciasHistoricas())->analizar($path);

        $this->assertSame(1, $result['total']);
        $this->assertSame(1, $result['listas']);
        $this->assertSame(0, $result['errores']);
        $this->assertSame(
            $origin->id,
            $result['filas'][0]['colegio_id_origen']
        );
        $this->assertSame(
            $destination->id,
            $result['filas'][0]['colegio_id_destino']
        );
        $this->assertSame(
            '2024-02-29',
            $result['filas'][0]['fecha_transferencia']
        );
    }

    public function test_it_marks_duplicate_rows_in_the_same_csv(): void
    {
        Storage::fake('local');
        $this->createCollege(['nombre' => 'Origen']);
        $this->createCollege(['nombre' => 'Destino']);
        $this->createAffiliate(null, ['ci' => '12345678']);
        $row = '12345678,Origen,Destino,01/02/2024';
        $path = $this->storeCsv(
            "ci,colegio_origen,colegio_destino,fecha_transferencia\n"
            .$row."\n".$row
        );

        $result = (new ImportadorTransferenciasHistoricas())->analizar($path);

        $this->assertSame(1, $result['listas']);
        $this->assertSame(1, $result['errores']);
        $this->assertSame('ERROR', $result['filas'][1]['estado']);
        $this->assertContains(
            'Esta transferencia está repetida en el CSV.',
            $result['filas'][1]['errores']
        );
    }

    public function test_it_rejects_csv_missing_required_headers(): void
    {
        Storage::fake('local');
        $path = $this->storeCsv("ci,fecha_transferencia\n12345678,01/02/2024");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Faltan columnas requeridas:');

        (new ImportadorTransferenciasHistoricas())->analizar($path);
    }

    public function test_it_detects_transfer_already_in_the_database(): void
    {
        Storage::fake('local');
        $origin = $this->createCollege(['nombre' => 'Origen']);
        $destination = $this->createCollege(['nombre' => 'Destino']);
        $affiliate = $this->createAffiliate($origin, ['ci' => '12345678']);

        TransferenciaAfiliadoFactory::new()->create([
            'afiliado_id' => $affiliate->id,
            'colegio_id_origen' => $origin->id,
            'colegio_id_destino' => $destination->id,
            'fecha_transferencia' => '2024-02-01',
        ]);
        $path = $this->storeCsv(
            "ci,colegio_origen,colegio_destino,fecha_transferencia\n"
            .'12345678,Origen,Destino,01/02/2024'
        );

        $result = (new ImportadorTransferenciasHistoricas())->analizar($path);

        $this->assertSame(0, $result['listas']);
        $this->assertSame(1, $result['errores']);
        $this->assertContains(
            'Esta transferencia ya existe en el sistema.',
            $result['filas'][0]['errores']
        );
    }

    private function storeCsv(string $contents): string
    {
        $relativePath = 'tests/'.uniqid('transferencias-', true).'.csv';
        Storage::disk('local')->put($relativePath, $contents);

        return Storage::disk('local')->path($relativePath);
    }
}
