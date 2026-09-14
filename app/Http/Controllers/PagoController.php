<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Afiliado;
use App\Models\LoteVerificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

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

        $afiliado = Afiliado::findOrFail($datos['afiliado_id']);

        Gate::authorize('store', [Pago::class, $afiliado]);

        /*Validar estado del afiliado*/

        if ($afiliado->estado !== 'ACTIVO') {
            return back()
                ->withErrors([
                    'afiliado_id' => 'No se puede registrar un pago para un afiliado inactivo.',
                ])
                ->withInput();
        }

        /*Calcular cantidad de períodos*/

        $montoMensualCentavos = (int) round($datos['monto_mensual'] * 100);
        $montoTotalCentavos = (int) round($datos['monto_total'] * 100);

        // ...
    }

    public function rechazar(Request $request, Pago $pago)
    {
        $datos = $request->validate([
            'observaciones' => ['required', 'string', 'max:1000'],
        ]);

        $usuario = Auth::user();

        Gate::authorize('rechazar', $pago);

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