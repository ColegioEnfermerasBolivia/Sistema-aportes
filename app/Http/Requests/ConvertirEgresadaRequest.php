<?php

namespace App\Http\Requests;

use App\Models\Afiliado;
use App\Models\Egresada;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ConvertirEgresadaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $egresada = $this->route('egresada');

        return $egresada instanceof Egresada
            && ($this->user()?->can('convert', $egresada) ?? false);
    }

    protected function prepareForValidation(): void
    {
        foreach ([
            'registro_departamental',
            'matricula_profesional',
            'celular',
        ] as $campo) {
            $valor = $this->input($campo);

            if (is_string($valor)) {
                $this->merge([
                    $campo => $campo === 'celular'
                        ? trim($valor)
                        : Afiliado::normalizarIdentificador($valor),
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'registro_departamental' => [
                'required',
                'string',
                'max:30',
                'unique:afiliados,registro_departamental',
            ],
            'matricula_profesional' => [
                'required',
                'string',
                'max:30',
                'unique:afiliados,matricula_profesional',
            ],
            'celular' => ['required', 'string', 'max:20'],
            'fecha_afiliacion' => [
                'required',
                'date',
                'after_or_equal:1900-01-01',
                'before_or_equal:today',
            ],
            'colegio_id' => [
                'required',
                'integer',
                Rule::exists('colegios', 'id')
                    ->where(fn ($query) => $query->where('estado', 'ACTIVO')),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $egresada = $this->route('egresada');
            if (!$egresada instanceof Egresada) {
                return;
            }

            if (!$egresada->tieneFormacionTitulada()) {
                $validator->errors()->add(
                    'egresada',
                    Egresada::MENSAJE_REQUIERE_TITULACION
                );
            }

            $ci = mb_strtoupper(
                Afiliado::normalizarIdentificador($egresada->ci) ?? '',
                'UTF-8'
            );

            foreach ([
                'ci' => [$ci, 20],
                'nombres' => [$egresada->nombres, 100],
                'apellidos' => [$egresada->apellidos, 100],
            ] as $campo => [$valor, $maximo]) {
                if (mb_strlen((string) $valor, 'UTF-8') > $maximo) {
                    $validator->errors()->add(
                        $campo,
                        "El campo {$campo} supera el máximo permitido para una afiliada."
                    );
                }
            }

            if (
                !$validator->errors()->has('ci')
                && \Illuminate\Support\Facades\DB::table('afiliados')
                    ->whereRaw(
                        "UPPER(REPLACE(TRIM(ci), ' ', '')) = ?",
                        [$ci]
                    )
                    ->exists()
            ) {
                $validator->errors()->add(
                    'ci',
                    'Ya existe una afiliada registrada con ese CI.'
                );
            }

            if (
                !$validator->errors()->has('colegio_id')
                && !$this->user()->can(
                    'convertToCollege',
                    [$egresada, (int) $this->input('colegio_id')]
                )
            ) {
                $validator->errors()->add(
                    'colegio_id',
                    'No tiene permiso para asignar la afiliada a ese colegio.'
                );
            }
        });
    }
}
