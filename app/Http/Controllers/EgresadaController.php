<?php

namespace App\Http\Controllers;

use App\Exports\EgresadasExport;
use App\Http\Requests\ConvertirEgresadaRequest;
use App\Http\Requests\EgresadaRequest;
use App\Models\Colegio;
use App\Models\Departamento;
use App\Models\Egresada;
use App\Models\EgresadaFormacion;
use App\Models\Universidad;
use App\Services\ConvertirEgresadaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class EgresadaController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        Gate::authorize('viewAny', Egresada::class);

        $filtros = $this->validarFiltros($request);
        $query = $this->consultaEgresadas($filtros);

        $porPagina = $request->expectsJson() ? 50 : 20;
        $egresadas = $query->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate($porPagina)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($egresadas);
        }

        $departamentos = Departamento::query()
            ->orderBy('nombre')
            ->get();
        $universidades = Universidad::query()
            ->orderBy('nombre')
            ->get();
        $usuario = $request->user();
        $colegiosConvertibles = $usuario->rol?->nombre === 'USUARIO'
            ? Colegio::query()
                ->whereKey($usuario->colegio_id)
                ->where('estado', 'ACTIVO')
                ->orderBy('nombre')
                ->get()
            : Colegio::query()
                ->where('estado', 'ACTIVO')
                ->orderBy('nombre')
                ->get();
        $departamentosOperables = $departamentos
            ->filter(fn (Departamento $departamento): bool =>
                Gate::allows(
                    'createFormation',
                    [Egresada::class, (int) $departamento->id]
                )
            )
            ->values();

        return view('egresadas.index', compact(
            'egresadas',
            'departamentos',
            'departamentosOperables',
            'universidades',
            'colegiosConvertibles',
            'filtros',
        ));
    }

    public function exportarExcel(Request $request)
    {
        Gate::authorize('viewAny', Egresada::class);

        $filtros = $this->validarFiltros($request);

        return Excel::download(
            new EgresadasExport($this->consultaEgresadas($filtros)),
            'padron-egresadas-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function convertir(
        ConvertirEgresadaRequest $request,
        Egresada $egresada,
        ConvertirEgresadaService $servicio
    ): JsonResponse|RedirectResponse {
        $afiliado = $servicio->convertir(
            $egresada,
            $request->validated(),
            $request->user()
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => $afiliado], 201);
        }

        return redirect()
            ->back()
            ->with('success', 'La egresada fue convertida en afiliada correctamente.');
    }

    public function edit(Egresada $egresada): JsonResponse
    {
        Gate::authorize('update', $egresada);

        $egresada->load('formaciones.universidad', 'formaciones.departamento');
        $formacionesEditables = $egresada->formaciones
            ->filter(fn (EgresadaFormacion $formacion): bool =>
                Gate::allows('updateFormation', $formacion)
            )
            ->values();

        return response()->json([
            'data' => [
                'id' => $egresada->id,
                'ci' => $egresada->ci,
                'nombres' => $egresada->nombres,
                'apellidos' => $egresada->apellidos,
                'celular' => $egresada->celular,
                'formaciones' => $formacionesEditables,
            ],
        ]);
    }

    public function show(Egresada $egresada): JsonResponse
    {
        Gate::authorize('view', $egresada);

        return response()->json([
            'data' => $egresada->load(
                'formaciones.universidad',
                'formaciones.departamento',
                'registradoPor',
                'actualizadoPor',
                'afiliado.colegio',
                'convertidaPor',
                'colegioAsignado'
            ),
        ]);
    }

    public function store(
        EgresadaRequest $request
    ): JsonResponse|RedirectResponse
    {
        $datos = $request->validated();
        $usuarioId = (int) $request->user()->id;

        $egresada = DB::transaction(function () use (
            $datos,
            $usuarioId
        ): Egresada {
            $egresada = Egresada::create([
                'ci' => trim($datos['ci']),
                'nombres' => trim($datos['nombres']),
                'apellidos' => trim($datos['apellidos']),
                'celular' => $datos['celular'] ?? null,
                'registrado_por' => $usuarioId,
            ]);

            $this->guardarFormaciones(
                $egresada,
                $datos['formaciones'],
                $usuarioId
            );

            return $egresada->load(
                'formaciones.universidad',
                'formaciones.departamento'
            );
        });

        if ($request->expectsJson()) {
            return response()->json(['data' => $egresada], 201);
        }

        return redirect()
            ->route('egresadas.index')
            ->with('success', 'Egresada registrada correctamente.');
    }

    public function update(
        EgresadaRequest $request,
        Egresada $egresada
    ): JsonResponse|RedirectResponse {
        Gate::authorize('update', $egresada);

        $datos = $request->validated();
        $usuarioId = (int) $request->user()->id;

        $egresada = DB::transaction(function () use (
            $datos,
            $egresada,
            $usuarioId
        ): Egresada {
            $egresada = Egresada::query()
                ->lockForUpdate()
                ->findOrFail($egresada->id);
            Gate::authorize('update', $egresada);

            $egresada->update([
                'ci' => trim($datos['ci']),
                'nombres' => trim($datos['nombres']),
                'apellidos' => trim($datos['apellidos']),
                'celular' => $datos['celular'] ?? null,
                'actualizado_por' => $usuarioId,
            ]);

            if (
                array_key_exists('formaciones_present', $datos)
                || array_key_exists('formaciones', $datos)
            ) {
                $formaciones = $datos['formaciones'] ?? [];
                $this->eliminarFormacionesOmitidas(
                    $egresada,
                    $formaciones
                );
                if ($formaciones !== []) {
                    $this->guardarFormaciones(
                        $egresada,
                        $formaciones,
                        $usuarioId
                    );
                }
            }

            return $egresada->load(
                'formaciones.universidad',
                'formaciones.departamento'
            );
        });

        if ($request->expectsJson()) {
            return response()->json(['data' => $egresada]);
        }

        return redirect()
            ->route('egresadas.index')
            ->with('success', 'Egresada actualizada correctamente.');
    }

    private function guardarFormaciones(
        Egresada $egresada,
        array $formaciones,
        int $usuarioId
    ): void {
        $claves = [];

        foreach ($formaciones as $datos) {
            $formacion = null;

            if (isset($datos['id'])) {
                $formacion = EgresadaFormacion::query()
                    ->where('egresada_id', $egresada->id)
                    ->findOrFail($datos['id']);
                Gate::authorize('updateFormation', $formacion);
            }

            Gate::authorize(
                'createFormation',
                [Egresada::class, (int) $datos['departamento_id']]
            );

            $clave = implode('|', [
                $datos['universidad_id'],
                $datos['departamento_id'],
                $this->normalizarCarrera($datos['carrera']),
            ]);

            if (isset($claves[$clave])) {
                throw ValidationException::withMessages([
                    'formaciones' => 'No se puede registrar dos veces la misma carrera, universidad y departamento.',
                ]);
            }
            $claves[$clave] = true;

            $duplicada = EgresadaFormacion::query()
                ->where('egresada_id', $egresada->id)
                ->where('universidad_id', $datos['universidad_id'])
                ->where('departamento_id', $datos['departamento_id'])
                ->when(
                    $formacion,
                    fn (Builder $query) => $query->where(
                        'id',
                        '!=',
                        $formacion->id
                    )
                )
                ->get(['id', 'carrera'])
                ->contains(fn (EgresadaFormacion $existente): bool =>
                    $this->normalizarCarrera($existente->carrera)
                        === $this->normalizarCarrera($datos['carrera'])
                );

            if ($duplicada) {
                throw ValidationException::withMessages([
                    'formaciones' => 'La egresada ya tiene registrada esa carrera para la universidad y departamento indicados.',
                ]);
            }

            $atributos = [
                'universidad_id' => $datos['universidad_id'],
                'departamento_id' => $datos['departamento_id'],
                'carrera' => trim($datos['carrera']),
                'fecha_egreso' => $datos['fecha_egreso'] ?? null,
                'estado_titulacion' => $datos['estado_titulacion']
                    ?? 'EGRESADA',
                'numero_titulo' => $datos['numero_titulo'] ?? null,
            ];

            if ($formacion) {
                $formacion->update($atributos + [
                    'actualizado_por' => $usuarioId,
                ]);
            } else {
                $egresada->formaciones()->create($atributos + [
                    'registrado_por' => $usuarioId,
                ]);
            }
        }
    }

    private function normalizarCarrera(string $carrera): string
    {
        return Str::upper(
            Str::ascii(
                preg_replace('/\s+/u', ' ', trim($carrera)) ?? ''
            )
        );
    }

    private function eliminarFormacionesOmitidas(
        Egresada $egresada,
        array $formacionesEnviadas
    ): void {
        $idsEnviados = collect($formacionesEnviadas)
            ->pluck('id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->all();

        $egresada->formaciones()
            ->get()
            ->each(function (EgresadaFormacion $formacion) use (
                $idsEnviados
            ): void {
                if (
                    !in_array((int) $formacion->id, $idsEnviados, true)
                    && Gate::allows('updateFormation', $formacion)
                ) {
                    $formacion->delete();
                }
            });
    }

    private function validarFiltros(Request $request): array
    {
        return $request->validate([
            'ci' => ['nullable', 'string', 'max:30'],
            'buscar' => ['nullable', 'string', 'max:200'],
            'universidad_id' => [
                'nullable',
                'integer',
                Rule::exists('universidades', 'id'),
            ],
            'departamento_id' => [
                'nullable',
                'integer',
                Rule::exists('departamentos', 'id'),
            ],
            'carrera' => ['nullable', 'string', 'max:200'],
            'anio_egreso' => [
                'nullable',
                'integer',
                'min:1900',
                'max:'.now()->year,
            ],
            'estado_titulacion' => [
                'nullable',
                Rule::in(EgresadaFormacion::ESTADOS_TITULACION),
            ],
            'estado_incorporacion' => [
                'nullable',
                Rule::in(['PENDIENTE', 'AFILIADA']),
            ],
        ]);
    }

    private function consultaEgresadas(array $filtros): Builder
    {
        $query = Egresada::query()
            ->with([
                'formaciones.universidad',
                'formaciones.departamento',
                'afiliado.colegio',
            ]);

        if (!empty($filtros['ci'])) {
            $query->where('ci', 'like', '%'.trim($filtros['ci']).'%');
        }

        if (!empty($filtros['buscar'])) {
            $terminos = preg_split(
                '/\s+/',
                trim($filtros['buscar']),
                -1,
                PREG_SPLIT_NO_EMPTY
            ) ?: [];

            foreach ($terminos as $termino) {
                $query->where(function (Builder $coincidencias) use (
                    $termino
                ): void {
                    $patron = "%{$termino}%";
                    $coincidencias
                        ->where('nombres', 'like', $patron)
                        ->orWhere('apellidos', 'like', $patron);
                });
            }
        }

        if (($filtros['estado_incorporacion'] ?? null) === 'AFILIADA') {
            $query->where(function (Builder $estado): void {
                $estado->whereHas('afiliado')
                    ->orWhere('estado', 'AFILIADA');
            });
        } elseif (($filtros['estado_incorporacion'] ?? null) === 'PENDIENTE') {
            $query->whereDoesntHave('afiliado')
                ->where('estado', '!=', 'AFILIADA');
        }

        $tieneFiltroFormacion = collect([
            'universidad_id',
            'departamento_id',
            'carrera',
            'anio_egreso',
            'estado_titulacion',
        ])->contains(
            fn (string $campo): bool => !empty($filtros[$campo])
        );

        if ($tieneFiltroFormacion) {
            $query->whereHas('formaciones', function (Builder $formaciones) use (
                $filtros
            ): void {
                if (!empty($filtros['universidad_id'])) {
                    $formaciones->where(
                        'universidad_id',
                        $filtros['universidad_id']
                    );
                }

                if (!empty($filtros['departamento_id'])) {
                    $formaciones->where(
                        'departamento_id',
                        $filtros['departamento_id']
                    );
                }

                if (!empty($filtros['carrera'])) {
                    $formaciones->where(
                        'carrera',
                        'like',
                        '%'.trim($filtros['carrera']).'%'
                    );
                }

                if (!empty($filtros['anio_egreso'])) {
                    $formaciones->whereYear(
                        'fecha_egreso',
                        (int) $filtros['anio_egreso']
                    );
                }

                if (!empty($filtros['estado_titulacion'])) {
                    $formaciones->where(
                        'estado_titulacion',
                        $filtros['estado_titulacion']
                    );
                }
            });
        }

        return $query;
    }
}
