<?php

namespace App\Http\Controllers;

use App\Exports\AfiliadosExport;
use App\Exports\AportesExport;
use App\PDF\AportesPdf;
use App\Models\Afiliado;
use App\Models\Postgrado;
use App\Models\Aporte;
use App\Models\Colegio;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;


class InformeController extends Controller
{
    public function afiliados(Request $request): View{
        Gate::authorize('viewAny', Afiliado::class);
        $usuario = auth()->user();

        /* Consulta base */
        $query = $this->consultaAfiliados(($request));

        $totalAfiliados = (clone $query)->count();

        $afiliados = $query
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(50)
            ->withQueryString();

        /* Años de afiliacion */
        $aniosAfiliacion = Afiliado::query()
            ->whereNotNull('fecha_afiliacion')
            ->selectRaw('YEAR(fecha_afiliacion) as anio')
            ->distinct()
            ->orderByDesc('anio')
            ->pluck('anio');

        /* Especialidades */
        $especialidades = Postgrado::where('estado', 'ACTIVO')
            ->orderBy('nombre')
            ->get();

        /* Colegios */
        $colegios = collect();

        if (in_array($usuario->rol?->nombre, ['ADMIN', 'SUPER ADMIN'])) {
            $colegios = Colegio::where('estado', 'ACTIVO')
                ->orderBy('nombre')
                ->get();
        }

        return view(
            'reports.afiliadosReports.index',
            compact(
                'afiliados',
                'totalAfiliados',
                'aniosAfiliacion',
                'especialidades',
                'colegios'
            )
        );
    }

    private function consultaAfiliados(Request $request, bool $cargarPostgrados = true)
    {
        $usuario = auth()->user();

        $query = Afiliado::query()
            ->with('colegio');
        
        if ($cargarPostgrados) {
            $query->with('afiliadosPostgrados.postgrado');
        }

        /*Restricción por usuario*/

        if ($usuario->rol?->nombre === 'USUARIO') {
            $colegio = $usuario->colegio;

            if (!$colegio) {
                abort(403);
            }

            if ($colegio->tipo === 'DEPARTAMENTAL') {
                $colegiosPermitidos = $colegio->hijos
                    ->pluck('id')
                    ->push($colegio->id);

                $query->whereIn('colegio_id', $colegiosPermitidos);
            } else {
                $query->where('colegio_id', $colegio->id);
            }
        }

        /*Buscar*/
        if ($request->filled('buscar')) {
            $buscar = trim($request->input('buscar'));

            $query->where(function ($q) use ($buscar) {
                $q->where('ci', 'like', "%{$buscar}%")
                    ->orWhere('nombres', 'like', "%{$buscar}%")
                    ->orWhere('apellidos', 'like', "%{$buscar}%");
            });
        }

        /*Año de afiliación*/
        if ($request->filled('year_afiliacion')) {
            $query->whereYear(
                'fecha_afiliacion',
                $request->input('year_afiliacion')
            );
        }

        /*Trabaja actualmente*/
        if ($request->filled('trabaja')) {
            if ($request->input('trabaja') === '1') {
                $query->whereHas('informacionesLaborales', function ($q) {
                    $q->where('culminado', false);
                });
            }

            if ($request->input('trabaja') === '0') {
                $query->whereDoesntHave('informacionesLaborales', function ($q) {
                    $q->where('culminado', false);
                });
            }
        }

        /*Especialidad*/
        if ($request->filled('especialidad_id')) {
            $query->whereHas('afiliadosPostgrados', function ($q) use ($request) {
                $q->where(
                    'postgrado_id',
                    $request->input('especialidad_id')
                );
            });
        }

        /*Colegio*/
        if (
            $request->filled('colegio_id') &&
            in_array($usuario->rol?->nombre, ['ADMIN', 'SUPER ADMIN'])
        ) {
            $query->where(
                'colegio_id',
                $request->input('colegio_id')
            );
        }
        return $query;
    }

    /* Exportar Afiliados a Excel */
    public function afiliadosExcel(Request $request)
    {
        Gate::authorize('viewAny', Afiliado::class);

        $query = $this->consultaAfiliados($request);
        $usuario = auth()->user();

        // Obtener nombre del colegio seleccionado
        $colegio = null;

        if ($request->filled('colegio_id')) {
            $colegio = Colegio::find($request->input('colegio_id'));
        }

        if (!$colegio && $usuario->rol?->nombre === 'USUARIO') {
            $colegio = $usuario->colegio;
        }

        // Obtener nombre de la especialidad seleccionada
        $especialidad = null;

        if ($request->filled('especialidad_id')) {
            $especialidad = Postgrado::find(
                $request->input('especialidad_id')
            );
        }

        // Traducir situación laboral
        $trabaja = match ($request->input('trabaja')) {
            '1' => 'Trabaja',
            '0' => 'No trabaja',
            default => 'Todas',
        };

        $informacionReporte = [
            'usuario' => $usuario->username,

            'fecha' => now()->format('d/m/Y H:i'),

            'anio' => $request->input('year_afiliacion')
                ?: 'Todos',

            'colegio' => $colegio->nombre
                ?? 'Todos',

            'especialidad' => $especialidad->nombre
                ?? 'Todas',

            'trabaja' => $trabaja,

            'buscar' => $request->filled('buscar')
                ? $request->input('buscar')
                : 'Sin búsqueda',
        ];

        return Excel::download(
            new AfiliadosExport(
                $query,
                $informacionReporte
            ),
            'informe-afiliadas.xlsx'
        );
    }

/*Exportar informe de afiliadas a PDF.*/
    public function afiliadosPdf(Request $request)
    {
        Gate::authorize('viewAny', Afiliado::class);

        /*
        * Construir la consulta respetando:
        * - permisos
        * - colegio
        * - búsqueda
        * - año de afiliación
        * - trabaja
        * - especialidad
        * - demás filtros definidos en consultaAfiliados()
        */
        $query = $this->consultaAfiliados($request)
            ->orderBy('apellidos')
            ->orderBy('nombres');

        /*
        * Verificar cantidad antes de cargar los registros
        * y antes de iniciar DomPDF.
        */
        $totalAfiliados = (clone $query)->count();

        if ($totalAfiliados > 1000) {
            return redirect()
                ->back()
                ->with(
                    'error',
                    "El informe contiene {$totalAfiliados} afiliados. " .
                    "Para informes de más de 1.000 afiliados, " .
                    "utilice la exportación a Excel."
                );
        }

        /*
        * Ahora sí obtenemos los registros.
        *
        * Como ya sabemos que hay como máximo 1.000,
        * el consumo de memoria queda limitado.
        */
        $afiliados = $query->get();

        $usuario = auth()->user();

        /*
        * Colegio mostrado en el encabezado.
        */
        $colegio = null;

        if ($request->filled('colegio_id')) {
            $colegio = Colegio::find(
                $request->input('colegio_id')
            );
        }

        /*
        * Si es USUARIO, su reporte pertenece a su colegio.
        */
        if (
            !$colegio &&
            $usuario->rol?->nombre === 'USUARIO'
        ) {
            $colegio = $usuario->colegio;
        }

        /*
        * Información del reporte.
        */
        $informacionReporte = [
            'usuario' => $usuario->username,
            'fecha' => now()->format('d/m/Y H:i'),
            'colegio' => $colegio->nombre ?? 'Todos los colegios',
        ];

        /*
        * Generar PDF.
        */
        $pdf = Pdf::loadView(
            'reports.afiliadosReports.pdf',
            compact(
                'afiliados',
                'informacionReporte'
            )
        );

        /*
        * Configuración de página.
        */
        $pdf->setPaper(
            'letter',
            'landscape'
        );

        /*
        * Numeración de páginas.
        */
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

        /*
        * Descargar PDF.
        */
        return $pdf->download(
            'informe-afiliadas.pdf'
        );
    }

    //APORTES
    private function consultaAportes(Request $request){
        $usuario = auth()->user();
        $query = Afiliado::with([
            'colegio',
            'aportes' => function ($query) {
                $query
                    ->where('estado', 'VERIFICADO')
                    ->orderBy('year')
                    ->orderBy('mes');
            },
        ]);

        //Restriccion por colegio para usuario
        if ($usuario->rol?->nombre === 'USUARIO') {
            $colegio = $usuario->colegio;

            if (!$colegio) {
                abort(403);
            }

            if ($colegio->tipo === 'DEPARTAMENTAL') {
                $colegiosPermitidos = $colegio->hijos
                    ->pluck('id')
                    ->push($colegio->id);
                
                $query->whereIn('colegio_id', $colegiosPermitidos);
            } else {
                $query->where('colegio_id', $colegio->id);
            }
        }

        //Busqueda por CI
        if ($request->filled('buscar')) {
            $buscar = trim($request->input('buscar'));

            $query->where('ci', $buscar);
            
        }

        //Filtro por Colegio
        if ($request->filled('colegio_id') && in_array($usuario->rol?->nombre, ['ADMIN', 'SUPER ADMIN'])) {
            $query->where('colegio_id', $request->input('colegio_id'));
        }

        //Orden
        return $query
            ->orderBy('apellidos')
            ->orderBy('nombres');
    }

    public function aportes(Request $request) {
        Gate::authorize('viewAny', Afiliado::class);

        $datos = $request->validate([
            'buscar' => [
                'nullable',
                'string',
                'max:100',
            ],
            'year' => [
                'nullable',
                'integer',
                'min:2000',
                'max:2100',
            ],
            'colegio_id' => [
                'nullable',
                'integer',
                'exists:colegios,id',
            ],
        ]);

        $query = $this->consultaAportes($request);

        /* Cantidad total de afiliados según filtros */
        $totalAfiliados = (clone $query)->count();

        /* Obtener afiliados */
        $afiliados = $query->get();

        $afiliados->each(function ($afiliado) {
            $afiliado->aportesPorGestion = $afiliado->aportes
                ->groupBy('year')
                ->map(function ($aportes){
                    return $aportes->keyBy('mes');
                });
        });

        //Gestion Seleccionada
        $year = $datos['year'] ?? null;

        //Colegios Disponibles
        $usuario = auth()->user();
        $colegios = collect();

        if (in_array($usuario->rol?->nombre, ['SUPER ADMIN', 'ADMIN'])) {
            $colegios = Colegio::where('estado', 'ACTIVO')
                ->orderBy('nombre')
                ->get();
        }

        //Gestiones disponibles
        $gestiones = Aporte::query()
            ->where('estado', 'VERIFICADO')
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        if (!$gestiones->contains(now()->year)) {
            $gestiones->prepend(now()->year);
        }

        $gestionVisual = $year ?? now()->year;

        //Meses
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

        return view(
            'reports.aportesReports.index',
            compact(
                'afiliados',
                'totalAfiliados',
                'colegios',
                'gestiones',
                'year',
                'gestionVisual',
                'meses'
            )
        );
    }

    public function aportesExcel(Request $request)
    {
        Gate::authorize('viewAny', Afiliado::class);

        $query = $this->consultaAportes($request);

        $usuario = auth()->user();

        $year = $request->filled('year')
            ? (int) $request->input('year')
            : null;

        $colegio = null;

        if ($request->filled('colegio_id')) {
            $colegio = Colegio::find(
                $request->input('colegio_id')
            );
        }

        if (
            !$colegio &&
            $usuario->rol?->nombre === 'USUARIO'
        ) {
            $colegio = $usuario->colegio;
        }

        /*Si se busca por CI*/

        $afiliada = null;

        if ($request->filled('buscar')) {
            $afiliada = (clone $query)
                ->first();
        }

        /*Información del reporte*/

        $informacionReporte = [
            'usuario' => $usuario->username,
            'fecha' => now()->format('d/m/Y H:i'),
            'year' => $year ?? 'Todos los años',
            'colegio' => $colegio->nombre
                ?? 'Todos los colegios',
            'buscar' => $request->filled('buscar')
                ? $request->input('buscar')
                : 'Sin búsqueda',
        ];

        /*Exportar*/

        return Excel::download(
            new AportesExport(
                $query,
                $year,
                $afiliada,
                $informacionReporte
            ),
            'informe-aportes.xlsx'
        );
    }

    /* Aportes en PDF */
    public function aportesPdf(Request $request)
    {
        Gate::authorize('viewAny', Afiliado::class);
        $query = $this->consultaAportes($request);
        $usuario = auth()->user();
        $year = $request->filled('year')
            ? (int) $request->input('year')
            : null;
        $colegio = null;

        if ($request->filled('colegio_id')) {
            $colegio = Colegio::find(
                $request->input('colegio_id')
            );
        }

         $totalAfiliados = (clone $query)->count();

        if ($totalAfiliados > 1000) {
            return redirect()
                ->back()
                ->with(
                    'error',
                    "El informe contiene {$totalAfiliados} afiliados. " .
                    "Para informes de más de 1.000 afiliados, " .
                    "utilice la exportación a Excel."
                );
        }

        if (
            !$colegio &&
            $usuario->rol?->nombre === 'USUARIO'
        ) {
            $colegio = $usuario->colegio;
        }

        /*Afiliada buscada por CI*/

        $afiliada = null;

        if ($request->filled('buscar')) {
            $afiliada = (clone $query)->first();
        }

        /*Año para el informe general*/

        $yearParaReporte = $year ?? now()->year;

        /*Información del reporte*/

        $informacionReporte = [
            'usuario' => $usuario->username,

            'fecha' => now()->format(
                'd/m/Y H:i'
            ),

            'year' => $year ?? 'Todos los años',

            'colegio' => $colegio->nombre
                ?? 'Todos los colegios',

            'buscar' => $request->filled('buscar')
                ? $request->input('buscar')
                : 'Sin búsqueda',
        ];     

        /*Obtener afiliados*/

        $afiliados = $query->get();
        /*
        |--------------------------------------------------------------------------
        | Si es informe general, necesitamos solamente
        | los aportes de la gestión seleccionada.
        |--------------------------------------------------------------------------
        */
        if (!$afiliada) {

            $afiliados->each(function ($afiliado) use ($yearParaReporte) {

                $afiliado->aportesDelYear =
                    $afiliado->aportes
                        ->where('year', $yearParaReporte)
                        ->keyBy('mes');
            });
        }

        /*Generar PDF*/

        return AportesPdf::generar(
            $afiliados,
            $year,
            $informacionReporte,
            $afiliada
        )->download(
            'informe-aportes.pdf'
        );
    }
}
