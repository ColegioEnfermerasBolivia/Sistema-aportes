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

class AfiliadosExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnWidths,
    WithEvents,
    WithFreezePane
{
    // Fila donde se insertan los encabezados institucionales
    private const FILAS_ENCABEZADO = 6;

    // Fila donde queda la fila de títulos de columna
    private const FILA_TITULOS_TABLA = self::FILAS_ENCABEZADO + 1; // 7

    // Última columna de la tabla
    private const ULTIMA_COLUMNA = 'H';

    public function __construct(
        private Builder $query,
        private array $informacionReporte = []
    ) {
    }

    public function query(): Builder
    {
        return $this->query
            ->orderBy('apellidos')
            ->orderBy('nombres');
    }

    public function headings(): array
    {
        return [
            'CI',
            'Mat-Prof',
            'Mat-Deptal',
            'Apellidos',
            'Nombres',
            'Fecha afiliación',
            'Colegio',
            'Especialidad',
        ];
    }

    public function map($afiliado): array
    {
        $especialidades = $afiliado->afiliadosPostgrados
            ->map(function ($afiliadoPostgrado) {
                return $afiliadoPostgrado->postgrado?->nombre;
            })
            ->filter()
            ->implode(', ');

        return [
            $afiliado->ci,
            $afiliado->matricula_profesional,
            $afiliado->registro_departamental,
            $afiliado->apellidos,
            $afiliado->nombres,
            $afiliado->fecha_afiliacion?->format('d/m/Y'),
            $afiliado->colegio?->nombre,
            $especialidades ?: 'Sin especialidad',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Encabezados de la tabla
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
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '1F3F5C'],
                    ],
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14, // CI
            'B' => 14, // Matrícula profesional
            'C' => 14, // Matrícula departamental
            'D' => 26, // Apellidos
            'E' => 26, // Nombres
            'F' => 18, // Fecha de afiliación
            'G' => 32, // Colegio
            'H' => 45, // Especialidad
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $ultimaColumna = self::ULTIMA_COLUMNA;

                // ── Espacio para el encabezado institucional ─────────────
                $sheet->insertNewRowBefore(
                    1,
                    self::FILAS_ENCABEZADO
                );

                // ── Logo institucional ──────────────────────────────────
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

                // ── Título y subtítulo ───────────────────────────────────
                $sheet->setCellValue(
                    'A1',
                    'COLEGIO DE ENFERMERAS DE BOLIVIA'
                );

                $sheet->setCellValue(
                    'A2',
                    'INFORME DE AFILIADAS'
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
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 13,
                        'color' => ['rgb' => '2E5B8A'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getRowDimension(2)->setRowHeight(22);

                // ── Información del reporte ──────────────────────────────
                $sheet->setCellValue(
                    'A4',
                    'Generado por: '
                    . ($this->informacionReporte['usuario']
                        ?? 'No disponible')
                );

                $sheet->setCellValue(
                    'A5',
                    'Fecha de generación: '
                    . ($this->informacionReporte['fecha']
                        ?? '')
                );

                $sheet->setCellValue(
                    'A6',
                    'Año de afiliación: '
                    . ($this->informacionReporte['anio'] ?? 'Todos')
                    . ' | Colegio: '
                    . ($this->informacionReporte['colegio'] ?? 'Todos')
                    . ' | Especialidad: '
                    . ($this->informacionReporte['especialidad'] ?? 'Todas')
                    . ' | Situación laboral: '
                    . ($this->informacionReporte['trabaja'] ?? 'Todas')
                    . ' | Búsqueda: '
                    . ($this->informacionReporte['buscar']
                        ?? 'Sin búsqueda')
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
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                // ── Autofiltro ────────────────────────────────────────────
                $filaTitulos = self::FILA_TITULOS_TABLA;

                $sheet->setAutoFilter(
                    "A{$filaTitulos}:{$ultimaColumna}{$filaTitulos}"
                );

                $sheet->getRowDimension(
                    $filaTitulos
                )->setRowHeight(22);

                // ── Bordes y bandas alternas ──────────────────────────────
                $primeraFilaDatos = $filaTitulos + 1;
                $ultimaFila = $sheet->getHighestRow();

                if ($ultimaFila >= $primeraFilaDatos) {

                    $rangoDatos =
                        "A{$primeraFilaDatos}:{$ultimaColumna}{$ultimaFila}";

                    $sheet->getStyle($rangoDatos)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'D9D9D9'],
                            ],
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'wrapText' => true,
                        ],
                    ]);

                    // Bandas alternas
                    for (
                        $fila = $primeraFilaDatos;
                        $fila <= $ultimaFila;
                        $fila++
                    ) {

                        if (
                            ($fila - $primeraFilaDatos) % 2 === 1
                        ) {
                            $sheet->getStyle(
                                "A{$fila}:{$ultimaColumna}{$fila}"
                            )->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => [
                                        'rgb' => 'F2F6FA',
                                    ],
                                ],
                            ]);
                        }

                        $sheet->getRowDimension($fila)
                            ->setRowHeight(18);
                    }

                    // ── Alineación por columna ────────────────────────────

                    // CI
                    $sheet->getStyle(
                        "A{$primeraFilaDatos}:A{$ultimaFila}"
                    )->getAlignment()
                        ->setHorizontal(
                            Alignment::HORIZONTAL_CENTER
                        );

                    // Matrículas
                    $sheet->getStyle(
                        "B{$primeraFilaDatos}:C{$ultimaFila}"
                    )->getAlignment()
                        ->setHorizontal(
                            Alignment::HORIZONTAL_CENTER
                        );

                    // Fecha
                    $sheet->getStyle(
                        "F{$primeraFilaDatos}:F{$ultimaFila}"
                    )->getAlignment()
                        ->setHorizontal(
                            Alignment::HORIZONTAL_CENTER
                        );

                    // Apellidos y nombres
                    $sheet->getStyle(
                        "D{$primeraFilaDatos}:E{$ultimaFila}"
                    )->getAlignment()
                        ->setHorizontal(
                            Alignment::HORIZONTAL_LEFT
                        );

                    // Colegio y especialidad
                    $sheet->getStyle(
                        "G{$primeraFilaDatos}:H{$ultimaFila}"
                    )->getAlignment()
                        ->setHorizontal(
                            Alignment::HORIZONTAL_LEFT
                        );

                    // CI como texto para no perder ceros a la izquierda
                    $sheet->getStyle(
                        "A{$primeraFilaDatos}:A{$ultimaFila}"
                    )->getNumberFormat()
                        ->setFormatCode('@');

                    // Matrículas como texto
                    $sheet->getStyle(
                        "B{$primeraFilaDatos}:C{$ultimaFila}"
                    )->getNumberFormat()
                        ->setFormatCode('@');
                }

                // ── Configuración de página ──────────────────────────────
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

                // Repetir títulos en cada página impresa
                $pageSetup->setRowsToRepeatAtTopByStartAndEnd(
                    $filaTitulos,
                    $filaTitulos
                );

                // Área de impresión
                $pageSetup->setPrintArea(
                    "A1:{$ultimaColumna}{$ultimaFila}"
                );

                // Márgenes
                $sheet->getPageMargins()
                    ->setTop(0.5)
                    ->setBottom(0.5)
                    ->setLeft(0.4)
                    ->setRight(0.4)
                    ->setHeader(0.2)
                    ->setFooter(0.2);
            },
        ];
    }

    public function freezePane(): string
    {
        return 'A' . (self::FILA_TITULOS_TABLA + 1); // A8
    }
}