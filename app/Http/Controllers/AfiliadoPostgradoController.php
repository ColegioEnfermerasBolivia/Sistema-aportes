<?php

namespace App\Http\Controllers;

use App\Models\Afiliado;
use App\Models\AfiliadoPostgrado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AfiliadoPostgradoController extends Controller
{
    public function store(
        Request $request,
        Afiliado $afiliado
    ) {
        Gate::authorize(
            'createAcademic',
            $afiliado
        );

        $datos = $request->validate([
            'postgrado_id' => [
                'required',
                'integer',
                Rule::exists('postgrados', 'id')
                    ->where('estado', 'ACTIVO'),
            ],

            'grado_academico_id' => [
                'required',
                'integer',
                Rule::exists('grados_academicos', 'id')
                    ->where('estado', 'ACTIVO'),
            ],

            'institucion' => [
                'required',
                'string',
                'max:150',
            ],

            'numero_titulo' => [
                'required',
                'string',
                'max:50',
                'unique:afiliado_postgrado,numero_titulo',
            ],

            'fecha_titulo' => [
                'required',
                'date',
            ],

            'numero_certificado' => [
                'required',
                'string',
                'max:50',
                'unique:afiliado_postgrado,numero_certificado',
            ],
        ]);

        $yaExiste = AfiliadoPostgrado::where('afiliado_id', $afiliado->id)
            ->where('postgrado_id', $datos['postgrado_id'])
            ->where('grado_academico_id', $datos['grado_academico_id'])
            ->exists();

        if ($yaExiste) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    'El afiliado ya tiene registrado ese postgrado con ese grado académico.'
                );
        }

        $datos['afiliado_id'] = $afiliado->id;

        AfiliadoPostgrado::create($datos);

        return redirect()
            ->route('afiliados.index')
            ->with(
                'success',
                'Información académica registrada correctamente.'
            );
    }

    public function update(
        Request $request,
        AfiliadoPostgrado $afiliadoPostgrado
    ) {
        Gate::authorize(
            'updateAcademic',
            $afiliadoPostgrado->afiliado
        );

        $datos = $request->validate([
            'postgrado_id' => [
                'required',
                'integer',
                Rule::exists('postgrados', 'id')
                    ->where('estado', 'ACTIVO'),
            ],

            'grado_academico_id' => [
                'required',
                'integer',
                Rule::exists('grados_academicos', 'id')
                    ->where('estado', 'ACTIVO'),
            ],

            'institucion' => [
                'required',
                'string',
                'max:150',
            ],

            'numero_titulo' => [
                'required',
                'string',
                'max:50',
                Rule::unique(
                    'afiliado_postgrado',
                    'numero_titulo'
                )->ignore($afiliadoPostgrado->id),
            ],

            'fecha_titulo' => [
                'required',
                'date',
            ],

            'numero_certificado' => [
                'required',
                'string',
                'max:50',
                Rule::unique(
                    'afiliado_postgrado',
                    'numero_certificado'
                )->ignore($afiliadoPostgrado->id),
            ],
        ]);

        $yaExiste = AfiliadoPostgrado::where(
            'afiliado_id',
            $afiliadoPostgrado->afiliado_id
        )
            ->where('postgrado_id', $datos['postgrado_id'])
            ->where(
                'grado_academico_id',
                $datos['grado_academico_id']
            )
            ->where('id', '!=', $afiliadoPostgrado->id)
            ->exists();

        if ($yaExiste) {
            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    'El afiliado ya tiene registrado ese postgrado con ese grado académico.'
                );
        }

        $afiliadoPostgrado->update($datos);

        return redirect()
            ->route('afiliados.index')
            ->with(
                'success',
                'Información académica actualizada correctamente.'
            );
    }

    public function destroy(
        AfiliadoPostgrado $afiliadoPostgrado
    ) {
        Gate::authorize(
            'deleteAcademic',
            $afiliadoPostgrado->afiliado
        );

        $afiliadoPostgrado->delete();

        return redirect()
            ->route('afiliados.index')
            ->with(
                'success',
                'Información académica eliminada correctamente.'
            );
    }
}