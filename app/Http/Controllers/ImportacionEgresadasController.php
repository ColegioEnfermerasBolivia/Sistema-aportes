<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportacionEgresadasRequest;
use App\Models\Egresada;
use App\Models\EgresadaFormacion;
use App\Services\ImportacionEgresadasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ImportacionEgresadasController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize('import', Egresada::class);

        return response()->json([
            'columnas_requeridas' => [
                'ci',
                'nombres',
                'apellidos',
                'universidad',
                'departamento',
                'carrera',
            ],
            'columnas_opcionales' => [
                'celular',
                'fecha_egreso',
                'estado_titulacion',
                'numero_titulo',
            ],
            'valores_estado_titulacion' => EgresadaFormacion::ESTADOS_TITULACION,
            'separadores' => [',', ';'],
            'departamento' => 'Se acepta el código o nombre del departamento.',
            'universidad' => 'Debe coincidir con una universidad registrada.',
            'identificacion' => 'Filas con el mismo CI se agrupan como una egresada; cada fila aporta una formación.',
        ]);
    }

    public function store(
        ImportacionEgresadasRequest $request,
        ImportacionEgresadasService $service
    ): JsonResponse|RedirectResponse {
        Gate::authorize('import', Egresada::class);

        $resultado = $service->importar(
            $request->file('archivo')->getRealPath(),
            (int) $request->user()->id
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'La importación se procesó correctamente.',
                'data' => $resultado,
            ]);
        }

        return redirect()
            ->route('egresadas.index')
            ->with(
                'success',
                "Importación completada: {$resultado['egresadas_creadas']} egresadas y "
                    ."{$resultado['formaciones_importadas']} formaciones registradas; "
                    ."{$resultado['formaciones_duplicadas']} duplicadas omitidas."
            );
    }
}
