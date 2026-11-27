<?php

namespace App\Http\Requests;

use App\Models\Egresada;
use App\Models\EgresadaFormacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class EgresadaRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('ci')) {
            $this->merge([
                'ci' => mb_strtoupper(trim((string) $this->input('ci')), 'UTF-8'),
            ]);
        }
    }

    public function authorize(): bool
    {
        $usuario = $this->user();
        if (!$usuario) {
            return false;
        }

        $egresada = $this->route('egresada');

        return $egresada instanceof Egresada
            ? $usuario->can('update', $egresada)
            : $usuario->can('create', Egresada::class);
    }

    public function rules(): array
    {
        $egresada = $this->route('egresada');
        $actualizando = $egresada instanceof Egresada;

        return [
            'ci' => [
                'required',
                'string',
                'max:30',
                Rule::unique('egresadas', 'ci')->ignore($egresada?->id),
            ],
            'nombres' => ['required', 'string', 'max:150'],
            'apellidos' => ['required', 'string', 'max:150'],
            'celular' => ['nullable', 'string', 'max:30'],
            'formaciones_present' => ['sometimes', 'boolean'],
            'formaciones' => [
                $actualizando ? 'sometimes' : 'required',
                'array',
                $actualizando ? 'min:0' : 'min:1',
            ],
            'formaciones.*' => ['required', 'array'],
            'formaciones.*.id' => [
                $actualizando ? 'sometimes' : 'prohibited',
                'integer',
                'distinct',
                Rule::exists('egresada_formacion', 'id'),
            ],
            'formaciones.*.universidad_id' => [
                'required',
                'integer',
                Rule::exists('universidades', 'id'),
            ],
            'formaciones.*.departamento_id' => [
                'required',
                'integer',
                Rule::exists('departamentos', 'id'),
            ],
            'formaciones.*.carrera' => [
                'required',
                'string',
                'max:200',
            ],
            'formaciones.*.fecha_egreso' => [
                'nullable',
                'date',
                'after_or_equal:1900-01-01',
            ],
            'formaciones.*.estado_titulacion' => [
                'nullable',
                Rule::in(EgresadaFormacion::ESTADOS_TITULACION),
            ],
            'formaciones.*.numero_titulo' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $ci = $this->input('ci');

            if (!is_string($ci) || $ci === '' || $validator->errors()->has('ci')) {
                return;
            }

            $query = Egresada::query()
                ->whereRaw('UPPER(TRIM(ci)) = ?', [$ci]);
            $egresada = $this->route('egresada');

            if ($egresada instanceof Egresada) {
                $query->where('id', '!=', $egresada->id);
            }

            if ($query->exists()) {
                $validator->errors()->add(
                    'ci',
                    'Ya existe una egresada registrada con ese CI.'
                );
            }
        });
    }
}
