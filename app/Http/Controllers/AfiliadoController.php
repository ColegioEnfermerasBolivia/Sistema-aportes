<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use App\Models\Colegio;
use App\Models\Postgrado;
use App\Models\Aporte;
use App\Services\AuditoriaService;
use App\Models\GradoAcademico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AfiliadoController extends Controller
{
    public function __construct(
        private AuditoriaService $auditoria
    ) {
    }

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Afiliado::class);

        $usuario = auth()->user();

        $query = Afiliado::with([
            'colegio',
            'informacionesLaborales' => function ($query) {
                $query->orderByDesc('fecha_ingreso_trabajo');
            },
            'afiliadosPostgrados.postgrado',
            'afiliadosPostgrados.gradoAcademico',
        ]);

        $postgrados = Postgrado::where('estado', 'ACTIVO')
            ->orderBy('nombre')
            ->get();

        $gradosAcademicos = GradoAcademico::where('estado', 'ACTIVO')
            ->orderBy('nombre')
            ->get();

        if ($usuario->rol?->nombre === 'USUARIO') {

            $colegio = $usuario->colegio;

            if (!$colegio) {
                abort(403);
            }

            if ($colegio->tipo === 'DEPARTAMENTAL') {

                $colegiosPermitidos = $colegio->hijos
                    ->pluck('id')
                    ->push($colegio->id);

                $query->whereIn(
                    'colegio_id',
                    $colegiosPermitidos
                );

            } else {

                $query->where(
                    'colegio_id',
                    $colegio->id
                );
            }
        }

        if ($request->filled('buscar')) {

            $buscar = trim(
                $request->input('buscar')
            );

            $query->where(function ($q) use ($buscar) {

                $q->where(
                    'ci',
                    'like',
                    "%{$buscar}%"
                )
                ->orWhere(
                    'nombres',
                    'like',
                    "%{$buscar}%"
                )
                ->orWhere(
                    'apellidos',
                    'like',
                    "%{$buscar}%"
                );
            });
        }

        if (
            in_array($usuario->rol?->nombre, [
                'SUPER ADMIN',
                'ADMIN',
            ])
            && $request->filled('colegio_id')
        ) {

            $query->where(
                'colegio_id',
                $request->input('colegio_id')
            );
        }

        $afiliados = $query
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        $colegios = collect();

        if (
            in_array($usuario->rol?->nombre, [
                'SUPER ADMIN',
                'ADMIN',
            ])
        ) {

            $colegios = Colegio::where(
                'estado',
                'ACTIVO'
            )
            ->orderBy('nombre')
            ->get();
        }

        return view(
            'afiliados.index',
            compact(
                'afiliados',
                'colegios',
                'postgrados',
                'gradosAcademicos',
            )
        );
    }

    public function store(Request $request)
    {
        Gate::authorize(
            'create',
            Afiliado::class
        );

        $usuario = auth()->user();
        $reglas = [

            'registro_departamental' => [
                'required',
                'string',
                'max:30',
                'unique:afiliados,registro_departamental',
            ],

            'matricula_profesional' => [
                'required',
                'string',
                'max:30',
                'unique:afiliados,matricula_profesional',
            ],

            'ci' => [
                'required',
                'string',
                'max:20',
                'unique:afiliados,ci',
            ],

            'nombres' => [
                'required',
                'string',
                'max:100',
            ],

            'apellidos' => [
                'required',
                'string',
                'max:100',
            ],

            'celular' => [
                'required',
                'string',
                'max:20',
            ],

            'fecha_afiliacion' => [
                'required',
                'date',
            ],
        ];

        if ($usuario->rol?->nombre === 'ADMIN') {

            $reglas['colegio_id'] = [
                'required',
                Rule::exists('colegios', 'id')
                    ->where(function ($query) {
                        $query->where(
                            'estado',
                            'ACTIVO'
                        );
                    }),
            ];
        }

        $datos = $request->validate($reglas);

        if ($usuario->rol?->nombre === 'USUARIO') {

            if (!$usuario->colegio_id) {
                abort(403);
            }

            $datos['colegio_id'] = $usuario->colegio_id;
        }

        Gate::authorize(
            'createInColegio',
            [
                Afiliado::class,
                $datos['colegio_id']
            ]
        );

        $datos['estado'] = 'ACTIVO';

        $afiliado = Afiliado::create($datos);

        $datosNuevos = [
            'registro_departamental' => $afiliado->registro_departamental,
            'matricula_profesional' => $afiliado->matricula_profesional,
            'ci' => $afiliado->ci,
            'nombres' => $afiliado->nombres,
            'apellidos' => $afiliado->apellidos,
            'celular' => $afiliado->celular,
            'fecha_afiliacion' => $afiliado->fecha_afiliacion,
            'colegio_id' => $afiliado->colegio_id,
            'estado' => $afiliado->estado,
        ];

        $this->auditoria->registrar(
                auth()->id(),
                'CREAR',
                'afiliados',
                $afiliado->id,
                null,
                $datosNuevos
        );

        return redirect()
            ->route('afiliados.index')
            ->with(
                'success',
                'Afiliado registrado correctamente.'
            );
    }

    public function update(
        Request $request,
        Afiliado $afiliado
    ) {
        Gate::authorize(
            'update',
            $afiliado
        );

        $datosAnteriores = [
            'registro_departamental' => $afiliado->registro_departamental,
            'matricula_profesional' => $afiliado->matricula_profesional,
            'ci' => $afiliado->ci,
            'nombres' => $afiliado->nombres,
            'apellidos' => $afiliado->apellidos,
            'celular' => $afiliado->celular,
            'fecha_afiliacion' => $afiliado->fecha_afiliacion,
            'colegio_id' => $afiliado->colegio_id,
            'estado' => $afiliado->estado,
        ];

        $usuario = auth()->user();
        $datos = $request->validate([

            'registro_departamental' => [
                'required',
                'string',
                'max:30',
                Rule::unique(
                    'afiliados',
                    'registro_departamental'
                )->ignore($afiliado->id),
            ],

            'matricula_profesional' => [
                'required',
                'string',
                'max:30',
                Rule::unique(
                    'afiliados',
                    'matricula_profesional'
                )->ignore($afiliado->id),
            ],

            'ci' => [
                'required',
                'string',
                'max:20',
                Rule::unique(
                    'afiliados',
                    'ci'
                )->ignore($afiliado->id),
            ],

            'nombres' => [
                'required',
                'string',
                'max:100',
            ],

            'apellidos' => [
                'required',
                'string',
                'max:100',
            ],

            'celular' => [
                'required',
                'string',
                'max:20',
            ],

            'fecha_afiliacion' => [
                'required',
                'date',
            ],
        ]);

        if ($usuario->rol?->nombre === 'SUPER ADMIN') {
                $request->validate([
                    'colegio_id' => [
                        'required',
                        Rule::exists('colegios', 'id')
                            ->where(function ($query) {
                                $query->where(
                                    'estado',
                                    'ACTIVO'
                                );
                            }),
                    ],
                ]);

                $datos['colegio_id'] = $request->input(
                    'colegio_id'
                );

            } else {
                $datos['colegio_id'] = $afiliado->colegio_id;
            }

        $datos['estado'] = $afiliado->estado;
        $afiliado->update($datos);

        $datosNuevos = [
            'registro_departamental' => $afiliado->registro_departamental,
            'matricula_profesional' => $afiliado->matricula_profesional,
            'ci' => $afiliado->ci,
            'nombres' => $afiliado->nombres,
            'apellidos' => $afiliado->apellidos,
            'celular' => $afiliado->celular,
            'fecha_afiliacion' => $afiliado->fecha_afiliacion,
            'colegio_id' => $afiliado->colegio_id,
            'estado' => $afiliado->estado,
        ];

        if ($datosAnteriores !== $datosNuevos) {
            $this->auditoria->registrar(
                auth()->id(),
                'ACTUALIZAR',
                'afiliados',
                $afiliado->id,
                $datosAnteriores,
                $datosNuevos
            );
        }

        return redirect()
            ->route('afiliados.index')
            ->with(
                'success',
                'Afiliado actualizado correctamente.'
            );
    }

    public function cambiarEstado(
        Afiliado $afiliado
    ) {
        Gate::authorize(
            'changeEstado',
            $afiliado
        );

        $estadoAnterior = $afiliado->estado;

        $afiliado->estado = $afiliado->estado === 'ACTIVO'
            ? 'INACTIVO'
            : 'ACTIVO';

        $afiliado->save();

        $accion = $afiliado->estado === 'ACTIVO'
            ? 'ACTIVAR'
            : 'DESACTIVAR';
        
        $this->auditoria->registrar(
            auth()->id(),
            $accion,
            'afiliados',
            $afiliado->id,
            ['estado' => $estadoAnterior,],
            ['estado' => $afiliado->estado,]
        );

        return redirect()
            ->route('afiliados.index')
            ->with(
                'success',
                $afiliado->estado === 'ACTIVO'
                    ? 'Afiliado activado correctamente.'
                    : 'Afiliado desactivado correctamente.'
            );
    }

    public function buscarPorCi(Request $request)
    {
        $datos = $request->validate([
            'ci' => ['required', 'string', 'max:50'],
        ]);

        $usuario = auth()->user();
        $query = Afiliado::query()
            ->where('ci', trim($datos['ci']));

        /*Restricción por colegio para usuarios USUARIO*/
        if ($usuario->rol?->nombre === 'USUARIO') {
            $colegio = $usuario->colegio;

            if (!$colegio) {
                return response()->json([
                    'message' => 'El usuario no tiene un colegio asignado.',
                ], 403);
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

        $afiliado = $query->first();
        if (!$afiliado) {
            return response()->json([
                'message' => 'No se encontró ningún afiliado con ese CI.',
            ], 404);
        }

        /*Último aporte verificado*/
        $ultimoAporte = Aporte::query()
            ->where('afiliado_id', $afiliado->id)
            ->where('estado', 'VERIFICADO')
            ->orderByDesc('year')
            ->orderByDesc('mes')
            ->first();

        return response()->json([
            'id' => $afiliado->id,
            'ci' => $afiliado->ci,
            'nombres' => $afiliado->nombres,
            'apellidos' => $afiliado->apellidos,
            'estado' => $afiliado->estado,
            'ultimo_aporte' => $ultimoAporte
                ? [
                    'monto' => $ultimoAporte->monto,
                    'mes' => $ultimoAporte->mes,
                    'year' => $ultimoAporte->year,
                ]
                : null,
        ]);
    }
}