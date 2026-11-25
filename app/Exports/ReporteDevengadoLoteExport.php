<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReporteDevengadoLoteExport implements FromArray, WithColumnWidths, WithHeadings
{
    /**
     * @param array<int, array{
     *     codigo_lote: string,
     *     colegio: string,
     *     periodo_lote: string,
     *     estado_lote: string,
     *     afiliada: string,
     *     ci: string,
     *     fecha_pago: ?string,
     *     monto_total: float,
     *     monto_mensual: float,
     *     periodos: string,
     *     saldo_generado: float,
     *     estado_pago: string,
     *     observaciones: ?string
     * }> $filas
     */
    public function __construct(private array $filas) {}

    public function array(): array
    {
        return array_map(
            fn (array $fila): array => array_values($fila),
            $this->filas
        );
    }

    public function headings(): array
    {
        return [
            'Código del lote',
            'Colegio',
            'Período del lote',
            'Estado del lote',
            'Afiliada',
            'CI',
            'Fecha del pago',
            'Importe total (Bs)',
            'Aporte mensual (Bs)',
            'Períodos cubiertos',
            'Saldo generado (Bs)',
            'Estado del pago',
            'Observaciones',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 28,
            'B' => 30,
            'C' => 18,
            'D' => 20,
            'E' => 36,
            'F' => 16,
            'G' => 20,
            'H' => 20,
            'I' => 22,
            'J' => 40,
            'K' => 22,
            'L' => 20,
            'M' => 40,
        ];
    }
}
