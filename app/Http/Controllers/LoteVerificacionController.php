<?php

namespace App\Http\Controllers;

use App\Models\Aporte;
use App\Models\Colegio;
use App\Models\LoteVerificacion;
use App\Models\Pago;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;
use function PHPUnit\Framework\returnArgument;

class LoteVerificacionController extends Controller
{
    public function __construct(
        private AuditoriaService $auditoria
    ) {}
    public function index()
    {
        Gate::authorize('viewAny', LoteVerificacion::class);
        $usuario = Auth::user();

        /*USUARIO*/
        if ($usuario->rol?->nombre === 'USUARIO') {

            $loteActual = LoteVerificacion::with([
                'colegio',
                'enviadoPor',
                'pagos' => fn ($query) => $query
                    ->with('afiliado')
                    ->orderBy('id'),
            ])
                ->where('colegio_id', $usuario->colegio_id)
                ->whereIn('estado', [
                    'ABIERTO',
                    'ENVIADO',
                    'EN_VERIFICACION',
                ])
                ->orderByDesc('year')
                ->orderByDesc('mes')
                ->orderByDesc('id')
                ->first();

            return view('aportes.lotes.index', compact('loteActual'));
        }

        /*ADMIN / SUPER ADMIN*/
        $consulta = LoteVerificacion::with([
            'colegio',
            'enviadoPor',
            'pagos' => fn ($query) => $query
                ->with('afiliado')
                ->orderBy('id'),
        ])
            ->whereIn('estado', [
                'ABIERTO',
                'ENVIADO',
                'EN_VERIFICACION',
            ]);

        $lotes = $consulta
            ->orderByDesc('year')
            ->orderByDesc('mes')
            ->orderByDesc('id')
            ->paginate(10);

        return view('aportes.lotes.index', compact('lotes'));
    }

    public function historial(Request $request)
    {
        Gate::authorize('viewAny', LoteVerificacion::class);

        $usuario = Auth::user();
        $datos = $request->validate([ 
            'buscar' => ['nullable', 'string', 'max:100'], 
            'colegio_id' => ['nullable', 'integer', 'exists:colegios,id'], 
            ]);

        $consulta = LoteVerificacion::with([
            'colegio',
            'enviadoPor',
            'verificadoPor',
            'pagos' => fn ($query) => $query
                ->whereIn('estado', ['OBSERVADO', 'VERIFICADO'])
                ->with('afiliado')
                ->orderBy('id'),
        ])
            ->where('estado', 'VERIFICADO');

        /*USUARIO:*/
        if ($usuario->rol?->nombre === 'USUARIO') {
            $consulta->where('colegio_id', $usuario->colegio_id);
        }

        /*BUSQUEDA POR CODIGO DE LOTE */
        if (!empty($datos['buscar'])) {
            $buscar = trim($datos['buscar']);

            $consulta->where(
                'codigo_lote',
                'like',
                "%{$buscar}%"
            );
        }

        /*FILTRO POR COLEGIO */

        if (in_array($usuario->rol?->nombre, ['ADMIN', 'SUPER ADMIN']) && !empty($datos['colegio_id'])) {
            $consulta->where('colegio_id', $datos['colegio_id']);
        }

        $lotes = $consulta
            ->orderByDesc('year')
            ->orderByDesc('mes')
            ->orderByDesc('id')
            ->paginate(10);

        $colegios = collect();

        if (in_array($usuario->rol?->nombre, ['ADMIN', 'SUPER ADMIN'])) {
            $colegios = Colegio::where('estado', 'ACTIVO')
                ->orderBy('nombre')
                ->get();
        }

        return view('aportes.lotes.historial', compact('lotes', 'colegios'));
    }

    public function create()
    {
        Gate::authorize('create', LoteVerificacion::class);

        $usuario = Auth::user();

        if (!$usuario->colegio_id) {
            abort(403, 'El usuario no tiene un colegio asignado.');
        }

        $colegio = $usuario->colegio;

        if (!$colegio) {
            abort(403, 'El colegio del usuario no existe.');
        }

        return view(
            'aportes.lotes.create',
            compact('colegio')
        );
    }

    public function store(Request $request)
    {
        Gate::authorize('create', LoteVerificacion::class);

        $usuario = Auth::user();

        $datos = $request->validate([
            'mes' => [
                'required',
                'integer',
                'between:1,12',
            ],
        ]);

        if (!$usuario->colegio_id) {
            return back()
                ->withErrors([
                    'lote' => 'El usuario no tiene un colegio asignado.',
                ])
                ->withInput();
        }

        $colegio = $usuario->colegio;

        if (!$colegio) {
            return back()
                ->withErrors([
                    'lote' => 'El colegio del usuario no existe.',
                ])
                ->withInput();
        }

        $loteAbierto = LoteVerificacion::query()
            ->where('colegio_id', $usuario->colegio_id)
            ->where('estado', 'ABIERTO')
            ->exists();

        if ($loteAbierto) {
            return back()
                ->withErrors([
                    'lote' => 'Tu colegio ya tiene un lote abierto. Debes cerrarlo y enviarlo antes de crear otro.',
                ])
                ->withInput();
        }

        $year = now()->year;
        $mes = (int) $datos['mes'];

        if (!$colegio->codigo) {
            return back()
                ->withErrors([
                    'lote' => 'El colegio no tiene un código configurado.',
                ])
                ->withInput();
        }

        $codigoLote = $colegio->codigo
            . '-'
            . $year
            . '-'
            . str_pad($mes, 2, '0', STR_PAD_LEFT);

        try {
            $lote = LoteVerificacion::create([
                'colegio_id' => $usuario->colegio_id,
                'mes' => $mes,
                'year' => $year,
                'codigo_lote' => $codigoLote,
                'cantidad_aportes' => 0,
                'monto_total' => 0,
                'estado' => 'ABIERTO',
            ]);

            //Auditoria
            $this->auditoria->registrar(
                $usuario->id,
                'CREAR',
                'lotes_verificacion',
                $lote->id,
                null,
                [
                    'colegio_id' => $lote->colegio_id,
                    'mes' => $lote->mes,
                    'year' => $lote->year,
                    'codigo_lote' => $lote->codigo_lote,
                    'cantidad_aportes' => $lote->cantidad_aportes,
                    'monto_total' => $lote->monto_total,
                    'estado' => $lote->estado,
                ]
            );

        } catch (\Illuminate\Database\QueryException $e) {
            return back()
                ->withErrors([
                    'lote' => 'Ya existe un lote para ese colegio, mes y año.',
                ])
                ->withInput();
        }

        return redirect()
            ->route('lotes.index')
            ->with(
                'success',
                "El lote {$lote->codigo_lote} fue creado correctamente."
            );
    }

    public function cerrar(LoteVerificacion $lote)
    {
        Gate::authorize('cerrar', $lote);

        $usuario = Auth::user();

        $datosAnteriores = [
            'estado' => $lote->estado,
            'fecha_cierre_departamental' => $lote->fecha_cierre_departamental,
            'enviado_por' => $lote->enviado_por
        ];

        $lote->update([
            'estado' => 'ENVIADO',
            'fecha_cierre_departamental' => now(),
            'enviado_por' => $usuario->id,
        ]);

        //Auditoria
        $this->auditoria->registrar(
            $usuario->id,
            'CERRAR',
            'lotes_verificacion',
            $lote->id,
            $datosAnteriores,[
                'estado' => $lote->estado,
                'fecha_cierre_departamental' => $lote->fecha_cierre_departamental,
                'enviado_por' => $lote->enviado_por
            ]
        );

        return back()->with(
            'success',
            "El lote {$lote->codigo_lote} fue cerrado y enviado correctamente."
        );
    }

    public function reabrir(LoteVerificacion $lote)
    {
        $usuario = Auth::user();

        Gate::authorize('reabrir', $lote);

        //Datos Anteriores
        $datosAnteriores = [
            'estado' => $lote->estado,
            'fecha_cierre_departamental' => $lote->fecha_cierre_departamental,
            'enviado_por' => $lote->enviado_por,
        ];

        $lote->update([
            'estado' => 'ABIERTO',
            'fecha_cierre_departamental' => null,
            'enviado_por' => null,
        ]);

        //Auditoria
        $this->auditoria->registrar(
            $usuario->id,
            'REABRIR',
            'lotes_verificacion',
            $lote->id,
            $datosAnteriores, [
                'estado' => $lote->estado,
                'fecha_cierre_departamental' => $lote->fecha_cierre_departamental,
                'enviado_por' => $lote->enviado_por
            ]
        );

        return back()->with(
            'success',
            "El lote {$lote->codigo_lote} fue reabierto correctamente."
        );
    }

    public function revisar(LoteVerificacion $lote)
    {
        Gate::authorize('revisar', $lote);

        $lote->update([
            'estado' => 'EN_VERIFICACION',
            'fecha_recepcion_col_nacional' => now(),
        ]);

        return redirect()
            ->route('lotes.index')
            ->with('lote_revisar', $lote->id);
    }

    public function previewAportes(
        Request $request,
        LoteVerificacion $lote
    ) {
        Gate::authorize('previewAportes', $lote);

        $datos = $request->validate([
            'pago_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'pago_ids.*' => [
                'required',
                'integer',
                'distinct',
            ],
        ]);

        $pagos = $lote->pagos()
            ->where('estado', 'EN_LOTE')
            ->whereIn('id', $datos['pago_ids'])
            ->with('afiliado')
            ->get();

        if ($pagos->count() !== count($datos['pago_ids'])) {
            return response()->json([
                'message' => 'Uno o más pagos seleccionados ya no están disponibles para revisión.',
            ], 422);
        }

        $aportesPreview = [];

        foreach ($pagos as $pago) {
            $afiliado = $pago->afiliado;

            $ultimoAporte = $afiliado->aportes()
                ->where('estado', 'VERIFICADO')
                ->orderByDesc('year')
                ->orderByDesc('mes')
                ->first();

            if ($ultimoAporte) {
                $fechaInicio = Carbon::create(
                    $ultimoAporte->year,
                    $ultimoAporte->mes,
                    1
                )->addMonth();
            } else {
                $fechaInicio = Carbon::parse(
                    $afiliado->fecha_afiliacion
                )->startOfMonth();
            }

            $montoTotalCentavos = (int) round(
                $pago->monto_total * 100
            );

            if (
                $montoTotalCentavos % $pago->cantidad_periodos !== 0
            ) {
                return response()->json([
                    'message' => "El pago {$pago->id} no puede dividirse exactamente entre sus períodos.",
                ], 422);
            }

            $montoPorPeriodoCentavos = intdiv(
                $montoTotalCentavos,
                $pago->cantidad_periodos
            );

            for (
                $i = 0;
                $i < $pago->cantidad_periodos;
                $i++
            ) {
                $fechaPeriodo = $fechaInicio
                    ->copy()
                    ->addMonths($i);

                $aportesPreview[] = [
                    'pago_id' => $pago->id,
                    'afiliado_id' => $afiliado->id,
                    'nombre' => $afiliado->nombres
                        . ' '
                        . $afiliado->apellidos,
                    'ci' => $afiliado->ci,
                    'mes' => $fechaPeriodo->month,
                    'year' => $fechaPeriodo->year,
                    'monto' => number_format(
                        $montoPorPeriodoCentavos / 100,
                        2,
                        '.',
                        ''
                    ),
                    'seleccionado' => true,
                ];
            }
        }
        return response()->json([
            'aportes' => $aportesPreview,
        ]);
    }

    public function confirmarAportes(
        Request $request,
        LoteVerificacion $lote
    ) {
        Gate::authorize('confirmarAportes', $lote);

        $datos = $request->validate([
            'pago_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'pago_ids.*' => [
                'required',
                'integer',
                'distinct',
            ],

            'pagos_observados' => [
                'nullable',
                'array',
            ],
            'pagos_observados.*' => [
                'required',
                'integer',
                'distinct',
            ],
            'observaciones_pagos' => [
                'nullable',
                'array',
            ],

            'observaciones_pagos.*' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $pagoIds = $datos['pago_ids'];
        $pagosObservadosIds = $datos['pagos_observados'] ?? [];
        $observacionesPagos = $datos['observaciones_pagos'] ?? [];
        $observacionesPagosNormalizadas = [];

        foreach ($pagosObservadosIds as $pagoId) {
            $observacion = trim($observacionesPagos[$pagoId] ?? '');

            if ($observacion === '') {
                return response()->json([
                    'message' => "Debes ingresar una observación para el pago {$pagoId}.",
                ], 422);
            }

            $observacionesPagosNormalizadas[$pagoId] = $observacion;
        }

        if (!empty(array_diff(
            $pagosObservadosIds,
            $pagoIds
        ))) {
            return response()->json([
                'message' => 'Se recibió un pago observado que no pertenece a la selección.',
            ], 422);
        }

        $usuario = Auth::user();

        $datosAnteriores = [
            'estado_lote' => $lote->estado,
            'pagos_seleccionados' => $pagoIds,
            'pagos_marcados_como_observados' => $pagosObservadosIds,
        ];

        try {
            $resultado = DB::transaction(function () use (
                $lote,
                $usuario,
                $pagoIds,
                $pagosObservadosIds,
                $observacionesPagosNormalizadas
            ) {
                $pagos = $lote->pagos()
                    ->whereIn('id', $pagoIds)
                    ->where('estado', 'EN_LOTE')
                    ->with('afiliado')
                    ->lockForUpdate()
                    ->get();

                if ($pagos->count() !== count($pagoIds)) {
                    throw new \Exception(
                        'Uno o más pagos seleccionados ya no están disponibles para procesar.'
                    );
                }

                $pagosProcesados = 0;
                $pagosObservados = 0;
                $aportesGenerados = 0;

                $pagosProcesadosIds = [];
                $pagosObservadosIdsRegistrados = [];

                foreach ($pagos as $pago) {
                    if (in_array(
                        $pago->id,
                        $pagosObservadosIds
                    )) {
                        $pago->update([
                            'estado' => 'OBSERVADO',
                            'observaciones' => $observacionesPagosNormalizadas[$pago->id],
                            'verificado_por' => $usuario->id,
                            'fecha_verificacion' => now(),
                        ]);
                        $pagosObservadosIdsRegistrados[] = $pago->id;
                        $pagosObservados++;
                        continue;
                    }

                    /*PAGO VERIFICADO*/
                    $afiliado = $pago->afiliado;

                    $ultimoAporte = $afiliado->aportes()
                        ->where('estado', 'VERIFICADO')
                        ->orderByDesc('year')
                        ->orderByDesc('mes')
                        ->first();

                    if ($ultimoAporte) {
                        $fechaInicio = Carbon::create(
                            $ultimoAporte->year,
                            $ultimoAporte->mes,
                            1
                        )->addMonth();
                    } else {
                        $fechaInicio = Carbon::parse(
                            $afiliado->fecha_afiliacion
                        )->startOfMonth();
                    }

                    $montoTotalCentavos = (int) round(
                        $pago->monto_total * 100
                    );

                    if (
                        $montoTotalCentavos
                        % $pago->cantidad_periodos !== 0
                    ) {
                        throw new \Exception(
                            "El pago {$pago->id} no puede dividirse exactamente entre sus períodos."
                        );
                    }

                    $montoPorPeriodoCentavos = intdiv(
                        $montoTotalCentavos,
                        $pago->cantidad_periodos
                    );

                    /*Generamos los períodos consecutivos.*/
                    for (
                        $i = 0;
                        $i < $pago->cantidad_periodos;
                        $i++
                    ) {
                        $fechaPeriodo = $fechaInicio
                            ->copy()
                            ->addMonths($i);

                        /*Protección adicional contra duplicados.*/
                        $existe = Aporte::where(
                                'afiliado_id',
                                $afiliado->id
                            )
                            ->where(
                                'mes',
                                $fechaPeriodo->month
                            )
                            ->where(
                                'year',
                                $fechaPeriodo->year
                            )
                            ->exists();

                        if ($existe) {
                            throw new \Exception(
                                "El afiliado {$afiliado->id} ya tiene un aporte registrado para {$fechaPeriodo->month}/{$fechaPeriodo->year}."
                            );
                        }

                        Aporte::create([
                            'afiliado_id' => $afiliado->id,
                            'colegio_id' => $afiliado->colegio_id,
                            'pago_id' => $pago->id,
                            'mes' => $fechaPeriodo->month,
                            'year' => $fechaPeriodo->year,
                            'monto' => $montoPorPeriodoCentavos / 100,
                            'estado' => 'VERIFICADO',
                            'observaciones' => null,
                            'registrado_por' => $pago->registrado_por,
                            'fecha_registro' => now(),
                            'verificado_por' => $usuario->id,
                            'fecha_verificacion' => now(),
                        ]);

                        $aportesGenerados++;
                    }

                    /*El pago queda definitivamente verificado.*/
                    $pago->update([
                        'estado' => 'VERIFICADO',
                        'verificado_por' => $usuario->id,
                        'fecha_verificacion' => now(),
                    ]);
                    $pagosProcesadosIds[] = $pago->id;
                    $pagosProcesados++;
                }

                $pagosPendientes = Pago::where(
                        'lote_verificacion_id',
                        $lote->id
                    )
                    ->where('estado', 'EN_LOTE')
                    ->count();

                $loteVerificado = false;

                if ($pagosPendientes === 0) {
                    $lote->update([
                        'estado' => 'VERIFICADO',
                        'verificado_por' => $usuario->id,
                        'fecha_verificacion' => now(),
                    ]);

                    $loteVerificado = true;
                }

                return [
                    'pagos_procesados' => $pagosProcesados,
                    'pagos_observados' => $pagosObservados,
                    'aportes_generados' => $aportesGenerados,
                    'lote_verificado' => $loteVerificado,

                    //Auditoria
                    'pagos_procesados_ids' => $pagosProcesadosIds,
                    'pagos_observados_ids' => $pagosObservadosIdsRegistrados,
                    'observaciones_pagos' => $observacionesPagosNormalizadas,
                ];
            });

            $datosNuevos = [
                'estado_lote' => $lote->estado,
                'pagos_verificados' => $resultado['pagos_procesados_ids'],
                'pagos_observados' => $resultado['pagos_observados_ids'],
                'observaciones_pagos' => $resultado['observaciones_pagos'],
                'cantidad_pagos_verificados' => $resultado['pagos_procesados'],
                'cantidad_pagos_observados' => $resultado['pagos_observados'],
                'cantidad_aportes_generados' => $resultado['aportes_generados'],
            ];

            $this->auditoria->registrar(
                $usuario->id,
                'CONFIRMAR APORTES',
                'lotes_verificacion',
                $lote->id,
                $datosAnteriores,
                $datosNuevos
            );

            return response()->json([
                'message' => 'La revisión del lote se procesó correctamente.',
                'resultado' => $resultado,
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
