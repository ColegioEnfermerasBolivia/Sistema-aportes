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