<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Afiliado;
use App\Models\LoteVerificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PagoController extends Controller
{
    public function store(Request $request)
    {
        $datos = $request->validate([
            'afiliado_id' => ['required', 'integer', 'exists:afiliados,id'],
            'monto_mensual' => ['required', 'numeric', 'gt:0'],
            'monto_total' => ['required', 'numeric', 'gt:0'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $usuario = Auth::user();

        /*Validar rol */

        if (!in_array($usuario->rol_id, [2, 3])) {
            return back()
                ->withErrors([
                    'afiliado_id' => 'No tienes permiso para registrar pagos.',
                ])
                ->withInput();
        }

        $afiliado = Afiliado::findOrFail($datos['afiliado_id']);

        /*Validar estado del afiliado*/

        if ($afiliado->estado !== 'ACTIVO') {
            return back()
                ->withErrors([
                    'afiliado_id' => 'No se puede registrar un pago para un afiliado inactivo.',
                ])
                ->withInput();
        }

        /*Validar alcance del USUARIO*/

        if ($usuario->rol_id === 3) {
            if ($usuario->colegio_id !== $afiliado->colegio_id) {
                return back()
                    ->withErrors([
                        'afiliado_id' => 'No tienes permiso para registrar pagos de este afiliado.',
                    ])
                    ->withInput();
            }
        }

        /*Calcular cantidad de períodos*/

        $montoMensualCentavos = (int) round($datos['monto_mensual'] * 100);
        $montoTotalCentavos = (int) round($datos['monto_total'] * 100);

        if ($montoTotalCentavos % $montoMensualCentavos !== 0) {
            return back()
                ->withErrors([
                    'monto_total' => 'El monto total debe ser divisible exactamente entre el monto mensual.',
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
                    'monto_total' => 'El monto total debe cubrir al menos un período.',
                ])
                ->withInput();
        }

        /*Determinar período del lote*/

        $ahora = now();

        $mes = $ahora->month;
        $year = $ahora->year;

        $colegio = $afiliado->colegio;

        /*Buscar lote del período*/

        $lote = LoteVerificacion::where('colegio_id', $colegio->id)
            ->where('mes', $mes)
            ->where('year', $year)
            ->first();

        /*Crear lote si todavía no existe*/

        if (!$lote) {
            $codigoLote = $colegio->codigo
                . '-' . $year
                . '-' . str_pad($mes, 2, '0', STR_PAD_LEFT);

            $lote = LoteVerificacion::create([
                'colegio_id' => $colegio->id,
                'mes' => $mes,
                'year' => $year,
                'codigo_lote' => $codigoLote,
                'cantidad_aportes' => 0,
                'monto_total' => 0,
                'estado' => 'ABIERTO',
            ]);
        }

        /*Verificar estado del lote*/

        if ($lote->estado !== 'ABIERTO') {
            return back()
                ->withErrors([
                    'afiliado_id' =>
                        'El lote correspondiente a este período ya fue cerrado y no permite registrar nuevos pagos.',
                ])
                ->withInput();
        }

        /*Registrar pago*/

        DB::transaction(function () use (
            $datos,
            $afiliado,
            $usuario,
            $cantidadPeriodos,
            $lote,
            $ahora
        ) {
            $pago = Pago::create([
                'afiliado_id' => $afiliado->id,
                'lote_verificacion_id' => $lote->id,
                'monto_total' => $datos['monto_total'],
                'cantidad_periodos' => $cantidadPeriodos,
                'estado' => 'EN_LOTE',
                'observaciones' => $datos['observaciones'] ?? null,
                'registrado_por' => $usuario->id,
                'creado_en' => $ahora,
            ]);

            $lote->increment(
                'cantidad_aportes',
                $cantidadPeriodos
            );

            $lote->increment(
                'monto_total',
                $datos['monto_total']
            );
        });

        return back()->with(
            'success',
            'Pago registrado correctamente.'
        );
    }

    public function rechazar(Request $request, Pago $pago)
    {
        $datos = $request->validate([
            'observaciones' => ['required', 'string', 'max:1000'],
        ]);

        $usuario = Auth::user();

        /*Validar rol*/

        if ($usuario->rol_id !== 2) {
            return back()
                ->withErrors([
                    'pago' => 'No tienes permiso para rechazar pagos.',
                ])
                ->withInput();
        }

        /*Validar estado del pago*/

        if ($pago->estado !== 'EN_LOTE') {
            return back()
                ->withErrors([
                    'pago' => 'Solo se pueden rechazar pagos que se encuentren en lote.',
                ])
                ->withInput();
        }

        /*Rechazar pago*/

        DB::transaction(function () use ($pago, $usuario, $datos) {
            $pago->update([
                'estado' => 'RECHAZADO',
                'verificado_por' => $usuario->id,
                'fecha_verificacion' => now(),
                'observaciones' => $datos['observaciones'],
            ]);
        });

        return back()->with(
            'success',
            'El pago fue rechazado correctamente.'
        );
    }

}