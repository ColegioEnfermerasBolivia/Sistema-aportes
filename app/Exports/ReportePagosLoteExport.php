<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class ReportePagosLoteExport implements FromArray, WithColumnWidths, WithEvents
{
    private const FILA_ENCABEZADOS_TABLA = 7;

    private const ULTIMA_COLUMNA = 'P';

    private array $rangosFilasPagos = [];

    /**
     * @param array{
     *     colegio: string,
     *     codigo_lote: string,
     *     fecha_generacion: string,
     *     usuario_generador: string,
     *     filas: array<int, array{
     *         aportante: string,
     *         ci: string,
     *         gestiones: array<int, array<int, float>>,
     *         saldo_disponible: float
     *     }>
     * } $reporte
     */
    public function __construct(private array $reporte) {}

    private const MESES = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    public function array(): array
    {
        $filas = [
            ['COLEGIO DE ENFERMERAS DE BOLIVIA'],
            ['REPORTE DETALLADO DE PAGOS POR LOTE'],
            ["Colegio: {$this->reporte['colegio']}"],
            ["Código del lote: {$this->reporte['codigo_lote']}"],
            ["Fecha de generación: {$this->reporte['fecha_generacion']}"],
            ["Usuario que genera el reporte: {$this->reporte['usuario_generador']}"],
            [
                'Nombre Completo',
                'CI',
                'Gestión',
                ...array_values(self::MESES),
                'Saldo',
            ],
        ];

        $this->rangosFilasPagos = [];
        $numeroFilaExcel = self::FILA_ENCABEZADOS_TABLA + 1;

        foreach ($this->reporte['filas'] as $fila) {
            $gestiones = $fila['gestiones'];
            ksort($gestiones);
            $primeraFilaPago = $numeroFilaExcel;

            foreach ($gestiones as $gestion => $aportes) {
                $datosFila = array_fill(0, 16, null);
                $datosFila[2] = $gestion;

                foreach (array_keys(self::MESES) as $mes) {
                    $datosFila[$mes + 2] = $aportes[$mes] ?? null;
                }

                if ($numeroFilaExcel === $primeraFilaPago) {
                    $datosFila[0] = $fila['aportante'];
                    $datosFila[1] = $fila['ci'];
                    $datosFila[15] = $fila['saldo_disponible'];
                }

                $filas[] = $datosFila;
                $numeroFilaExcel++;
            }

            $this->rangosFilasPagos[] = [
                'inicio' => $primeraFilaPago,
                'fin' => $numeroFilaExcel - 1,
            ];
        }

        return $filas;
    }

    public function columnWidths(): array
    {
        $anchos = [
            'A' => 30,
            'B' => 15,
            'C' => 10,
        ];

        foreach (range('D', 'O') as $columna) {
            $anchos[$columna] = 12;
        }

        $anchos[self::ULTIMA_COLUMNA] = 10;

        return $anchos;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $ultimaFila = max(
                    self::FILA_ENCABEZADOS_TABLA,
                    self::FILA_ENCABEZADOS_TABLA
                        + array_sum(array_map(
                            fn (array $rango): int => $rango['fin'] - $rango['inicio'] + 1,
                            $this->rangosFilasPagos
                        ))
                );

                foreach (range(1, 6) as $fila) {
                    $sheet->mergeCells('A'.$fila.':'.self::ULTIMA_COLUMNA.$fila);
                }

                $sheet->getStyle('A1:'.self::ULTIMA_COLUMNA.'6')->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                        'color' => ['rgb' => '0D1B6E'],
                    ],
                ]);
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 13,
                        'color' => ['rgb' => '1A34C9'],
                    ],
                ]);
                $sheet->getStyle('A3:'.self::ULTIMA_COLUMNA.'6')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                ]);
                $sheet->getStyle('A7:'.self::ULTIMA_COLUMNA.$ultimaFila)
                    ->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => '9CA3AF'],
                            ],
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_TOP,
                            'wrapText' => true,
                        ],
                    ]);
                $sheet->getStyle('A7:'.self::ULTIMA_COLUMNA.'7')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '2E5B8A'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                foreach ($this->rangosFilasPagos as $rango) {
                    if ($rango['inicio'] === $rango['fin']) {
                        continue;
                    }

                    foreach (['A', 'B', self::ULTIMA_COLUMNA] as $columna) {
                        $sheet->mergeCells(
                            "{$columna}{$rango['inicio']}:{$columna}{$rango['fin']}"
                        );
                    }
                }

                if ($ultimaFila > self::FILA_ENCABEZADOS_TABLA) {
                    $sheet->getStyle("D8:O{$ultimaFila}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                    $sheet->getStyle("P8:P{$ultimaFila}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                    $sheet->getStyle("A8:P{$ultimaFila}")
                        ->getAlignment()
                        ->setVertical(Alignment::VERTICAL_CENTER)
                        ->setWrapText(true);
                }

                $sheet->getRowDimension(1)->setRowHeight(24);
                $sheet->getRowDimension(2)->setRowHeight(24);
                $sheet->getRowDimension(7)->setRowHeight(24);

                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
                $sheet->getPageSetup()->setFitToPage(true);
                $sheet->getPageMargins()
                    ->setTop(0.5)
                    ->setRight(0.3)
                    ->setBottom(0.5)
                    ->setLeft(0.3);
                $sheet->setShowGridlines(false);
            },
        ];
    }
}
