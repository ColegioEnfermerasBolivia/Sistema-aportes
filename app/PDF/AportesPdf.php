<?php

namespace App\PDF;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class AportesPdf
{
    public static function generar(
        Collection $afiliados,
        ?int $year,
        array $informacionReporte = [],
        ?object $afiliada = null
    ) {
        $meses = [
            1 => 'ENERO',
            2 => 'FEBRERO',
            3 => 'MARZO',
            4 => 'ABRIL',
            5 => 'MAYO',
            6 => 'JUNIO',
            7 => 'JULIO',
            8 => 'AGOSTO',
            9 => 'SEPTIEMBRE',
            10 => 'OCTUBRE',
            11 => 'NOVIEMBRE',
            12 => 'DICIEMBRE',
        ];

        /*
        |--------------------------------------------------------------------------
        | CI + Todos los años
        |--------------------------------------------------------------------------
        */

        if ($afiliada && $year === null) {

            $aportesPorGestion = $afiliada->aportes
                ->where('estado', 'VERIFICADO')
                ->groupBy('year')
                ->map(function ($aportes) {
                    return $aportes->keyBy('mes');
                })
                ->sortKeysDesc();

            $pdf = Pdf::loadView(
                'reports.aportesReports.pdf',
                [
                    'modo' => 'afiliada_todos',
                    'afiliada' => $afiliada,
                    'aportesPorGestion' => $aportesPorGestion,
                    'afiliados' => collect(),
                    'year' => null,
                    'meses' => $meses,
                    'informacionReporte' => $informacionReporte,
                ]
            );

        } else {
             $pdf = Pdf::loadView(
                'reports.aportesReports.pdf',
                [
                    'modo' => 'general',
                    'afiliada' => null,
                    'aportesPorGestion' => collect(),
                    'afiliados' => $afiliados,
                    'year' => $year,
                    'meses' => $meses,
                    'informacionReporte' => $informacionReporte,
                ]
            );
        }
        $pdf->setPaper('letter', 'landscape');

        $domPdf = $pdf->getDomPDF();
        $canvas = $domPdf->getCanvas();

        $canvas->page_text(
            730,
            565,
            'Pagina {PAGE_NUM} DE {PAGE_COUNT}',
            'Helvetica',
            7,
            [0.45, 0.45, 0.45]
        );
        return $pdf;            
    }
}