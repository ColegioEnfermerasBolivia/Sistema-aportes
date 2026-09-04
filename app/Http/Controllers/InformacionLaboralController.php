<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use App\Models\InformacionLaboral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;


class InformacionLaboralController extends Controller
{
    public function index(Afiliado $afiliado)
    {
        Gate::authorize('viewLaboral', $afiliado);

        $informacionesLaborales = $afiliado
            ->informacionesLaborales()
            ->orderByDesc('fecha_ingreso_trabajo')
            ->get();

        return view(
            'informacion-laboral.index',
            compact(
                'afiliado',
                'informacionesLaborales'
            )
        );
    }

    public function store(
        Request $request,
        Afiliado $afiliado
    ) {
        Gate::authorize('createLaboral', $afiliado);

        $trabajoActivo = $afiliado
            ->informacionesLaborales()
            ->where('culminado', false)
            ->exists();

        if ($trabajoActivo) {
            return redirect()
                ->back()
                ->with(
                    'error',
                    'El afiliado ya tiene un trabajo activo. Debe culminarlo antes de registrar otro.'
                );
        }

        $datos = $request->validate([
            'institucion' => [
                'required',
                'string',
                'max:150',
            ],

            'tipo_contrato' => [
                'required',
                'string',
                'max:100',
            ],

            'haber_basico' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'fecha_ingreso_trabajo' => [
                'required',
                'date',
            ],
        ]);

        $datos['afiliado_id'] = $afiliado->id;

        $datos['culminado'] = false;

        $datos['fecha_retiro'] = null;

        $datos['monto_aporte'] = round(
            $datos['haber_basico'] * 0.01,
            2
        );

        InformacionLaboral::create($datos);

        return redirect()
            ->route(
                'informacion-laboral.index',
                $afiliado
            )
            ->with(
                'success',
                'Información laboral registrada correctamente.'
            );
    }

    public function update(
        Request $request,
        InformacionLaboral $informacionLaboral
    ) {
        Gate::authorize(
            'updateLaboral',
            $informacionLaboral->afiliado
        );

        if ($informacionLaboral->culminado) {
            return redirect()
                ->back()
                ->with(
                    'error',
                    'Este registro laboral ya fue culminado y no puede modificarse.'
                );
        }

        $datos = $request->validate([
            'institucion' => [
                'required',
                'string',
                'max:150',
            ],

            'tipo_contrato' => [
                'required',
                'string',
                'max:100',
            ],

            'haber_basico' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'fecha_ingreso_trabajo' => [
                'required',
                'date',
            ],

            'culminado' => [
                'required',
                'boolean',
            ],

            'fecha_retiro' => [
                'nullable',
                'date',
                'required_if:culminado,1',
            ],
        ]);

        if (
            $datos['culminado'] &&
            $datos['fecha_retiro'] < $datos['fecha_ingreso_trabajo']
        ) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    'La fecha de retiro no puede ser anterior a la fecha de ingreso.'
                );
        }

        if (!$datos['culminado']) {
            $datos['fecha_retiro'] = null;
        }

        $datos['monto_aporte'] = round(
            $datos['haber_basico'] * 0.01,
            2
        );
        $informacionLaboral->update($datos);

        return redirect()
            ->route(
                'informacion-laboral.index',
                $informacionLaboral->afiliado_id
            )
            ->with(
                'success',
                'Información laboral actualizada correctamente.'
            );
    }
}