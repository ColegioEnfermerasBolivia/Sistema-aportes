<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Aporte;
use App\Models\LoteVerificacion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LoteVerificacionController extends Controller
{
    public function registrarAportes(LoteVerificacion $lote)
    {
        $usuario = Auth::user();

        /*Validar rol*/

        if ($usuario->rol_id !== 2) {
            return back()
                ->withErrors([
                    'lote' => 'No tienes permiso para registrar aportes.',
                ]);
        }

        /*Validar estado del lote*/

        if ($lote->estado !== 'EN_VERIFICACION') {
            return back()
                ->withErrors([
                    'lote' => 'El lote debe encontrarse en estado EN_VERIFICACION para registrar los aportes.',
                ]);
        }

        DB::transaction(function () use ($lote, $usuario) {

            $pagos = $lote->pagos()
                ->where('estado', 'VERIFICADO')
                ->with('afiliado')
                ->get();

            foreach ($pagos as $pago) {

                /*Evitar procesar nuevamente un pago*/

                if ($pago->aportes()->exists()) {
                    continue;
                }

                $afiliado = $pago->afiliado;

                /*Determinar primer período a registrar*/

                $ultimoAporte = $afiliado->aportes()
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

                /*Calcular monto por período*/

                $montoPorPeriodo = $pago->monto_total / $pago->cantidad_periodos;

                /*Generar aportes*/

                for ($i = 0; $i < $pago->cantidad_periodos; $i++) {

                    $fechaPeriodo = $fechaInicio->copy()->addMonths($i);

                    Aporte::create([
                        'afiliado_id' => $afiliado->id,
                        'colegio_id' => $afiliado->colegio_id,
                        'pago_id' => $pago->id,
                        'mes' => $fechaPeriodo->month,
                        'year' => $fechaPeriodo->year,
                        'monto' => $montoPorPeriodo,
                        'estado' => 'VERIFICADO',
                        'observaciones' => null,
                        'registrado_por' => $pago->registrado_por,
                        'fecha_registro' => now(),
                        'verificado_por' => $usuario->id,
                        'fecha_verificacion' => now(),
                    ]);
                }
            }
        });

        return back()->with(
            'success',
            'Los aportes del lote fueron registrados correctamente.'
        );
    }
}
