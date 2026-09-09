<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use App\Models\InformacionLaboral;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;


class InformacionLaboralController extends Controller
{
    public function __construct(
        private AuditoriaService $auditoria
    ){}

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

        $informacionLaboral = InformacionLaboral::create($datos);

        $datosNuevos = [
            'afiliado_id' => $informacionLaboral->afiliado_id,
            'institucion' => $informacionLaboral->institucion,
            'tipo_contrato' => $informacionLaboral->tipo_contrato,
            'haber_basico' => $informacionLaboral->haber_basico,
            'monto_aporte' => $informacionLaboral->monto_aporte,
            'fecha_ingreso_trabajo' => $informacionLaboral->fecha_ingreso_trabajo,
            'culminado' => $informacionLaboral->culminado,
            'fecha_retiro' => $informacionLaboral->fecha_retiro,
        ];

        $this->auditoria->registrar(
            auth()->id(),
            'CREAR',
            'informacion_laboral',
            $informacionLaboral->id,
            null,
            $datosNuevos
        );

        return redirect()
            ->route(
                'afiliados.index',
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

        $datosAnteriores = [
            'afiliado_id' => $informacionLaboral->afiliado_id,
            'institucion' => $informacionLaboral->institucion,
            'tipo_contrato' => $informacionLaboral->tipo_contrato,
            'haber_basico' => $informacionLaboral->haber_basico,
            'monto_aporte' => $informacionLaboral->monto_aporte,
            'fecha_ingreso_trabajo' => $informacionLaboral->fecha_ingreso_trabajo,
            'culminado' => $informacionLaboral->culminado,
            'fecha_retiro' => $informacionLaboral->fecha_retiro,
        ];

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

        $datosNuevos = [
            'afiliado_id' => $informacionLaboral->afiliado_id,
            'institucion' => $informacionLaboral->institucion,
            'tipo_contrato' => $informacionLaboral->tipo_contrato,
            'haber_basico' => $informacionLaboral->haber_basico,
            'monto_aporte' => $informacionLaboral->monto_aporte,
            'fecha_ingreso_trabajo' => $informacionLaboral->fecha_ingreso_trabajo,
            'culminado' => $informacionLaboral->culminado,
            'fecha_retiro' => $informacionLaboral->fecha_retiro,
        ];

        if ($datosAnteriores !== $datosNuevos) {
            $this->auditoria->registrar(
                auth()->id(),
                'ACTUALIZAR',
                'informacion_laboral',
                $informacionLaboral->id,
                $datosAnteriores,
                $datosNuevos
            );
        }

        return redirect()
            ->route('afiliados.index')
            ->with(
                'success',
                'Información laboral actualizada correctamente.'
            );
    }
}