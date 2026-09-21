<?php

namespace App\Http\Controllers;

use App\Models\Aporte;
use App\Models\Afiliado;
use App\Models\LoteVerificacion;
use App\Models\Pago;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

class PagoController extends Controller
{
    public function __construct(
        private AuditoriaService $auditoria
    ) {}

    public function store(Request $request)
    {
        $datos = $request->validate([
            'afiliado_id' => [
                'required',
                'integer',
                'exists:afiliados,id',
            ],
            'monto_mensual' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'monto_total' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'observaciones' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $usuario = Auth::user();

        $afiliado = Afiliado::findOrFail(
            $datos['afiliado_id']
        );

        Gate::authorize(
            'store',
            [Pago::class, $afiliado]
        );

        if ($afiliado->estado !== 'ACTIVO') {
            return back()
                ->withErrors([
                    'afiliado_id' =>
                        'No se puede registrar un pago para un afiliado inactivo.',
                ])
                ->withInput();
        }

        $montoMensualCentavos = (int) round(
            $datos['monto_mensual'] * 100
        );

        $montoTotalCentavos = (int) round(
            $datos['monto_total'] * 100
        );

        if (
            $montoTotalCentavos % $montoMensualCentavos !== 0
        ) {
            return back()
                ->withErrors([
                    'monto_total' =>
                        'El monto total debe ser divisible exactamente entre el monto mensual.',
                ])
                ->withInput();
        }

        $cantidadPeriodos = intdiv(
            $montoTotalCentavos,
            $montoMensualCentavos
        );

        if ($cantidadPeriodos < 1) {
            return back()
                ->withErrors([
                    'monto_total' =>
                        'El pago debe corresponder al menos a un período.',
                ])
                ->withInput();
        }

        /*FLUJO USUARIO*/
        if ($usuario->rol?->nombre === 'USUARIO') {

            /*
             * Buscar el lote ABIERTO del colegio.
             */
            $lote = LoteVerificacion::query()
                ->where('colegio_id', $usuario->colegio_id)
                ->where('estado', 'ABIERTO')
                ->first();

            if (!$lote) {
                return back()
                    ->withErrors([
                        'pago' =>
                            'No existe un lote abierto para registrar el pago.',
                    ])
                    ->withInput();
            }

            if ($afiliado->colegio_id !== $lote->colegio_id) {
                return back()
                    ->withErrors([
                        'afiliado_id' =>
                            'El afiliado no pertenece al colegio del lote.',
                    ])
                    ->withInput();
            }

            $pagoExistente = Pago::query()
                ->where('lote_verificacion_id', $lote->id)
                ->where('afiliado_id', $afiliado->id)
                ->exists();

            if ($pagoExistente) {
                return back()
                    ->withErrors([
                        'afiliado_id' => 'Este afiliado ya tiene un pago registrado para este mes'
                    ])
                    ->withInput();
            }

            $pago = null;

            DB::transaction(function () use (
                $datos,
                $usuario,
                $afiliado,
                $lote,
                $cantidadPeriodos,
                $montoTotalCentavos,
                &$pago
            ) {
                $pago = Pago::create([
                    'afiliado_id' => $afiliado->id,
                    'lote_verificacion_id' => $lote->id,
                    'monto_total' => $montoTotalCentavos / 100,
                    'cantidad_periodos' => $cantidadPeriodos,
                    'estado' => 'EN_LOTE',
                    'observaciones' =>
                        $datos['observaciones'] ?? null,
                    'registrado_por' => $usuario->id,
                    'verificado_por' => null,
                    'fecha_verificacion' => null,
                ]);
                $lote->increment(
                    'cantidad_aportes',
                    $cantidadPeriodos
                );

                $lote->increment(
                    'monto_total',
                    $montoTotalCentavos / 100
                );
            });

            //Auditoria

            $this->auditoria->registrar(
                $usuario->id,
                'CREAR',
                'pagos',
                $pago->id,
                null,
                [
                    'afiliado_id' => $afiliado->id,
                    'lote_verificacion_id' => $lote->id,
                    'monto_total' => $pago->monto_total,
                    'cantidad_periodos' => $pago->cantidad_periodos,
                    'estado' => $pago->estado,
                    'observaciones' => $pago->observaciones,
                    'registrado_por' => $pago->registrado_por,
                    'verificado_por' => $pago->verificado_por,
                    'fecha_verificacion' => $pago->fecha_verificacion,
                ]
            );

            return back()->with(
                'success',
                'El pago fue registrado correctamente en el lote.'
            );
        }

        /*FLUJO ADMIN */
        if ($usuario->rol?->nombre === 'ADMIN') {

            try {
                $pago = null;
                $aportesGenerados = 0;

                DB::transaction(function () use (
                    $datos,
                    $usuario,
                    $afiliado,
                    $cantidadPeriodos,
                    $montoTotalCentavos,
                    &$pago,
                    &$aportesGenerados
                ) {

                    $pago = Pago::create([
                        'afiliado_id' => $afiliado->id,
                        'lote_verificacion_id' => null,
                        'monto_total' => $montoTotalCentavos / 100,
                        'cantidad_periodos' => $cantidadPeriodos,
                        'estado' => 'VERIFICADO',
                        'observaciones' =>
                            $datos['observaciones'] ?? null,
                        'registrado_por' => $usuario->id,
                        'verificado_por' => $usuario->id,
                        'fecha_verificacion' => now(),
                    ]);

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

                    if (
                        $montoTotalCentavos
                        % $cantidadPeriodos !== 0
                    ) {
                        throw new \Exception(
                            'El monto total no puede dividirse exactamente entre los períodos.'
                        );
                    }

                    $montoPorPeriodoCentavos = intdiv(
                        $montoTotalCentavos,
                        $cantidadPeriodos
                    );

                    for (
                        $i = 0;
                        $i < $cantidadPeriodos;
                        $i++
                    ) {
                        $fechaPeriodo = $fechaInicio
                            ->copy()
                            ->addMonths($i);

                        $existe = Aporte::query()
                            ->where(
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
                            'monto' =>
                                $montoPorPeriodoCentavos / 100,
                            'estado' => 'VERIFICADO',
                            'observaciones' => null,
                            'registrado_por' => $usuario->id,
                            'fecha_registro' => now(),
                            'verificado_por' => $usuario->id,
                            'fecha_verificacion' => now(),
                        ]);

                        $aportesGenerados++;
                    }
                });

                //Auditoria
                $this->auditoria->registrar(
                    $usuario->id,
                    'CREAR',
                    'pagos',
                    $pago->id,
                    null,
                    [
                        'afiliado_id' => $afiliado->id,
                        'lote_verificacion_id' => null,
                        'monto_total' => $pago->monto_total,
                        'cantidad_periodos' => $pago->cantidad_periodos,
                        'estado' => $pago->estado,
                        'observaciones' => $pago->observaciones,
                        'registrado_por' => $pago->registrado_por,
                        'verificado_por' => $pago->verificado_por,
                        'fecha_verificacion' => $pago->fecha_verificacion,
                        'aportes_generados' => $aportesGenerados,
                    ]
                );

                return back()->with(
                    'success',
                    'El pago fue registrado y verificado correctamente.'
                );

            } catch (\Exception $e) {

                return back()
                    ->withErrors([
                        'pago' => $e->getMessage(),
                    ])
                    ->withInput();
            }
        }
        abort(403);
    }

    public function update(
        Request $request,
        Pago $pago
    ) {
        Gate::authorize('update', $pago);

        $datos = $request->validate([
            'monto_mensual' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'monto_total' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'observaciones' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $montoMensualCentavos = (int) round(
            $datos['monto_mensual'] * 100
        );

        $montoTotalCentavos = (int) round(
            $datos['monto_total'] * 100
        );

        if (
            $montoTotalCentavos % $montoMensualCentavos !== 0
        ) {
            return back()
                ->withErrors([
                    'monto_total' =>
                        'El monto total debe ser divisible exactamente entre el monto mensual.',
                ])
                ->withInput();
        }

        $cantidadPeriodos = intdiv(
            $montoTotalCentavos,
            $montoMensualCentavos
        );

        if ($cantidadPeriodos < 1) {
            return back()
                ->withErrors([
                    'monto_total' =>
                        'El pago debe corresponder al menos a un período.',
                ])
                ->withInput();
        }

        $lote = $pago->loteVerificacion;

        //Datos Anteriores para Auditoria
        $datosAnteriores = [
            'monto_total' => $pago->monto_total,
            'cantidad_periodos' => $pago->cantidad_periodos,
            'observaciones' => $pago->observaciones,
        ];

        DB::transaction(function () use (
            $pago,
            $lote,
            $montoTotalCentavos,
            $cantidadPeriodos,
            $datos
        ) {

            $lote->decrement(
                'monto_total',
                $pago->monto_total
            );

            $lote->decrement(
                'cantidad_aportes',
                $pago->cantidad_periodos
            );

            $pago->update([
                'monto_total' =>
                    $montoTotalCentavos / 100,
                'cantidad_periodos' =>
                    $cantidadPeriodos,
                'observaciones' =>
                    $datos['observaciones'] ?? null,
            ]);

            $lote->increment(
                'monto_total',
                $montoTotalCentavos / 100
            );

            $lote->increment(
                'cantidad_aportes',
                $cantidadPeriodos
            );
        });

        $usuario = Auth::user();

        $this->auditoria->registrar(
            $usuario->id,
            'ACTUALIZAR',
            'pagos',
            $pago->id,
            $datosAnteriores,
            [
                'monto_total' => $pago->monto_total,
                'cantidad_periodos' => $pago->cantidad_periodos,
                'observaciones' => $pago->observaciones,
            ]
        );

        return back()->with(
            'success',
            'El pago fue actualizado correctamente.'
        );
    }
}
