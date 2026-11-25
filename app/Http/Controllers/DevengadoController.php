<?php

namespace App\Http\Controllers;

use App\Exports\ReporteDevengadoLoteExport;
use App\Models\Afiliado;
use App\Models\Aporte;
use App\Models\Colegio;
use App\Models\DevengadoExoneracion;
use App\Models\DevengadoLote;
use App\Models\DevengadoPago;
use App\Models\DevengadoPagoDetalle;
use App\Models\DevengadoPeriodo;
use App\Models\Saldo;
use App\Models\Usuario;
use App\Services\AuditoriaService;
use App\Services\DevengadoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DevengadoController extends Controller
{
    public function __construct(
        private AuditoriaService $auditoria,
        private DevengadoService $devengadoService
    ) {}

    public function index()
    {
        Gate::authorize('viewAny', DevengadoLote::class);
        $usuario = Auth::user();
        $colegiosDisponibles = Colegio::query()
            ->whereIn('id', $this->colegiosPermitidos($usuario))
            ->where('estado', 'ACTIVO')
            ->orderBy('nombre')
            ->get();

        $lotes = DevengadoLote::query()
            ->whereIn('estado', ['ABIERTO', 'ENVIADO', 'EN_VERIFICACION'])
            ->when(
                $usuario->rol?->nombre === 'USUARIO',
                fn ($query) => $query->whereIn(
                    'colegio_id',
                    $this->colegiosPermitidos($usuario)
                )
            )
            ->with('colegio')
            ->withCount('pagos')
            ->latest('id')
            ->get();

        $pagos = DevengadoPago::query()
            ->whereHas('lote', function ($query) use ($usuario): void {
                if ($usuario->rol?->nombre === 'USUARIO') {
                    $query->whereIn(
                        'colegio_id',
                        $this->colegiosPermitidos($usuario)
                    );
                }
            })
            ->with(['afiliado', 'lote', 'detalles.periodo'])
            ->latest('id')
            ->paginate(15);
        if ($usuario->rol?->nombre === 'USUARIO') {
            $pagos->getCollection()
                ->filter(fn (DevengadoPago $pago): bool => $pago->estado === 'EN_LOTE'
                    && ! in_array($pago->lote->estado, ['EN_VERIFICACION', 'VERIFICADO'], true))
                ->each(fn (DevengadoPago $pago) => $this->prepararDatosEdicion($pago));
        }

        return view('devengados.index', compact('lotes', 'pagos', 'colegiosDisponibles'));
    }

    public function create()
    {
        Gate::authorize('create', DevengadoLote::class);
        $usuario = Auth::user();
        $colegiosDisponibles = Colegio::query()
            ->whereIn('id', $this->colegiosPermitidos($usuario))
            ->where('estado', 'ACTIVO')
            ->orderBy('nombre')
            ->get();

        return view('devengados.create', compact('colegiosDisponibles'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', DevengadoLote::class);
        $usuario = Auth::user();
        $colegiosPermitidos = $this->colegiosPermitidos($usuario);
        $datos = $request->validate([
            'colegio_id' => [
                $colegiosPermitidos === [] ? 'required' : 'nullable',
                'integer',
                Rule::in($colegiosPermitidos),
            ],
            'mes' => ['required', 'integer', 'between:1,12'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);

        $colegioId = (int) ($datos['colegio_id'] ?? $usuario->colegio_id);
        if (! in_array($colegioId, $colegiosPermitidos, true)) {
            abort(403);
        }

        $year = now()->year;
        $mes = (int) $datos['mes'];
        $lote = DB::transaction(function () use (
            $colegioId,
            $mes,
            $year,
            $usuario,
            $datos
        ): DevengadoLote {
            $colegio = Colegio::query()
                ->whereKey($colegioId)
                ->lockForUpdate()
                ->firstOrFail();
            if ($colegio->estado !== 'ACTIVO') {
                throw ValidationException::withMessages([
                    'colegio_id' => 'El colegio seleccionado no está activo.',
                ]);
            }

            $loteDevengadoExiste = DevengadoLote::query()
                ->where('colegio_id', $colegioId)
                ->where('mes', $mes)
                ->where('year', $year)
                ->exists();

            if ($loteDevengadoExiste) {
                throw ValidationException::withMessages([
                    'lote' => 'Ya existe un lote devengado para ese colegio y período.',
                ]);
            }

            if (! $colegio->codigo) {
                throw ValidationException::withMessages([
                    'lote' => 'El colegio no tiene un código configurado.',
                ]);
            }

            $codigo = 'DEV-'.$colegio->codigo.'-'.$year.'-'
                .str_pad((string) $mes, 2, '0', STR_PAD_LEFT);
            $lote = DevengadoLote::create([
                'colegio_id' => $colegioId,
                'mes' => $mes,
                'year' => $year,
                'codigo_lote' => $codigo,
                'estado' => 'ABIERTO',
                'observaciones' => $datos['observaciones'] ?? null,
                'creado_por' => $usuario->id,
            ]);

            $this->auditoria->registrar(
                $usuario->id,
                'CREAR',
                'devengado_lotes',
                $lote->id,
                null,
                [
                    'colegio_id' => $colegioId,
                    'mes' => $mes,
                    'year' => $year,
                    'codigo_lote' => $codigo,
                    'estado' => 'ABIERTO',
                ]
            );

            return $lote;
        });

        return redirect()
            ->route('lotes.index')
            ->with('success', "El lote {$lote->codigo_lote} fue creado.");
    }

    public function buscarAfiliada(Request $request)
    {
        Gate::authorize('viewAny', DevengadoLote::class);
        $datos = $request->validate([
            'buscar' => [
                'required_without_all:ci,afiliado_id',
                'nullable',
                'string',
                'max:100',
            ],
            'ci' => [
                'required_without_all:buscar,afiliado_id',
                'nullable',
                'string',
                'max:50',
            ],
            'afiliado_id' => [
                'required_without_all:buscar,ci',
                'nullable',
                'integer',
                'exists:afiliados,id',
            ],
        ]);

        $usuario = Auth::user();
        $consulta = Afiliado::query()
            ->with('colegio:id,nombre');

        if ($usuario->rol?->nombre === 'USUARIO') {
            $colegiosPermitidos = $this->colegiosPermitidos($usuario);
            if ($colegiosPermitidos === []) {
                abort(403);
            }

            $consulta->whereIn('colegio_id', $colegiosPermitidos);
        }

        if (! empty($datos['afiliado_id'])) {
            $afiliada = $consulta
                ->whereKey($datos['afiliado_id'])
                ->first();

            if (! $afiliada) {
                return response()->json([
                    'message' => 'No se encontró la afiliada.',
                ], 404);
            }

            return $this->datosAfiliadaParaPago($afiliada);
        }

        $termino = trim($datos['buscar'] ?? $datos['ci']);
        $ci = Afiliado::normalizarIdentificador($termino);
        $afiliadaPorCi = (clone $consulta)
            ->where('ci', $ci)
            ->first();

        if ($afiliadaPorCi) {
            return $this->datosAfiliadaParaPago($afiliadaPorCi);
        }

        $resultados = $consulta
            ->buscarPorTerminos($termino)
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->limit(26)
            ->get([
                'id',
                'colegio_id',
                'ci',
                'nombres',
                'apellidos',
                'estado',
            ]);

        if ($resultados->isEmpty()) {
            return response()->json([
                'message' => 'No se encontró ninguna afiliada con esos datos.',
            ], 404);
        }

        return response()->json([
            'lista' => true,
            'hay_mas_resultados' => $resultados->count() > 25,
            'resultados' => $resultados
                ->take(25)
                ->map(fn (Afiliado $afiliada): array => [
                    'id' => $afiliada->id,
                    'ci' => $afiliada->ci,
                    'nombres' => $afiliada->nombres,
                    'apellidos' => $afiliada->apellidos,
                    'estado' => $afiliada->estado,
                    'colegio' => $afiliada->colegio?->nombre,
                ])
                ->values(),
        ]);
    }

    private function datosAfiliadaParaPago(Afiliado $afiliada)
    {
        if (! $this->fechaAfiliacionValida($afiliada)) {
            return response()->json([
                'message' => $this->mensajeFechaAfiliacionInvalida(),
            ], 422);
        }

        $lotesAbiertos = DevengadoLote::query()
            ->where('colegio_id', $afiliada->colegio_id)
            ->where('estado', 'ABIERTO')
            ->whereNotNull('mes')
            ->whereNotNull('year')
            ->orderByDesc('year')
            ->orderByDesc('mes')
            ->get(['id', 'codigo_lote', 'mes', 'year']);

        return response()->json([
            'id' => $afiliada->id,
            'ci' => $afiliada->ci,
            'nombres' => $afiliada->nombres,
            'apellidos' => $afiliada->apellidos,
            'estado' => $afiliada->estado,
            'campos_faltantes_pago' => array_values(
                $afiliada->camposFaltantesParaPago()
            ),
            'lotes' => $lotesAbiertos,
            'gestiones' => $this->matrizPeriodos($afiliada),
        ]);
    }

    public function storePayment(Request $request)
    {
        Gate::authorize('create', DevengadoLote::class);
        $datos = $request->validate([
            'devengado_lote_id' => ['required', 'integer', 'exists:devengado_lotes,id'],
            'afiliado_id' => ['required', 'integer', 'exists:afiliados,id'],
            'clave_idempotencia' => ['required', 'uuid'],
            'monto_total' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:99999999.99'],
            'monto_mensual' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:99999999.99'],
            'metodo_determinacion' => [
                'required',
                Rule::in(['MINIMO_VIGENTE', 'BOLETAS_SALARIALES']),
            ],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'periodos' => ['nullable', 'array'],
            'periodos.*' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/', 'distinct'],
        ]);

        $loteId = (int) $datos['devengado_lote_id'];
        $afiliadaId = (int) $datos['afiliado_id'];
        $montoMensualCentavos = $this->aCentavos($datos['monto_mensual']);
        $montoTotalCentavos = $this->aCentavos($datos['monto_total']);
        $periodosSeleccionados = $this->parsearPeriodos(
            $datos['periodos'] ?? [],
            $montoMensualCentavos,
            $montoTotalCentavos
        );
        $usuario = Auth::user();

        $pagoDuplicado = DevengadoPago::query()
            ->where('clave_idempotencia', $datos['clave_idempotencia'])
            ->first();
        if ($pagoDuplicado) {
            abort_unless(
                $pagoDuplicado->afiliado_id === $afiliadaId
                    && $pagoDuplicado->devengado_lote_id === $loteId,
                409
            );

            return redirect()
                ->route('devengados.index')
                ->with('success', 'El pago ya había sido registrado; no se duplicó.');
        }

        DB::transaction(function () use (
            $datos,
            $usuario,
            $loteId,
            $afiliadaId,
            $montoMensualCentavos,
            $montoTotalCentavos,
            $periodosSeleccionados
        ): void {
            $lote = DevengadoLote::query()
                ->whereKey($loteId)
                ->lockForUpdate()
                ->firstOrFail();
            Gate::authorize('manage', $lote);

            $afiliada = Afiliado::query()
                ->whereKey($afiliadaId)
                ->lockForUpdate()
                ->firstOrFail();

            $afiliada->validarDatosParaPago();
            $this->validarAfiliadaEnLote($afiliada, $lote, $usuario);
            $this->validarPeriodosDisponibles(
                $afiliada,
                $periodosSeleccionados,
                $lote
            );

            $cantidadPeriodos = count($periodosSeleccionados);
            $saldoGeneradoCentavos = $montoTotalCentavos
                - ($montoMensualCentavos * $cantidadPeriodos);
            if ($saldoGeneradoCentavos < 0) {
                throw ValidationException::withMessages([
                    'periodos' => 'El pago total no alcanza para cubrir los meses seleccionados.',
                ]);
            }

            $pagoExistente = DevengadoPago::query()
                ->where('clave_idempotencia', $datos['clave_idempotencia'])
                ->first();
            if ($pagoExistente) {
                if (
                    $pagoExistente->afiliado_id !== $afiliadaId
                    || $pagoExistente->devengado_lote_id !== $loteId
                ) {
                    abort(409);
                }

                return;
            }

            $pago = DevengadoPago::create([
                'devengado_lote_id' => $lote->id,
                'afiliado_id' => $afiliada->id,
                'monto_total' => $montoTotalCentavos / 100,
                'monto_mensual' => $montoMensualCentavos / 100,
                'saldo_generado' => $saldoGeneradoCentavos / 100,
                'cantidad_periodos' => $cantidadPeriodos,
                'estado' => 'EN_LOTE',
                'clave_idempotencia' => $datos['clave_idempotencia'],
                'observaciones' => $datos['observaciones'] ?? null,
                'registrado_por' => $usuario->id,
                'fecha_pago' => now(),
            ]);

            $periodoIds = $this->guardarPeriodosDelPago(
                $pago,
                $lote,
                $afiliada,
                $periodosSeleccionados,
                $montoMensualCentavos,
                $datos['metodo_determinacion'],
                $datos['observaciones'] ?? null
            );

            $this->auditoria->registrar(
                $usuario->id,
                'CREAR',
                'devengado_pagos',
                $pago->id,
                null,
                [
                    'devengado_lote_id' => $lote->id,
                    'afiliado_id' => $afiliada->id,
                    'monto_total' => $pago->monto_total,
                    'monto_mensual' => $pago->monto_mensual,
                    'saldo_generado' => $pago->saldo_generado,
                    'periodo_ids' => $periodoIds,
                    'periodos' => $periodosSeleccionados,
                    'metodo_determinacion' => $datos['metodo_determinacion'],
                    'observaciones' => $datos['observaciones'] ?? null,
                ]
            );
        });

        return redirect()
            ->route('devengados.index')
            ->with('success', 'El pago devengado fue agregado al lote.');
    }

    public function editPayment(DevengadoPago $pago)
    {
        Gate::authorize('update', $pago);
        $pago->load(['afiliado', 'lote', 'detalles.periodo']);
        $gestiones = $this->matrizPeriodos(
            $pago->afiliado,
            $pago->id
        );
        $seleccionados = $pago->detalles
            ->map(fn (DevengadoPagoDetalle $detalle): array => [
                'year' => $detalle->periodo->year,
                'mes' => $detalle->periodo->mes,
            ])
            ->values();
        $afiliada = $pago->afiliado;
        $lotesDisponibles = collect([[
            'id' => $pago->lote->id,
            'codigo_lote' => $pago->lote->codigo_lote,
            'mes' => $pago->lote->mes,
            'year' => $pago->lote->year,
        ]]);

        return view('devengados.pagos.edit', compact(
            'pago',
            'gestiones',
            'seleccionados',
            'afiliada',
            'lotesDisponibles'
        ));
    }

    public function updatePayment(Request $request, DevengadoPago $pago)
    {
        Gate::authorize('update', $pago);
        $datos = $request->validate([
            'monto_total' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:99999999.99'],
            'monto_mensual' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:99999999.99'],
            'metodo_determinacion' => [
                'required',
                Rule::in(['MINIMO_VIGENTE', 'BOLETAS_SALARIALES']),
            ],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'periodos' => ['nullable', 'array'],
            'periodos.*' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/', 'distinct'],
        ]);

        $periodosSeleccionados = $this->parsearPeriodos(
            $datos['periodos'] ?? [],
            $this->aCentavos($datos['monto_mensual']),
            $this->aCentavos($datos['monto_total'])
        );
        $usuario = Auth::user();

        DB::transaction(function () use (
            $pago,
            $datos,
            $usuario,
            $periodosSeleccionados
        ): void {
            $lote = DevengadoLote::query()
                ->whereKey($pago->devengado_lote_id)
                ->lockForUpdate()
                ->firstOrFail();
            $pagoBloqueado = DevengadoPago::query()
                ->whereKey($pago->id)
                ->lockForUpdate()
                ->firstOrFail();
            if ($pagoBloqueado->devengado_lote_id !== $lote->id) {
                abort(409);
            }
            Gate::authorize('update', $pagoBloqueado);

            $afiliada = Afiliado::query()
                ->whereKey($pagoBloqueado->afiliado_id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->validarAfiliadaEnLote($afiliada, $lote, $usuario);
            $montoMensualCentavos = $this->aCentavos($datos['monto_mensual']);
            $montoTotalCentavos = $this->aCentavos($datos['monto_total']);
            $saldoGeneradoCentavos = $montoTotalCentavos
                - ($montoMensualCentavos * count($periodosSeleccionados));
            if ($saldoGeneradoCentavos < 0) {
                throw ValidationException::withMessages([
                    'periodos' => 'El pago total no alcanza para cubrir los meses seleccionados.',
                ]);
            }

            $this->validarPeriodosDisponibles(
                $afiliada,
                $periodosSeleccionados,
                $lote,
                $pagoBloqueado
            );

            $anteriores = [
                'monto_total' => $pagoBloqueado->monto_total,
                'monto_mensual' => $pagoBloqueado->monto_mensual,
                'periodos' => $pagoBloqueado->detalles()
                    ->with('periodo')
                    ->get()
                    ->map(fn (DevengadoPagoDetalle $detalle): array => [
                        'year' => $detalle->periodo->year,
                        'mes' => $detalle->periodo->mes,
                        'monto' => $detalle->periodo->monto,
                        'metodo_determinacion' => $detalle->periodo->metodo_determinacion,
                        'observaciones' => $detalle->periodo->observaciones,
                    ])
                    ->all(),
            ];

            $detalleAnteriorIds = $pagoBloqueado->detalles()
                ->pluck('devengado_periodo_id')
                ->all();
            DevengadoPeriodo::query()
                ->whereIn('id', $detalleAnteriorIds)
                ->whereNotIn('id', $this->idsPeriodosSeleccionados(
                    $lote,
                    $afiliada,
                    $periodosSeleccionados
                ))
                ->update([
                    'estado' => 'ANULADO',
                    'observacion_revision' => 'Desvinculado al editar el pago antes de la verificación.',
                ]);
            $pagoBloqueado->detalles()->delete();

            $pagoBloqueado->update([
                'monto_total' => $montoTotalCentavos / 100,
                'monto_mensual' => $montoMensualCentavos / 100,
                'saldo_generado' => $saldoGeneradoCentavos / 100,
                'cantidad_periodos' => count($periodosSeleccionados),
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            $periodoIds = $this->guardarPeriodosDelPago(
                $pagoBloqueado,
                $lote,
                $afiliada,
                $periodosSeleccionados,
                $montoMensualCentavos,
                $datos['metodo_determinacion'],
                $datos['observaciones'] ?? null
            );

            $this->auditoria->registrar(
                $usuario->id,
                'ACTUALIZAR',
                'devengado_pagos',
                $pagoBloqueado->id,
                $anteriores,
                [
                    'monto_total' => $pagoBloqueado->monto_total,
                    'monto_mensual' => $pagoBloqueado->monto_mensual,
                    'periodo_ids' => $periodoIds,
                    'periodos' => $periodosSeleccionados,
                    'metodo_determinacion' => $datos['metodo_determinacion'],
                    'observaciones' => $datos['observaciones'] ?? null,
                ]
            );
        });

        return redirect()
            ->route('devengados.show', $pago->devengado_lote_id)
            ->with('success', 'El pago devengado fue actualizado.');
    }

    public function submit(DevengadoLote $lote)
    {
        Gate::authorize('submit', $lote);
        $usuario = Auth::user();

        DB::transaction(function () use ($lote, $usuario): void {
            $bloqueado = DevengadoLote::query()
                ->whereKey($lote->id)
                ->lockForUpdate()
                ->firstOrFail();
            Gate::authorize('submit', $bloqueado);

            if (! $bloqueado->pagos()->where('estado', 'EN_LOTE')->exists()) {
                throw ValidationException::withMessages([
                    'lote' => 'No se puede enviar un lote sin pagos pendientes.',
                ]);
            }

            $estadoAnterior = $bloqueado->estado;
            $bloqueado->update([
                'estado' => 'ENVIADO',
                'enviado_por' => $usuario->id,
                'fecha_envio' => now(),
            ]);
            $this->auditoria->registrar(
                $usuario->id,
                'ENVIAR',
                'devengado_lotes',
                $bloqueado->id,
                ['estado' => $estadoAnterior],
                ['estado' => 'ENVIADO']
            );
        });

        return back()->with('success', 'El lote devengado fue enviado a revisión central.');
    }

    public function excel(DevengadoLote $lote): BinaryFileResponse
    {
        Gate::authorize('viewReport', $lote);

        $lote->load([
            'colegio',
            'pagos' => fn ($query) => $query
                ->with(['afiliado', 'detalles.periodo'])
                ->orderBy('id'),
        ]);

        $filas = $lote->pagos
            ->map(function (DevengadoPago $pago) use ($lote): array {
                $periodos = $pago->detalles
                    ->sortBy(fn (DevengadoPagoDetalle $detalle): int => (
                        $detalle->periodo->year * 100
                    ) + $detalle->periodo->mes)
                    ->map(fn (DevengadoPagoDetalle $detalle): string => str_pad(
                            (string) $detalle->periodo->mes,
                            2,
                            '0',
                            STR_PAD_LEFT
                        ).'/'.$detalle->periodo->year)
                    ->implode(', ');

                return [
                    'codigo_lote' => $lote->codigo_lote,
                    'colegio' => $lote->colegio->nombre,
                    'periodo_lote' => str_pad(
                        (string) $lote->mes,
                        2,
                        '0',
                        STR_PAD_LEFT
                    ).'/'.$lote->year,
                    'estado_lote' => str_replace('_', ' ', $lote->estado),
                    'afiliada' => trim(
                        $pago->afiliado->apellidos.', '.$pago->afiliado->nombres
                    ),
                    'ci' => $pago->afiliado->ci,
                    'fecha_pago' => $pago->fecha_pago?->format('d/m/Y H:i'),
                    'monto_total' => (float) $pago->monto_total,
                    'monto_mensual' => (float) $pago->monto_mensual,
                    'periodos' => $periodos !== ''
                        ? $periodos
                        : 'Sin períodos; pago registrado como saldo',
                    'saldo_generado' => (float) $pago->saldo_generado,
                    'estado_pago' => str_replace('_', ' ', $pago->estado),
                    'observaciones' => $pago->observaciones,
                ];
            })
            ->all();

        return Excel::download(
            new ReporteDevengadoLoteExport($filas),
            "reporte-lote-devengado-{$lote->id}.xlsx"
        );
    }

    public function show(DevengadoLote $lote)
    {
        Gate::authorize('view', $lote);

        $usuario = Auth::user();
        if (
            $usuario->rol?->nombre === 'ADMIN'
            && $lote->estado === 'ENVIADO'
        ) {
            DB::transaction(function () use ($lote, $usuario): void {
                $bloqueado = DevengadoLote::query()
                    ->whereKey($lote->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                Gate::authorize('review', $bloqueado);

                if ($bloqueado->estado === 'ENVIADO') {
                    $bloqueado->update([
                        'estado' => 'EN_VERIFICACION',
                        'revisado_por' => $usuario->id,
                        'fecha_revision' => now(),
                    ]);
                    $this->auditoria->registrar(
                        $usuario->id,
                        'INICIAR_REVISION',
                        'devengado_lotes',
                        $bloqueado->id,
                        ['estado' => 'ENVIADO'],
                        ['estado' => 'EN_VERIFICACION']
                    );
                }
            });
            $lote->refresh();
        }

        $lote->load([
            'colegio',
            'creadoPor',
            'periodos.afiliado',
            'periodos.exoneraciones.registradoPor',
            'periodos.pagosDetalles',
            'pagos' => fn ($query) => $query
                ->with([
                    'afiliado',
                    'registradoPor',
                    'detalles.periodo',
                ])
                ->orderBy('id'),
        ]);
        $pagosParaRevision = $lote->pagos
            ->where('estado', 'EN_LOTE')
            ->map(fn (DevengadoPago $pago): array => [
                'id' => $pago->id,
                'nombre' => $pago->afiliado->apellidos.', '.$pago->afiliado->nombres,
                'ci' => $pago->afiliado->ci,
                'monto_total' => $pago->monto_total,
                'monto_mensual' => $pago->monto_mensual,
                'saldo_generado' => $pago->saldo_generado,
                'periodos' => $pago->detalles
                    ->map(fn (DevengadoPagoDetalle $detalle): array => [
                        'year' => $detalle->periodo->year,
                        'mes' => $detalle->periodo->mes,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
        if (
            $usuario->rol?->nombre === 'USUARIO'
            && ! in_array($lote->estado, ['EN_VERIFICACION', 'VERIFICADO'], true)
        ) {
            $lote->pagos
                ->where('estado', 'EN_LOTE')
                ->each(fn (DevengadoPago $pago) => $this->prepararDatosEdicion($pago));
        }
        $devengadoService = $this->devengadoService;

        return view('devengados.show', compact(
            'lote',
            'pagosParaRevision',
            'devengadoService'
        ));
    }

    public function confirmarPagos(Request $request, DevengadoLote $lote)
    {
        Gate::authorize('process', $lote);
        $datos = $request->validate([
            'pago_ids' => ['required', 'array', 'min:1'],
            'pago_ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $usuario = Auth::user();
        $pagoIds = array_map('intval', $datos['pago_ids']);
        if (count(array_unique($pagoIds)) !== count($pagoIds)) {
            throw ValidationException::withMessages([
                'pago_ids' => 'No se puede seleccionar dos veces el mismo pago.',
            ]);
        }

        DB::transaction(function () use ($lote, $usuario, $pagoIds): void {
            $loteBloqueado = DevengadoLote::query()
                ->whereKey($lote->id)
                ->lockForUpdate()
                ->firstOrFail();
            Gate::authorize('process', $loteBloqueado);

            $pagos = $loteBloqueado->pagos()
                ->whereIn('id', $pagoIds)
                ->where('estado', 'EN_LOTE')
                ->with(['afiliado', 'detalles.periodo'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            if ($pagos->count() !== count($pagoIds)) {
                throw ValidationException::withMessages([
                    'pago_ids' => 'Uno o más pagos ya no están pendientes en este lote.',
                ]);
            }
            $periodoIds = $pagos
                ->flatMap(fn (DevengadoPago $pago) => $pago->detalles->pluck('devengado_periodo_id'));
            if ($periodoIds->count() !== $periodoIds->unique()->count()) {
                throw ValidationException::withMessages([
                    'pago_ids' => 'Un período aparece distribuido en más de un pago.',
                ]);
            }

            $afiliadaIds = $pagos->pluck('afiliado_id')->unique()->sort();
            $afiliadasBloqueadas = Afiliado::query()
                ->whereIn('id', $afiliadaIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($pagos as $pago) {
                $afiliada = $afiliadasBloqueadas->get($pago->afiliado_id);
                if (! $afiliada) {
                    throw ValidationException::withMessages([
                        'pago_ids' => 'No se encontró la afiliada asociada a uno de los pagos.',
                    ]);
                }
                if ($afiliada->colegio_id !== $loteBloqueado->colegio_id) {
                    throw ValidationException::withMessages([
                        'pago_ids' => 'Una afiliada no pertenece al colegio del lote devengado.',
                    ]);
                }
                if (! $this->fechaAfiliacionValida($afiliada)) {
                    throw ValidationException::withMessages([
                        'pago_ids' => $this->mensajeFechaAfiliacionInvalida(),
                    ]);
                }

                $detalles = $pago->detalles;
                if ($pago->monto_mensual === null) {
                    throw ValidationException::withMessages([
                        'pago_ids' => "El pago {$pago->id} no tiene registrado el aporte mensual.",
                    ]);
                }
                $montoMensualCentavos = $this->aCentavos($pago->monto_mensual);
                $montoTotalEsperado = ($montoMensualCentavos * $detalles->count())
                    + $this->aCentavos($pago->saldo_generado);
                if (
                    $detalles->count() !== (int) $pago->cantidad_periodos
                    || $montoMensualCentavos <= 0
                    || $montoTotalEsperado !== $this->aCentavos($pago->monto_total)
                ) {
                    throw ValidationException::withMessages([
                        'pago_ids' => "El pago {$pago->id} no tiene una distribución válida.",
                    ]);
                }
                $fechaAfiliacion = Carbon::parse($afiliada->fecha_afiliacion)
                    ->startOfMonth();

                foreach ($detalles as $detalle) {
                    $periodo = $detalle->periodo;
                    $fechaPeriodo = Carbon::create($periodo->year, $periodo->mes, 1);
                    if (
                        $periodo->estado !== 'EN_LOTE'
                        || $periodo->devengado_lote_id !== $loteBloqueado->id
                        || $periodo->afiliado_id !== $pago->afiliado_id
                        || $fechaPeriodo->lessThan($fechaAfiliacion)
                        || $fechaPeriodo->greaterThan(now()->startOfMonth())
                        || $this->aCentavos($detalle->monto) !== $montoMensualCentavos
                        || $this->aCentavos($periodo->monto) !== $montoMensualCentavos
                    ) {
                        throw ValidationException::withMessages([
                            'pago_ids' => 'Un pago contiene un período inconsistente o que ya no está disponible.',
                        ]);
                    }

                    $existente = Aporte::query()
                        ->where('afiliado_id', $periodo->afiliado_id)
                        ->where('mes', $periodo->mes)
                        ->where('year', $periodo->year)
                        ->first();
                    if ($existente) {
                        throw ValidationException::withMessages([
                            'pago_ids' => "Ya existe un aporte para {$periodo->mes}/{$periodo->year} (estado {$existente->estado}); no se modificó el historial.",
                        ]);
                    }
                }
            }

            foreach ($pagos as $pago) {
                $ahora = now();
                $afiliada = $afiliadasBloqueadas->get($pago->afiliado_id);
                if (! $afiliada) {
                    throw ValidationException::withMessages([
                        'pago_ids' => 'No se encontró la afiliada asociada a uno de los pagos.',
                    ]);
                }

                foreach ($pago->detalles as $detalle) {
                    $periodo = $detalle->periodo;
                    $aporte = Aporte::create([
                        'afiliado_id' => $periodo->afiliado_id,
                        'colegio_id' => $afiliada->colegio_id,
                        'pago_id' => null,
                        'mes' => $periodo->mes,
                        'year' => $periodo->year,
                        'monto' => $periodo->monto,
                        'estado' => 'VERIFICADO',
                        'observaciones' => null,
                        'registrado_por' => $usuario->id,
                        'fecha_registro' => $ahora,
                        'verificado_por' => $usuario->id,
                        'fecha_verificacion' => $ahora,
                    ]);
                    $periodo->update([
                        'estado' => 'REGISTRADO',
                        'revisado_por' => $usuario->id,
                        'fecha_revision' => $ahora,
                        'aporte_id' => $aporte->id,
                    ]);
                }

                $saldoCentavos = $this->aCentavos($pago->saldo_generado);
                if ($saldoCentavos > 0) {
                    $saldo = Saldo::create([
                        'afiliado_id' => $pago->afiliado_id,
                        'pago_id' => null,
                        'monto' => $saldoCentavos / 100,
                        'monto_disponible' => $saldoCentavos / 100,
                        'estado' => 'ACTIVO',
                        'observacion' => "Saldo remanente del pago devengado {$pago->id}, lote {$loteBloqueado->codigo_lote}.",
                    ]);
                    $this->auditoria->registrar(
                        $usuario->id,
                        'CREAR',
                        'saldos',
                        $saldo->id,
                        null,
                        [
                            'afiliado_id' => $saldo->afiliado_id,
                            'devengado_pago_id' => $pago->id,
                            'monto' => $saldo->monto,
                        ]
                    );
                }

                $pago->update([
                    'estado' => 'VERIFICADO',
                    'verificado_por' => $usuario->id,
                    'fecha_verificacion' => $ahora,
                ]);
                $this->auditoria->registrar(
                    $usuario->id,
                    'VERIFICAR',
                    'devengado_pagos',
                    $pago->id,
                    ['estado' => 'EN_LOTE'],
                    [
                        'estado' => 'VERIFICADO',
                        'aporte_ids' => $pago->detalles
                            ->map(fn (DevengadoPagoDetalle $detalle): int => (int) $detalle->periodo->aporte_id)
                            ->all(),
                        'saldo_generado' => $pago->saldo_generado,
                    ]
                );
            }

            $pendientes = $loteBloqueado->pagos()
                ->where('estado', 'EN_LOTE')
                ->exists();
            $estadoNuevo = $pendientes ? 'EN_VERIFICACION' : 'VERIFICADO';
            $loteBloqueado->update([
                'estado' => $estadoNuevo,
                'revisado_por' => $usuario->id,
                'fecha_revision' => now(),
            ]);
            $this->auditoria->registrar(
                $usuario->id,
                $pendientes ? 'CONTINUAR_REVISION' : 'VERIFICAR',
                'devengado_lotes',
                $loteBloqueado->id,
                null,
                ['estado' => $estadoNuevo]
            );
        });

        return redirect()
            ->route('lotes.index')
            ->with('success', 'Los pagos seleccionados fueron verificados y sus aportes registrados.');
    }

    public function exonerar(Request $request, DevengadoPeriodo $periodo)
    {
        Gate::authorize('exonerate', $periodo);
        $datos = $request->validate([
            'monto' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:99999999.99'],
            'motivo' => ['required', 'string', 'max:2000'],
            'presidente_nombre' => ['required', 'string', 'max:200'],
            'autorizacion_referencia' => ['required', 'string', 'max:200'],
        ]);
        $usuario = Auth::user();

        DB::transaction(function () use ($periodo, $datos, $usuario): void {
            $bloqueado = DevengadoPeriodo::query()
                ->whereKey($periodo->id)
                ->lockForUpdate()
                ->firstOrFail();
            Gate::authorize('exonerate', $bloqueado);

            $montoCentavos = $this->aCentavos($datos['monto']);
            if ($montoCentavos > $this->devengadoService->saldoPendiente($bloqueado)) {
                throw ValidationException::withMessages([
                    'monto' => 'El importe exonerado supera el saldo pendiente.',
                ]);
            }

            $exoneracion = DevengadoExoneracion::create([
                'devengado_periodo_id' => $bloqueado->id,
                'monto' => $montoCentavos / 100,
                'motivo' => trim($datos['motivo']),
                'presidente_nombre' => trim($datos['presidente_nombre']),
                'autorizacion_referencia' => trim($datos['autorizacion_referencia']),
                'registrado_por' => $usuario->id,
                'fecha_exoneracion' => now(),
            ]);
            $this->auditoria->registrar(
                $usuario->id,
                'EXONERAR',
                'devengado_exoneraciones',
                $exoneracion->id,
                null,
                [
                    'periodo_id' => $bloqueado->id,
                    'monto' => $exoneracion->monto,
                    'motivo' => $exoneracion->motivo,
                    'presidente_nombre' => $exoneracion->presidente_nombre,
                    'autorizacion_referencia' => $exoneracion->autorizacion_referencia,
                ]
            );
        });

        return back()->with('success', 'La exoneración autorizada quedó registrada y no se contabilizó como pago.');
    }

    private function prepararDatosEdicion(DevengadoPago $pago): void
    {
        $pago->setAttribute(
            'gestiones_edicion',
            $this->matrizPeriodos($pago->afiliado, $pago->id)
        );
        $pago->setAttribute(
            'seleccionados_edicion',
            $pago->detalles
                ->map(fn (DevengadoPagoDetalle $detalle): array => [
                    'year' => $detalle->periodo->year,
                    'mes' => $detalle->periodo->mes,
                ])
                ->values()
                ->all()
        );
    }

    /** @return array<int, array{year:int, meses:array<int, array{mes:int, disponible:bool, estado:string}>}> */
    private function matrizPeriodos(
        Afiliado $afiliada,
        ?int $pagoActualId = null
    ): array {
        if (! $this->fechaAfiliacionValida($afiliada)) {
            return [];
        }

        $aportes = Aporte::query()
            ->where('afiliado_id', $afiliada->id)
            ->get(['mes', 'year'])
            ->mapWithKeys(fn (Aporte $aporte): array => [
                $this->clavePeriodo((int) $aporte->year, (int) $aporte->mes) => true,
            ]);

        $consultaPeriodos = DevengadoPeriodo::query()
            ->where('afiliado_id', $afiliada->id)
            ->whereNotIn('estado', ['ANULADO', 'RECHAZADO']);

        if ($pagoActualId !== null) {
            $consultaPeriodos->with('pagosDetalles');
        }

        $periodosOcupados = $consultaPeriodos
            ->get(['id', 'mes', 'year']);
        $ocupados = [];
        $periodosDelPagoActual = [];
        foreach ($periodosOcupados as $periodo) {
            $key = $this->clavePeriodo($periodo->year, $periodo->mes);
            $esDelPagoActual = $pagoActualId !== null
                && $periodo->pagosDetalles->contains(
                    fn (DevengadoPagoDetalle $detalle): bool => (int) $detalle->devengado_pago_id === $pagoActualId
                );
            if ($esDelPagoActual) {
                $periodosDelPagoActual[$key] = true;
            } else {
                $ocupados[$key] = true;
            }
        }

        $inicio = Carbon::parse($afiliada->fecha_afiliacion)->startOfMonth();
        $fin = now()->startOfMonth();
        $gestiones = [];

        while ($inicio->lessThanOrEqualTo($fin)) {
            $year = $inicio->year;
            $mes = $inicio->month;
            $key = $this->clavePeriodo($year, $mes);
            $registrado = isset($aportes[$key]);
            $ocupado = isset($ocupados[$key]);
            $seleccionable = ! $ocupado
                && (! $registrado || isset($periodosDelPagoActual[$key]));
            $gestiones[$year] ??= ['year' => $year, 'meses' => []];
            $gestiones[$year]['meses'][] = [
                'mes' => $mes,
                'disponible' => $seleccionable,
                'estado' => $registrado
                    ? 'REGISTRADO'
                    : ($ocupado ? 'EN_LOTE' : 'LAGUNA'),
            ];
            $inicio->addMonth();
        }

        return array_values($gestiones);
    }

    /**
     * @param  array<int, string>  $periodos
     * @return array<int, array{year:int, mes:int}>
     */
    private function parsearPeriodos(
        array $periodos,
        int $montoMensualCentavos,
        int $montoTotalCentavos
    ): array {
        $normalizados = [];
        foreach ($periodos as $periodo) {
            [$year, $mes] = array_map('intval', explode('-', $periodo));
            if (! checkdate($mes, 1, $year)) {
                throw ValidationException::withMessages([
                    'periodos' => 'Uno de los períodos seleccionados no es válido.',
                ]);
            }
            $fecha = Carbon::create($year, $mes, 1);
            if ($fecha->greaterThan(now()->startOfMonth())) {
                throw ValidationException::withMessages([
                    'periodos' => 'Solo se pueden cubrir períodos anteriores o iguales al mes actual.',
                ]);
            }
            $normalizados[] = ['year' => $year, 'mes' => $mes];
        }

        if ($montoMensualCentavos * count($normalizados) > $montoTotalCentavos) {
            throw ValidationException::withMessages([
                'monto_total' => 'El total pagado debe cubrir los meses seleccionados.',
            ]);
        }

        return $normalizados;
    }

    /**
     * @param  array<int, array{year:int, mes:int}>  $periodos
     */
    private function validarPeriodosDisponibles(
        Afiliado $afiliada,
        array $periodos,
        DevengadoLote $lote,
        ?DevengadoPago $pagoActual = null
    ): void {
        $afiliacion = Carbon::parse($afiliada->fecha_afiliacion)->startOfMonth();
        foreach ($periodos as $periodo) {
            $fecha = Carbon::create($periodo['year'], $periodo['mes'], 1);
            if ($fecha->lessThan($afiliacion)) {
                throw ValidationException::withMessages([
                    'periodos' => 'No se puede regularizar un período anterior a la fecha de afiliación.',
                ]);
            }

            $aporteExistente = Aporte::query()
                ->where('afiliado_id', $afiliada->id)
                ->where('mes', $periodo['mes'])
                ->where('year', $periodo['year'])
                ->first();
            if ($aporteExistente) {
                throw ValidationException::withMessages([
                    'periodos' => "Ya existe un aporte para {$periodo['mes']}/{$periodo['year']} ({$aporteExistente->estado}); no se modificó el historial.",
                ]);
            }

            $ocupado = DevengadoPeriodo::query()
                ->where('afiliado_id', $afiliada->id)
                ->where('mes', $periodo['mes'])
                ->where('year', $periodo['year'])
                ->whereNotIn('estado', ['ANULADO', 'RECHAZADO'])
                ->where('devengado_lote_id', '!=', $lote->id)
                ->exists();
            if ($ocupado) {
                throw ValidationException::withMessages([
                    'periodos' => "El período {$periodo['mes']}/{$periodo['year']} ya está reservado en otro lote devengado.",
                ]);
            }

            $actual = DevengadoPeriodo::query()
                ->where('devengado_lote_id', $lote->id)
                ->where('afiliado_id', $afiliada->id)
                ->where('mes', $periodo['mes'])
                ->where('year', $periodo['year'])
                ->first();
            if (
                $actual
                && $actual->estado !== 'ANULADO'
                && (
                    $pagoActual === null
                    || ! $pagoActual->detalles()
                        ->where('devengado_periodo_id', $actual->id)
                        ->exists()
                )
            ) {
                throw ValidationException::withMessages([
                    'periodos' => "El período {$periodo['mes']}/{$periodo['year']} ya está reservado en este lote.",
                ]);
            }
        }
    }

    /**
     * @param  array<int, array{year:int, mes:int}>  $periodos
     * @return array<int, int>
     */
    private function guardarPeriodosDelPago(
        DevengadoPago $pago,
        DevengadoLote $lote,
        Afiliado $afiliada,
        array $periodos,
        int $montoMensualCentavos,
        string $metodo,
        ?string $observaciones
    ): array {
        $ids = [];
        foreach ($periodos as $periodoData) {
            $periodo = DevengadoPeriodo::query()
                ->where('devengado_lote_id', $lote->id)
                ->where('afiliado_id', $afiliada->id)
                ->where('mes', $periodoData['mes'])
                ->where('year', $periodoData['year'])
                ->first();

            if ($periodo && $periodo->estado === 'ANULADO') {
                $periodo->update([
                    'monto' => $montoMensualCentavos / 100,
                    'metodo_determinacion' => $metodo,
                    'observaciones' => $observaciones,
                    'estado' => 'EN_LOTE',
                    'observacion_revision' => null,
                    'revisado_por' => null,
                    'fecha_revision' => null,
                    'aporte_id' => null,
                ]);
            } elseif (! $periodo) {
                $periodo = $lote->periodos()->create([
                    'afiliado_id' => $afiliada->id,
                    'mes' => $periodoData['mes'],
                    'year' => $periodoData['year'],
                    'monto' => $montoMensualCentavos / 100,
                    'metodo_determinacion' => $metodo,
                    'observaciones' => $observaciones,
                    'estado' => 'EN_LOTE',
                ]);
            }

            DevengadoPagoDetalle::create([
                'devengado_pago_id' => $pago->id,
                'devengado_periodo_id' => $periodo->id,
                'monto' => $montoMensualCentavos / 100,
            ]);
            $ids[] = $periodo->id;
        }

        return $ids;
    }

    /**
     * @param  array<int, array{year:int, mes:int}>  $periodos
     * @return array<int, int>
     */
    private function idsPeriodosSeleccionados(
        DevengadoLote $lote,
        Afiliado $afiliada,
        array $periodos
    ): array {
        if ($periodos === []) {
            return [];
        }

        return $lote->periodos()
            ->where('afiliado_id', $afiliada->id)
            ->where(function ($query) use ($periodos): void {
                foreach ($periodos as $periodo) {
                    $query->orWhere(function ($subQuery) use ($periodo): void {
                        $subQuery->where('year', $periodo['year'])
                            ->where('mes', $periodo['mes']);
                    });
                }
            })
            ->pluck('id')
            ->all();
    }

    private function validarAfiliadaEnLote(
        Afiliado $afiliada,
        DevengadoLote $lote,
        Usuario $usuario
    ): void {
        if (! $this->fechaAfiliacionValida($afiliada)) {
            throw ValidationException::withMessages([
                'afiliado_id' => $this->mensajeFechaAfiliacionInvalida(),
            ]);
        }

        if ($afiliada->estado !== 'ACTIVO') {
            throw ValidationException::withMessages([
                'afiliado_id' => 'No se puede registrar un pago para una afiliada inactiva.',
            ]);
        }
        if ($afiliada->colegio_id !== $lote->colegio_id) {
            throw ValidationException::withMessages([
                'afiliado_id' => 'La afiliada no pertenece al colegio del lote.',
            ]);
        }
        if (! in_array($afiliada->colegio_id, $this->colegiosPermitidos($usuario), true)) {
            abort(403);
        }
    }

    /** @return array<int, int> */
    private function colegiosPermitidos(Usuario $usuario): array
    {
        $colegio = $usuario->colegio;
        if (! $colegio) {
            return [];
        }

        $ids = [$colegio->id];
        if ($colegio->tipo === 'DEPARTAMENTAL') {
            $ids = array_merge(
                $ids,
                $colegio->hijos()
                    ->where('estado', 'ACTIVO')
                    ->pluck('id')
                    ->all()
            );
        }

        return array_map('intval', $ids);
    }

    private function fechaAfiliacionValida(Afiliado $afiliada): bool
    {
        $fechaOriginal = $afiliada->getRawOriginal('fecha_afiliacion');
        if (
            ! is_string($fechaOriginal)
            || preg_match(
                '/^(\d{4})-(\d{2})-(\d{2})(?:$|[ T])/',
                $fechaOriginal,
                $partes
            ) !== 1
        ) {
            return false;
        }

        $year = (int) $partes[1];
        $month = (int) $partes[2];
        $day = (int) $partes[3];
        $fecha = substr($fechaOriginal, 0, 10);

        return $year >= 1900
            && checkdate($month, $day, $year)
            && $fecha <= now()->toDateString();
    }

    private function mensajeFechaAfiliacionInvalida(): string
    {
        return 'La fecha de afiliación no es válida. Corrígela en los datos de la afiliada antes de registrar pagos devengados.';
    }

    private function clavePeriodo(int $year, int $mes): string
    {
        return $year.'-'.str_pad((string) $mes, 2, '0', STR_PAD_LEFT);
    }

    private function aCentavos(string|int|float $monto): int
    {
        $partes = explode('.', number_format((float) $monto, 2, '.', ''));

        return ((int) $partes[0] * 100) + (int) ($partes[1] ?? 0);
    }
}
