<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use App\Models\Aporte;
use App\Models\Colegio;
use App\Models\GradoAcademico;
use App\Models\Postgrado;
use App\Models\Saldo;
use App\Models\TransferenciaAfiliado;
use App\Services\AuditoriaService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AfiliadoController extends Controller
{
    public function __construct(
        private AuditoriaService $auditoria
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Afiliado::class);

        $usuario = auth()->user();

        $query = Afiliado::with('colegio');

        $postgrados = Postgrado::where('estado', 'ACTIVO')
            ->orderBy('nombre')
            ->get();

        $gradosAcademicos = GradoAcademico::where('estado', 'ACTIVO')
            ->orderBy('nombre')
            ->get();

        if ($usuario->rol?->nombre === 'USUARIO') {

            $colegio = $usuario->colegio;

            if (! $colegio) {
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
            ->paginate(50)
            ->withQueryString();

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
        } elseif (
            $usuario->rol?->nombre === 'USUARIO'
            && $usuario->colegio?->tipo === 'DEPARTAMENTAL'
        ) {
            $colegios = $this->colegiosPermitidosParaUsuario(
                $usuario->colegio
            );
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

        $this->normalizarIdentificadores($request);

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

            'observacion' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];

        $mensajes = [
            'registro_departamental.unique' => 'Ya existe un afiliado registrado con ese registro departamental.',
            'matricula_profesional.unique' => 'Ya existe un afiliado registrado con esa matrícula profesional.',
            'ci.unique' => 'Ya existe un afiliado registrado con ese CI.',
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
        } elseif (
            $usuario->rol?->nombre === 'USUARIO'
            && $usuario->colegio?->tipo === 'DEPARTAMENTAL'
        ) {
            $colegiosPermitidos = $this->colegiosPermitidosParaUsuario(
                $usuario->colegio
            );

            if ($colegiosPermitidos->count() > 1) {
                $reglas['colegio_id'] = [
                    'required',
                    'integer',
                    Rule::in($colegiosPermitidos->modelKeys()),
                ];
            }
        }

        $datos = $request->validate($reglas, $mensajes);
        $observacion = $datos['observacion'] ?? null;
        unset($datos['observacion']);

        if ($usuario->rol?->nombre === 'USUARIO') {

            if (! $usuario->colegio_id) {
                abort(403);
            }

            $datos['colegio_id'] = $datos['colegio_id']
                ?? $usuario->colegio_id;
        }

        Gate::authorize(
            'createInColegio',
            [
                Afiliado::class,
                $datos['colegio_id'],
            ]
        );

        $datos['estado'] = 'ACTIVO';

        $afiliado = DB::transaction(function () use (
            $datos,
            $observacion,
            $usuario
        ) {
            $afiliado = Afiliado::create($datos);

            if (filled($observacion)) {
                $afiliado->observaciones()->create([
                    'observacion' => $observacion,
                    'usuario_id' => $usuario->id,
                ]);
            }

            return $afiliado;
        });

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

        $this->normalizarIdentificadores($request);

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

            'observacion' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);
        $observacion = $datos['observacion'] ?? null;
        unset($datos['observacion']);

        /*
        * ---------------------------------------------------------
        * DETERMINAR EL COLEGIO
        * ---------------------------------------------------------
        *
        * Solamente SUPER ADMIN puede cambiar el colegio.
        */
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

                /*
                * La fecha solamente será necesaria cuando
                * realmente se produzca una transferencia.
                */
                'fecha_transferencia' => [
                    'nullable',
                    'date',
                ],
            ]);

            $datos['colegio_id'] = $request->input(
                'colegio_id'
            );

        } else {

            /*
            * Los demás usuarios no pueden cambiar
            * el colegio de la afiliada.
            */
            $datos['colegio_id'] = $afiliado->colegio_id;
        }

        /*
        * El estado no se modifica desde este formulario.
        */
        $datos['estado'] = $afiliado->estado;

        /*
        * ---------------------------------------------------------
        * DETERMINAR SI EXISTE UNA TRANSFERENCIA
        * ---------------------------------------------------------
        */
        $huboTransferencia =
            (int) $afiliado->colegio_id
            !== (int) $datos['colegio_id'];

        if ($huboTransferencia) {
            $request->validate([
                'fecha_transferencia' => [
                    'required',
                    'date',
                    'before_or_equal:today',
                ],
            ]);
        }

        /*
        * Guardamos los datos de transferencia antes de
        * modificar la afiliada.
        */
        $transferencia = null;

        /*
        * ---------------------------------------------------------
        * ACTUALIZACIÓN ATÓMICA
        * ---------------------------------------------------------
        *
        * La actualización de la afiliada y el registro de la
        * transferencia deben realizarse juntos.
        */
        DB::transaction(function () use (
            $afiliado,
            $datos,
            $huboTransferencia,
            $request,
            $observacion,
            $usuario,
            &$transferencia
        ) {

            /*
            * Si cambió el colegio, primero guardamos
            * los datos necesarios para el historial.
            */
            if ($huboTransferencia) {
                $transferencia = TransferenciaAfiliado::create([
                    'afiliado_id' => $afiliado->id,

                    'colegio_id_origen' => $afiliado->colegio_id,

                    'colegio_id_destino' => $datos['colegio_id'],

                    'fecha_transferencia' => $request->input(
                        'fecha_transferencia'
                    ) ?? now()->toDateString(),
                ]);
            }

            /*
            * Finalmente actualizamos la afiliada.
            */
            $afiliado->update($datos);

            if (filled($observacion)) {
                $afiliado->observaciones()->create([
                    'observacion' => $observacion,
                    'usuario_id' => $usuario->id,
                ]);
            }
        });

        /*
        * ---------------------------------------------------------
        * DATOS NUEVOS
        * ---------------------------------------------------------
        */
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

        /*
        * ---------------------------------------------------------
        * AUDITORÍA DEL AFILIADO
        * ---------------------------------------------------------
        */
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

        /*
        * ---------------------------------------------------------
        * AUDITORÍA DE LA TRANSFERENCIA
        * ---------------------------------------------------------
        *
        * La transferencia es un hecho independiente de la
        * actualización general de la afiliada, por lo que también
        * dejamos registrada su creación.
        */
        if ($transferencia !== null) {

            $this->auditoria->registrar(
                auth()->id(),
                'CREAR',
                'transferencias_afiliados',
                $transferencia->id,
                null,
                [
                    'afiliado_id' => $transferencia->afiliado_id,

                    'colegio_id_origen' => $transferencia->colegio_id_origen,

                    'colegio_id_destino' => $transferencia->colegio_id_destino,

                    'fecha_transferencia' => $transferencia->fecha_transferencia,
                ]
            );
        }

        return redirect()
            ->route('afiliados.index')
            ->with(
                'success',
                $huboTransferencia
                    ? 'Afiliado actualizado y transferencia registrada correctamente.'
                    : 'Afiliado actualizado correctamente.'
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
            ['estado' => $estadoAnterior],
            ['estado' => $afiliado->estado]
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

        /* Restricción por colegio para usuarios USUARIO */
        if ($usuario->rol?->nombre === 'USUARIO') {
            $colegio = $usuario->colegio;

            if (! $colegio) {
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
        if (! $afiliado) {
            return response()->json([
                'message' => 'No se encontró ningún afiliado con ese CI.',
            ], 404);
        }

        /* Último aporte verificado */
        $ultimoAporte = Aporte::query()
            ->where('afiliado_id', $afiliado->id)
            ->where('estado', 'VERIFICADO')
            ->orderByDesc('year')
            ->orderByDesc('mes')
            ->first();

        $saldoDisponible = Saldo::query()
            ->where('afiliado_id', $afiliado->id)
            ->where('estado', 'ACTIVO')
            ->where('monto_disponible', '>', 0)
            ->sum('monto_disponible');

        $montoAporteLaboral = $afiliado->informacionesLaborales()
            ->where('culminado', false)
            ->orderByDesc('fecha_ingreso_trabajo')
            ->value('monto_aporte');

        return response()->json([
            'id' => $afiliado->id,
            'ci' => $afiliado->ci,
            'nombres' => $afiliado->nombres,
            'apellidos' => $afiliado->apellidos,
            'estado' => $afiliado->estado,
            'saldo_disponible' => number_format(
                (float) $saldoDisponible,
                2,
                '.',
                ''
            ),
            'monto_aporte_laboral' => $montoAporteLaboral,
            'ultimo_aporte' => $ultimoAporte
                ? [
                    'monto' => $ultimoAporte->monto,
                    'mes' => $ultimoAporte->mes,
                    'year' => $ultimoAporte->year,
                ]
                : null,
        ]);
    }

    private function normalizarIdentificadores(Request $request): void
    {
        foreach ([
            'registro_departamental',
            'matricula_profesional',
            'ci',
        ] as $campo) {
            $valor = $request->input($campo);

            if (is_string($valor)) {
                $request->merge([
                    $campo => Afiliado::normalizarIdentificador($valor),
                ]);
            }
        }
    }

    public function detalle(Afiliado $afiliado)
    {
        Gate::authorize('viewLaboral', $afiliado);

        $afiliado->load([
            'colegio',
            'informacionesLaborales' => function ($query) {
                $query->orderByDesc('fecha_ingreso_trabajo');
            },
            'afiliadosPostgrados.postgrado',
            'afiliadosPostgrados.gradoAcademico',
            'observaciones' => function ($query) {
                $query->orderByDesc('creado_en')
                    ->orderByDesc('id');
            },
        ]);

        $postgrados = Postgrado::where('estado', 'ACTIVO')
            ->orderBy('nombre')
            ->get();

        $gradosAcademicos = GradoAcademico::where('estado', 'ACTIVO')
            ->orderBy('nombre')
            ->get();

        return view(
            'afiliados.partials.info',
            compact('afiliado', 'postgrados', 'gradosAcademicos')
        );
    }

    public function formularioEdicion(Afiliado $afiliado)
    {
        Gate::authorize('update', $afiliado);

        $colegios = auth()->user()->rol?->nombre === 'SUPER ADMIN'
            ? Colegio::where('estado', 'ACTIVO')
                ->orderBy('nombre')
                ->get()
            : collect();

        return view('afiliados.partials.form', [
            'modo' => 'editar',
            'afiliado' => $afiliado,
            'colegios' => $colegios,
        ]);
    }

    /**
     * @return Collection<int, Colegio>
     */
    private function colegiosPermitidosParaUsuario(Colegio $colegio)
    {
        return $colegio->hijos()
            ->where('estado', 'ACTIVO')
            ->orderBy('nombre')
            ->get()
            ->prepend($colegio);
    }
}
