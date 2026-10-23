<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithFreezePane;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AportesExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnWidths,
    WithEvents,
    WithFreezePane
{
    private const FILAS_ENCABEZADO = 6;

    private const FILA_TITULOS_TABLA =
        self::FILAS_ENCABEZADO + 1; // 7

    private const ULTIMA_COLUMNA = 'P';

    private int $numeroFila = 0;

    private array $meses = [
        1 => 'ENE',
        2 => 'FEB',
        3 => 'MAR',
        4 => 'ABR',
        5 => 'MAY',
        6 => 'JUN',
        7 => 'JUL',
        8 => 'AGO',
        9 => 'SEP',
        10 => 'OCT',
        11 => 'NOV',
        12 => 'DIC',
    ];

    public function __construct(
        private $query,
        private ?int $year,
        private $afiliada = null,
        private array $informacionReporte = []
    ) {
    }

    /*Consulta normal*/

    public function query(): Builder
    {
        return $this->query
            ->with([
                'colegio',
                'aportes' => function ($query) {
                    $query
                        ->where('estado', 'VERIFICADO')
                        ->when(
                            $this->year !== null,
                            function ($query) {
                                $query->where(
                                    'year',
                                    $this->year
                                );
                            }
                        )
                        ->orderBy('year')
                        ->orderBy('mes');
                },
            ])
            ->orderBy('apellidos')
            ->orderBy('nombres');
    }

    /*Encabezados de la tabla normal */

    public function headings(): array
    {
        return [
            'N.º',
            'Afiliada',
            'CI',
            'ENE',
            'FEB',
            'MAR',
            'ABR',
            'MAY',
            'JUN',
            'JUL',
            'AGO',
            'SEP',
            'OCT',
            'NOV',
            'DIC',
        ];
    }

    /*Mapeo de la tabla normal*/

    public function map($afiliado): array
    {
        $aportes = $afiliado->aportes->keyBy('mes');

        $this->numeroFila++;

        $fila = [
            $this->numeroFila,
            $afiliado->apellidos . ', ' . $afiliado->nombres,
            $afiliado->ci,
        ];

        foreach ($this->meses as $numeroMes => $nombreMes) {

            $aporte = $aportes->get($numeroMes);

            $fila[] = $aporte
                ? number_format(
                    (float) $aporte->monto,
                    2,
                    '.',
                    ''
                )
                : '—';
        }

        return $fila;
    }

    /*Estilos de encabezado normal*/

    public function styles(Worksheet $sheet): array
    {
        return [
            self::FILA_TITULOS_TABLA => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],

                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2E5B8A'],
                ],

                'alignment' => [
                    'horizontal' =>
                        Alignment::HORIZONTAL_CENTER,

                    'vertical' =>
                        Alignment::VERTICAL_CENTER,
                ],

                'borders' => [
                    'allBorders' => [
                        'borderStyle' =>
                            Border::BORDER_THIN,

                        'color' => [
                            'rgb' => '1F3F5C',
                        ],
                    ],
                ],
            ],
        ];
    }

    /*Encabezado institucional*/

    private function generarEncabezadoInstitucional(
        Worksheet $sheet
    ): void {

        $ultimaColumna = self::ULTIMA_COLUMNA;

        /*Espacio para encabezado*/

        $sheet->insertNewRowBefore(
            1,
            self::FILAS_ENCABEZADO
        );

        /*Logo*/

        $logo = new Drawing();

        $logo->setName(
            'Logo Colegio de Enfermeras de Bolivia'
        );

        $logo->setDescription(
            'Logo institucional'
        );

        $logo->setPath(
            public_path('images/logo.png')
        );

        $logo->setHeight(80);
        $logo->setCoordinates('A1');
        $logo->setOffsetX(8);
        $logo->setOffsetY(8);
        $logo->setWorksheet($sheet);

        /*Títulos*/

        $sheet->setCellValue(
            'A1',
            'COLEGIO DE ENFERMERAS DE BOLIVIA'
        );

        $sheet->setCellValue(
            'A2',
            'INFORME DE APORTES'
        );

        $sheet->mergeCells(
            "A1:{$ultimaColumna}1"
        );

        $sheet->mergeCells(
            "A2:{$ultimaColumna}2"
        );

        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => '1F3F5C'],
            ],

            'alignment' => [
                'horizontal' =>
                    Alignment::HORIZONTAL_CENTER,

                'vertical' =>
                    Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 13,
                'color' => ['rgb' => '2E5B8A'],
            ],

            'alignment' => [
                'horizontal' =>
                    Alignment::HORIZONTAL_CENTER,

                'vertical' =>
                    Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension(1)
            ->setRowHeight(28);

        $sheet->getRowDimension(2)
            ->setRowHeight(22);

        /*Información del reporte*/

        $sheet->setCellValue(
            'A4',
            'Generado por: '
            . (
                $this->informacionReporte['usuario']
                ?? 'No disponible'
            )
        );

        $sheet->setCellValue(
            'A5',
            'Fecha de generación: '
            . (
                $this->informacionReporte['fecha']
                ?? ''
            )
        );

        $sheet->setCellValue(
            'A6',
            'Gestión: '
            . (
                $this->informacionReporte['year']
                ?? (
                    $this->year
                    ?? 'Todos los años'
                )
            )
            . ' | Colegio: '
            . (
                $this->informacionReporte['colegio']
                ?? 'Todos'
            )
            . ' | Búsqueda: '
            . (
                $this->informacionReporte['buscar']
                ?? 'Sin búsqueda'
            )
        );

        $sheet->mergeCells(
            "A4:{$ultimaColumna}4"
        );

        $sheet->mergeCells(
            "A5:{$ultimaColumna}5"
        );

        $sheet->mergeCells(
            "A6:{$ultimaColumna}6"
        );

        $sheet->getStyle(
            "A4:{$ultimaColumna}6"
        )->applyFromArray([
            'font' => [
                'size' => 10,
                'italic' => true,
                'color' => ['rgb' => '555555'],
            ],

            'alignment' => [
                'horizontal' =>
                    Alignment::HORIZONTAL_CENTER,
            ],
        ]);
    }

    /*Excel: CI + Todos los años*/

    private function generarTodosLosAnios(
        Worksheet $sheet
    ): void {

        $afiliada = $this->afiliada;

        /*Limpiar la tabla generada automáticamente por FromQuery*/

        $ultimaFilaInicial =
            $sheet->getHighestRow();

        if ($ultimaFilaInicial > 0) {
            $sheet->removeRow(
                1,
                $ultimaFilaInicial
            );
        }

        /*Encabezado institucional*/

        $this->generarEncabezadoInstitucional($sheet);

        /*Aportes verificados agrupados por gestión*/

        $aportesPorGestion = $afiliada->aportes
            ->where('estado', 'VERIFICADO')
            ->groupBy('year')
            ->map(function ($aportes) {
                return $aportes->keyBy('mes');
            })
            ->sortKeysDesc();

        /*Información de la afiliada*/

        $fila = 8;

        $sheet->mergeCells(
            "A{$fila}:P{$fila}"
        );

        $sheet->setCellValue(
            "A{$fila}",
            'AFILIADA'
        );

        $sheet->getStyle(
            "A{$fila}:P{$fila}"
        )->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF'],
            ],

            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2E5B8A'],
            ],

            'alignment' => [
                'horizontal' =>
                    Alignment::HORIZONTAL_LEFT,

                'vertical' =>
                    Alignment::VERTICAL_CENTER,
            ],
        ]);

        $fila++;

        $sheet->mergeCells(
            "A{$fila}:P{$fila}"
        );

        $sheet->setCellValue(
            "A{$fila}",
            $afiliada->apellidos
            . ', '
            . $afiliada->nombres
        );

        $sheet->getStyle(
            "A{$fila}:P{$fila}"
        )->getFont()
            ->setBold(true)
            ->setSize(12);

        $fila++;

        $sheet->setCellValue(
            "A{$fila}",
            'CI: ' . $afiliada->ci
        );

        $fila++;

        $sheet->setCellValue(
            "A{$fila}",
            'Colegio: '
            . (
                $afiliada->colegio->nombre
                ?? 'Sin colegio'
            )
        );

        $fila += 2;

        /*Si no existen aportes verificados*/

        if ($aportesPorGestion->isEmpty()) {

            $sheet->mergeCells(
                "A{$fila}:P{$fila}"
            );

            $sheet->setCellValue(
                "A{$fila}",
                'No registra aportes verificados.'
            );

            $sheet->getStyle(
                "A{$fila}:P{$fila}"
            )->getFont()
                ->setItalic(true);

            $this->configurarPaginaTodosLosAnios(
                $sheet
            );

            return;
        }

        /*Una tabla por gestión*/

        foreach (
            $aportesPorGestion as $year => $aportes
        ) {

            /*Título de la gestión*/

            $sheet->mergeCells(
                "A{$fila}:P{$fila}"
            );

            $sheet->setCellValue(
                "A{$fila}",
                "APORTES DEL AÑO: {$year}"
            );

            $sheet->getStyle(
                "A{$fila}:P{$fila}"
            )->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => '1F3F5C'],
                ],

                'alignment' => [
                    'horizontal' =>
                        Alignment::HORIZONTAL_CENTER,

                    'vertical' =>
                        Alignment::VERTICAL_CENTER,
                ],
            ]);

            $fila++;

            /*Encabezados*/

            $encabezados = [
                'N.º',
                'Afiliada',
                'CI',
                'ENE',
                'FEB',
                'MAR',
                'ABR',
                'MAY',
                'JUN',
                'JUL',
                'AGO',
                'SEP',
                'OCT',
                'NOV',
                'DIC',
            ];

            $sheet->fromArray(
                $encabezados,
                null,
                "A{$fila}"
            );

            $filaEncabezado = $fila;

            /*Estilo encabezado*/

            $sheet->getStyle(
                "A{$filaEncabezado}:P{$filaEncabezado}"
            )->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],

                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2E5B8A'],
                ],

                'alignment' => [
                    'horizontal' =>
                        Alignment::HORIZONTAL_CENTER,

                    'vertical' =>
                        Alignment::VERTICAL_CENTER,
                ],

                'borders' => [
                    'allBorders' => [
                        'borderStyle' =>
                            Border::BORDER_THIN,

                        'color' => [
                            'rgb' => '1F3F5C',
                        ],
                    ],
                ],
            ]);

            $fila++;

            /*Datos*/

            $filaDatos = [
                1,
                $afiliada->apellidos
                    . ', '
                    . $afiliada->nombres,

                $afiliada->ci,
            ];

            foreach ($this->meses as $numeroMes => $nombreMes) {

                $aporte = $aportes->get($numeroMes);

                $filaDatos[] = $aporte
                    ? number_format(
                        (float) $aporte->monto,
                        2,
                        '.',
                        ''
                    )
                    : '—';
            }

            $sheet->fromArray(
                $filaDatos,
                null,
                "A{$fila}"
            );

            /*Estilo datos*/

            $sheet->getStyle(
                "A{$fila}:P{$fila}"
            )->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' =>
                            Border::BORDER_THIN,

                        'color' => [
                            'rgb' => 'D9D9D9',
                        ],
                    ],
                ],

                'alignment' => [
                    'vertical' =>
                        Alignment::VERTICAL_CENTER,

                    'wrapText' => true,
                ],
            ]);

            $sheet->getStyle(
                "A{$fila}"
            )->getAlignment()
                ->setHorizontal(
                    Alignment::HORIZONTAL_CENTER
                );

            $sheet->getStyle(
                "C{$fila}"
            )->getAlignment()
                ->setHorizontal(
                    Alignment::HORIZONTAL_CENTER
                );

            $sheet->getStyle(
                "C{$fila}"
            )->getNumberFormat()
                ->setFormatCode('@');

            $sheet->getStyle(
                "D{$fila}:P{$fila}"
            )->getAlignment()
                ->setHorizontal(
                    Alignment::HORIZONTAL_CENTER
                );

            $sheet->getRowDimension($fila)
                ->setRowHeight(20);

            /*Espacio antes de la siguiente gestión*/

            $fila += 3;
        }

        $this->configurarPaginaTodosLosAnios(
            $sheet
        );
    }

    /*Configuración de página para Todos los años*/

    private function configurarPaginaTodosLosAnios(
        Worksheet $sheet
    ): void {

        $ultimaColumna = self::ULTIMA_COLUMNA;
        $ultimaFila = $sheet->getHighestRow();

        /*Anchos*/

        $sheet->getColumnDimension('A')
            ->setWidth(8);

        $sheet->getColumnDimension('B')
            ->setWidth(34);

        $sheet->getColumnDimension('C')
            ->setWidth(16);

        foreach (range('D', 'P') as $columna) {
            $sheet->getColumnDimension($columna)
                ->setWidth(12);
        }

        /*Página*/

        $pageSetup = $sheet->getPageSetup();

        $pageSetup->setOrientation(
            PageSetup::ORIENTATION_LANDSCAPE
        );

        $pageSetup->setPaperSize(
            PageSetup::PAPERSIZE_LETTER
        );

        $pageSetup->setFitToPage(true);
        $pageSetup->setFitToWidth(1);
        $pageSetup->setFitToHeight(0);

        $pageSetup->setHorizontalCentered(true);
        $pageSetup->setVerticalCentered(false);

        $sheet->getPageMargins()
            ->setTop(0.5)
            ->setBottom(0.5)
            ->setLeft(0.4)
            ->setRight(0.4)
            ->setHeader(0.2)
            ->setFooter(0.2);

        /*Área de impresión*/

        $sheet->getPageSetup()
            ->setPrintArea(
                "A1:{$ultimaColumna}{$ultimaFila}"
            );

        /*Congelar encabezado institucional*/

        $sheet->freezePane('A9');
    }

    /*Anchos de columna*/

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 34,
            'C' => 16,

            'D' => 12,
            'E' => 12,
            'F' => 12,
            'G' => 12,
            'H' => 12,
            'I' => 12,
            'J' => 12,
            'K' => 12,
            'L' => 12,
            'M' => 12,
            'N' => 12,
            'O' => 12,
            'P' => 12,
        ];
    }

    /*Eventos*/

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (
                AfterSheet $event
            ) {

                $sheet =
                    $event->sheet->getDelegate();

                /*
                |--------------------------------------------------------------------------
                | CASO ESPECIAL:
                | CI + Todos los años
                |--------------------------------------------------------------------------
                */

                if (
                    $this->afiliada
                    && $this->year === null
                ) {

                    $this->generarTodosLosAnios(
                        $sheet
                    );

                    return;
                }

                /*INFORME NORMAL*/

                $ultimaColumna =
                    self::ULTIMA_COLUMNA;

                /*Encabezado institucional*/

                $this->generarEncabezadoInstitucional(
                    $sheet
                );

                /*Autofiltro*/

                $filaTitulos =
                    self::FILA_TITULOS_TABLA;

                $sheet->setAutoFilter(
                    "A{$filaTitulos}:{$ultimaColumna}{$filaTitulos}"
                );

                $sheet->getRowDimension(
                    $filaTitulos
                )->setRowHeight(22);

                /*Bordes y bandas*/

                $primeraFilaDatos =
                    $filaTitulos + 1;

                $ultimaFila =
                    $sheet->getHighestRow();

                if (
                    $ultimaFila >=
                    $primeraFilaDatos
                ) {

                    $rangoDatos =
                        "A{$primeraFilaDatos}:{$ultimaColumna}{$ultimaFila}";

                    $sheet->getStyle(
                        $rangoDatos
                    )->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' =>
                                    Border::BORDER_THIN,

                                'color' => [
                                    'rgb' => 'D9D9D9',
                                ],
                            ],
                        ],

                        'alignment' => [
                            'vertical' =>
                                Alignment::VERTICAL_CENTER,

                            'wrapText' => true,
                        ],
                    ]);

                    /*Bandas alternas*/

                    for (
                        $fila = $primeraFilaDatos;
                        $fila <= $ultimaFila;
                        $fila++
                    ) {

                        if (
                            (
                                $fila -
                                $primeraFilaDatos
                            ) % 2 === 1
                        ) {

                            $sheet->getStyle(
                                "A{$fila}:{$ultimaColumna}{$fila}"
                            )->applyFromArray([
                                'fill' => [
                                    'fillType' =>
                                        Fill::FILL_SOLID,

                                    'startColor' => [
                                        'rgb' => 'F2F6FA',
                                    ],
                                ],
                            ]);
                        }

                        $sheet->getRowDimension(
                            $fila
                        )->setRowHeight(18);
                    }

                    /*N.º*/

                    $sheet->getStyle(
                        "A{$primeraFilaDatos}:A{$ultimaFila}"
                    )->getAlignment()
                        ->setHorizontal(
                            Alignment::HORIZONTAL_CENTER
                        );

                    /*CI*/

                    $sheet->getStyle(
                        "C{$primeraFilaDatos}:C{$ultimaFila}"
                    )->getAlignment()
                        ->setHorizontal(
                            Alignment::HORIZONTAL_CENTER
                        );

                    $sheet->getStyle(
                        "C{$primeraFilaDatos}:C{$ultimaFila}"
                    )->getNumberFormat()
                        ->setFormatCode('@');

                    /*Montos*/

                    $sheet->getStyle(
                        "D{$primeraFilaDatos}:P{$ultimaFila}"
                    )->getAlignment()
                        ->setHorizontal(
                            Alignment::HORIZONTAL_CENTER
                        );
                }

                /*Configuración de página*/

                $pageSetup =
                    $sheet->getPageSetup();

                $pageSetup->setOrientation(
                    PageSetup::ORIENTATION_LANDSCAPE
                );

                $pageSetup->setPaperSize(
                    PageSetup::PAPERSIZE_LETTER
                );

                $pageSetup->setFitToPage(true);
                $pageSetup->setFitToWidth(1);
                $pageSetup->setFitToHeight(0);

                $pageSetup->setHorizontalCentered(true);
                $pageSetup->setVerticalCentered(false);

                $pageSetup->setRowsToRepeatAtTopByStartAndEnd(
                    $filaTitulos,
                    $filaTitulos
                );

                $sheet->getPageMargins()
                    ->setTop(0.5)
                    ->setBottom(0.5)
                    ->setLeft(0.4)
                    ->setRight(0.4)
                    ->setHeader(0.2)
                    ->setFooter(0.2);

                $sheet->getPageSetup()
                    ->setPrintArea(
                        "A1:{$ultimaColumna}{$ultimaFila}"
                    );
            },
        ];
    }

    /*Freeze pane*/

    public function freezePane(): string
    {
        return 'A' .
            (self::FILA_TITULOS_TABLA + 1);
    }
}