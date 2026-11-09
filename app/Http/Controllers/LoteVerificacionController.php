<?php

namespace App\Http\Controllers;

use App\Models\Aporte;
use App\Models\Afiliado;
use App\Models\Colegio;
use App\Models\LoteVerificacion;
use App\Models\Pago;
use App\Services\AuditoriaService;
use App\Services\SaldoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;
use function PHPUnit\Framework\returnArgument;

class LoteVerificacionController extends Controller
{
    public function __construct(
        private AuditoriaService $auditoria,
        private SaldoService $saldoService
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
                    ->with(['afiliado.saldosActivos', 'saldos'])
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
                ->with(['afiliado.saldosActivos', 'saldos'])
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
                ->with(['afiliado.saldosActivos', 'saldos'])
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
            . str_pad((string) $mes, 2, '0', STR_PAD_LEFT);

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
        $resumenPagos = [];

        foreach ($pagos as $pago) {
            $afiliado = $pago->afiliado;

            if (!$afiliado) {
                return response()->json([
                    'message' => "El pago {$pago->id} no tiene una afiliada asociada.",
                ], 422);
            }

            if (
                $pago->monto_mensual === null ||
                (int) round((float) $pago->monto_mensual * 100) <= 0
            ) {
                return response()->json([
                    'message' => "El pago {$pago->id} no tiene una tarifa mensual válida. Debe revisarse antes de confirmar.",
                ], 422);
            }

            $montoMensualCentavos = (int) round(
                (float) $pago->monto_mensual * 100
            );

            $montoTotalCentavos = (int) round(
                (float) $pago->monto_total * 100
            );

            $saldosAnteriores = $this->saldoService
                ->activosParaAfiliado($afiliado->id);
            $distribucion = $this->saldoService->distribuir(
                $saldosAnteriores,
                $montoTotalCentavos,
                $montoMensualCentavos
            );

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

            $periodos = $this->periodosDesde(
                $fechaInicio,
                $distribucion['cantidad_periodos']
            );
            $aportesExistentes = $this->aportesExistentesPorPeriodo(
                $afiliado->id,
                $periodos
            );

            foreach ($periodos as $fechaPeriodo) {
                if (isset($aportesExistentes[$this->clavePeriodo(
                    $fechaPeriodo->year,
                    $fechaPeriodo->month
                )])) {
                    return response()->json([
                        'message' => "La afiliada {$afiliado->id} ya tiene un aporte registrado para {$fechaPeriodo->month}/{$fechaPeriodo->year}.",
                    ], 422);
                }

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
                        $montoMensualCentavos / 100,
                        2,
                        '.',
                        ''
                    ),
                    'seleccionado' => true,
                ];
            }

            $saldoAplicadoCentavos = min(
                $distribucion['saldo_anterior_centavos'],
                $distribucion['costo_periodos_centavos']
            );

            if ($saldoAplicadoCentavos > 0) {
                $resumenPagos[] = [
                    'pago_id' => $pago->id,
                    'monto_total' => number_format(
                        $montoTotalCentavos / 100,
                        2,
                        '.',
                        ''
                    ),
                    'monto_mensual' => number_format(
                        $montoMensualCentavos / 100,
                        2,
                        '.',
                        ''
                    ),
                    'nombre' => $afiliado->nombres
                        . ' '
                        . $afiliado->apellidos,
                    'ci' => $afiliado->ci,
                    'saldo_anterior' => number_format(
                        $distribucion['saldo_anterior_centavos'] / 100,
                        2,
                        '.',
                        ''
                    ),
                    'saldo_aplicado' => number_format(
                        $saldoAplicadoCentavos / 100,
                        2,
                        '.',
                        ''
                    ),
                    'cantidad_periodos' =>
                        $distribucion['cantidad_periodos'],
                    'saldo_final_estimado' => number_format(
                        $distribucion['saldo_final_centavos'] / 100,
                        2,
                        '.',
                        ''
                    ),
                ];
            }
        }

        return response()->json([
            'aportes' => $aportesPreview,
            'resumen_pagos' => $resumenPagos,
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
            $observacion = trim(
                $observacionesPagos[$pagoId] ?? ''
            );

            if ($observacion === '') {
                return response()->json([
                    'message' => "Debes ingresar una observación para el pago {$pagoId}.",
                ], 422);
            }

            $observacionesPagosNormalizadas[$pagoId] =
                $observacion;
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
            ): array {
                // Bloqueamos el lote para evitar confirmaciones simultáneas.
                $loteBloqueado = LoteVerificacion::query()
                    ->whereKey($lote->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $pagos = $loteBloqueado->pagos()
                    ->whereIn('id', $pagoIds)
                    ->where('estado', 'EN_LOTE')
                    ->with('afiliado')
                    ->lockForUpdate()
                    ->get();

                if ($pagos->count() !== count($pagoIds)) {
                    throw new \RuntimeException(
                        'Uno o más pagos seleccionados ya no están disponibles para procesar.'
                    );
                }

                $pagosProcesados = 0;
                $pagosObservados = 0;
                $aportesGenerados = 0;
                $saldosGenerados = 0;

                $pagosProcesadosIds = [];
                $pagosObservadosIdsRegistrados = [];
                $saldosGeneradosDetalle = [];

                foreach ($pagos as $pago) {
                    if (in_array(
                        $pago->id,
                        $pagosObservadosIds
                    )) {
                        $pago->update([
                            'estado' => 'OBSERVADO',
                            'observaciones' =>
                                $observacionesPagosNormalizadas[$pago->id],
                            'verificado_por' => $usuario->id,
                            'fecha_verificacion' => now(),
                        ]);

                        $pagosObservadosIdsRegistrados[] = $pago->id;
                        $pagosObservados++;

                        continue;
                    }

                    $afiliado = $pago->afiliado;

                    if (!$afiliado) {
                        throw new \RuntimeException(
                            "El pago {$pago->id} no tiene una afiliada asociada."
                        );
                    }

                    Afiliado::query()
                        ->whereKey($afiliado->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (
                        $pago->monto_mensual === null ||
                        (int) round(
                            (float) $pago->monto_mensual * 100
                        ) <= 0
                    ) {
                        throw new \RuntimeException(
                            "El pago {$pago->id} no tiene una tarifa mensual válida. Debe revisarse antes de confirmar."
                        );
                    }

                    $montoMensualCentavos = (int) round(
                        (float) $pago->monto_mensual * 100
                    );

                    $montoTotalCentavos = (int) round(
                        (float) $pago->monto_total * 100
                    );

                    $saldosAnteriores = $this->saldoService
                        ->activosParaAfiliado($afiliado->id, true);
                    $distribucion = $this->saldoService->distribuir(
                        $saldosAnteriores,
                        $montoTotalCentavos,
                        $montoMensualCentavos
                    );
                    $cantidadPeriodos =
                        $distribucion['cantidad_periodos'];

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

                    $periodos = $this->periodosDesde(
                        $fechaInicio,
                        $cantidadPeriodos
                    );
                    $aportesExistentes = $this->aportesExistentesPorPeriodo(
                        $afiliado->id,
                        $periodos
                    );

                    // Validamos todos los períodos antes de insertarlos en bloque.
                    $aportesParaInsertar = [];
                    $ahora = now();

                    foreach ($periodos as $fechaPeriodo) {
                        if (isset($aportesExistentes[$this->clavePeriodo(
                            $fechaPeriodo->year,
                            $fechaPeriodo->month
                        )])) {
                            throw new \RuntimeException(
                                "La afiliada {$afiliado->id} ya tiene un aporte registrado para {$fechaPeriodo->month}/{$fechaPeriodo->year}."
                            );
                        }

                        $aportesParaInsertar[] = [
                            'afiliado_id' => $afiliado->id,
                            'colegio_id' => $afiliado->colegio_id,
                            'pago_id' => $pago->id,
                            'mes' => $fechaPeriodo->month,
                            'year' => $fechaPeriodo->year,
                            'monto' => $montoMensualCentavos / 100,
                            'estado' => 'VERIFICADO',
                            'observaciones' => null,
                            'registrado_por' => $pago->registrado_por,
                            'fecha_registro' => $ahora,
                            'verificado_por' => $usuario->id,
                            'fecha_verificacion' => $ahora,
                            'creado_en' => $ahora,
                            'actualizado_en' => $ahora,
                        ];
                    }

                    if ($aportesParaInsertar !== []) {
                        foreach (
                            array_chunk($aportesParaInsertar, 50)
                            as $loteAportes
                        ) {
                            Aporte::query()->insert($loteAportes);
                        }

                        $aportesGenerados += count($aportesParaInsertar);
                    }

                    /*
                    * Consumimos primero los saldos anteriores.
                    * Solo se utiliza de ellos lo necesario para cubrir
                    * los períodos generados.
                    */
                    $this->saldoService->consumir(
                        $saldosAnteriores,
                        $distribucion['costo_periodos_centavos']
                    );

                    /*
                    * Lo que no cubren los saldos anteriores se cubre
                    * con el pago actual.
                    */
                    $consumoPagoCentavos = max(
                        0,
                        $distribucion['costo_periodos_centavos']
                            - $distribucion['saldo_anterior_centavos']
                    );

                    if ($consumoPagoCentavos > $montoTotalCentavos) {
                        throw new \RuntimeException(
                            "No se pudo cuadrar el cálculo del pago {$pago->id}."
                        );
                    }

                    $remanentePagoCentavos = max(
                        0,
                        $montoTotalCentavos - $consumoPagoCentavos
                    );

                    // Solo generamos el saldo nuevo al confirmar el pago.
                    if ($remanentePagoCentavos > 0) {
                        $saldoNuevo = $this->saldoService
                            ->registrarRemanente(
                                $afiliado->id,
                                $pago->id,
                                $remanentePagoCentavos
                            );

                        $saldosGenerados++;
                        $saldosGeneradosDetalle[] = [
                            'pago_id' => $pago->id,
                            'saldo_id' => $saldoNuevo?->id,
                            'monto' => number_format(
                                $remanentePagoCentavos / 100,
                                2,
                                '.',
                                ''
                            ),
                        ];
                    }

                    // Actualizamos el número real de períodos generados.
                    $cantidadPeriodosAnterior = (int) $pago->cantidad_periodos;

                    $pago->update([
                        'cantidad_periodos' => $cantidadPeriodos,
                        'estado' => 'VERIFICADO',
                        'verificado_por' => $usuario->id,
                        'fecha_verificacion' => now(),
                    ]);

                    // El lote ya contabilizó los períodos originales al registrar el pago.
                    $diferenciaPeriodos =
                        $cantidadPeriodos - $cantidadPeriodosAnterior;

                    if ($diferenciaPeriodos > 0) {
                        $loteBloqueado->increment(
                            'cantidad_aportes',
                            $diferenciaPeriodos
                        );
                    } elseif ($diferenciaPeriodos < 0) {
                        $loteBloqueado->decrement(
                            'cantidad_aportes',
                            abs($diferenciaPeriodos)
                        );
                    }

                    $pagosProcesadosIds[] = $pago->id;
                    $pagosProcesados++;
                }

                $pagosPendientes = Pago::query()
                    ->where('lote_verificacion_id', $loteBloqueado->id)
                    ->where('estado', 'EN_LOTE')
                    ->count();

                $loteVerificado = false;

                if ($pagosPendientes === 0) {
                    $loteBloqueado->update([
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
                    'saldos_generados' => $saldosGenerados,
                    'saldos_generados_detalle' => $saldosGeneradosDetalle,
                    'lote_verificado' => $loteVerificado,
                    'pagos_procesados_ids' => $pagosProcesadosIds,
                    'pagos_observados_ids' =>
                        $pagosObservadosIdsRegistrados,
                    'observaciones_pagos' =>
                        $observacionesPagosNormalizadas,
                ];
            });

            $lote->refresh();

            $datosNuevos = [
                'estado_lote' => $lote->estado,
                'pagos_verificados' => $resultado['pagos_procesados_ids'],
                'pagos_observados' => $resultado['pagos_observados_ids'],
                'observaciones_pagos' => $resultado['observaciones_pagos'],
                'cantidad_pagos_verificados' => $resultado['pagos_procesados'],
                'cantidad_pagos_observados' => $resultado['pagos_observados'],
                'cantidad_aportes_generados' => $resultado['aportes_generados'],
                'cantidad_saldos_generados' => $resultado['saldos_generados'],
                'saldos_generados_detalle' =>
                    $resultado['saldos_generados_detalle'],
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
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => $e instanceof \RuntimeException
                    ? $e->getMessage()
                    : 'No se pudo procesar la revisión del lote. Verifica los datos e inténtalo nuevamente.',
            ], 422);
        }
    }

    /**
     * @return array<int, Carbon>
     */
    private function periodosDesde(Carbon $fechaInicio, int $cantidad): array
    {
        $periodos = [];

        for ($i = 0; $i < $cantidad; $i++) {
            $periodos[] = $fechaInicio->copy()->addMonths($i);
        }

        return $periodos;
    }

    /**
     * @param array<int, Carbon> $periodos
     * @return array<string, true>
     */
    private function aportesExistentesPorPeriodo(
        int $afiliadoId,
        array $periodos
    ): array {
        if ($periodos === []) {
            return [];
        }

        $primerPeriodo = $periodos[0];
        $ultimoPeriodo = $periodos[\count($periodos) - 1];
        $aportesExistentes = [];

        $aportes = Aporte::query()
            ->where('afiliado_id', $afiliadoId)
            ->whereBetween('year', [
                $primerPeriodo->year,
                $ultimoPeriodo->year,
            ])
            ->get(['mes', 'year']);

        foreach ($aportes as $aporte) {
            $aportesExistentes[$this->clavePeriodo(
                (int) $aporte->year,
                (int) $aporte->mes
            )] = true;
        }

        return $aportesExistentes;
    }

    private function clavePeriodo(int $year, int $mes): string
    {
        return "{$year}-{$mes}";
    }

}
