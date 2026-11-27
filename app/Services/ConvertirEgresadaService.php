<?php

namespace App\Services;

use App\Models\Afiliado;
use App\Models\Egresada;
use App\Models\Usuario;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ConvertirEgresadaService
{
    public function __construct(
        private AuditoriaService $auditoria
    ) {}

    public function convertir(
        Egresada $egresada,
        array $datos,
        Usuario $usuario
    ): Afiliado {
        return DB::transaction(function () use (
            $egresada,
            $datos,
            $usuario
        ): Afiliado {
            $egresada = Egresada::query()
                ->lockForUpdate()
                ->findOrFail($egresada->id);

            Gate::forUser($usuario)->authorize('convert', $egresada);
            Gate::forUser($usuario)->authorize(
                'convertToCollege',
                [$egresada, (int) $datos['colegio_id']]
            );

            if (!$egresada->tieneFormacionTitulada()) {
                throw ValidationException::withMessages([
                    'egresada' => Egresada::MENSAJE_REQUIERE_TITULACION,
                ]);
            }

            if (
                Afiliado::query()
                    ->where('egresada_id', $egresada->id)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'egresada' => 'Esta egresada ya fue convertida en afiliada.',
                ]);
            }

            $ci = mb_strtoupper(
                Afiliado::normalizarIdentificador($egresada->ci) ?? '',
                'UTF-8'
            );
            $ciExistente = Afiliado::query()
                ->whereRaw(
                    "UPPER(REPLACE(TRIM(ci), ' ', '')) = ?",
                    [$ci]
                )
                ->exists();

            if ($ciExistente) {
                throw ValidationException::withMessages([
                    'ci' => 'Ya existe una afiliada registrada con ese CI.',
                ]);
            }

            try {
                $afiliado = Afiliado::create([
                    'egresada_id' => $egresada->id,
                    'registro_departamental' => $datos['registro_departamental'],
                    'matricula_profesional' => $datos['matricula_profesional'],
                    'ci' => $ci,
                    'colegio_id' => (int) $datos['colegio_id'],
                    'nombres' => $egresada->nombres,
                    'apellidos' => $egresada->apellidos,
                    'celular' => $datos['celular'],
                    'estado' => 'ACTIVO',
                    'fecha_afiliacion' => $datos['fecha_afiliacion'],
                ]);
            } catch (QueryException $exception) {
                if (!in_array((string) $exception->getCode(), [
                    '23000',
                    '23505',
                    '19',
                ], true)) {
                    throw $exception;
                }

                throw ValidationException::withMessages([
                    'egresada' => 'La afiliada o sus datos de registro ya fueron creados.',
                ]);
            }

            $estadoAnterior = $egresada->estado;
            $egresada->update([
                'estado' => 'AFILIADA',
                'convertida_por' => $usuario->id,
                'convertida_en' => now(),
                'colegio_asignado_id' => $afiliado->colegio_id,
            ]);

            $this->auditoria->registrar(
                (int) $usuario->id,
                'CONVERTIR_AFILIADA',
                'egresadas',
                (int) $egresada->id,
                [
                    'estado' => $estadoAnterior,
                    'ci' => $egresada->ci,
                ],
                [
                    'estado' => $egresada->estado,
                    'afiliado_id' => $afiliado->id,
                    'ci' => $afiliado->ci,
                    'colegio_id' => $afiliado->colegio_id,
                    'convertida_por' => $usuario->id,
                    'convertida_en' => $egresada->convertida_en?->toISOString(),
                    'registro_departamental' => $afiliado->registro_departamental,
                    'matricula_profesional' => $afiliado->matricula_profesional,
                ]
            );

            return $afiliado;
        }, attempts: 3);
    }
}
