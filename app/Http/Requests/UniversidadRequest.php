<?php

namespace App\Http\Requests;

use App\Models\Universidad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UniversidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $usuario = $this->user();
        if (!$usuario) {
            return false;
        }

        $universidad = $this->route('universidad');

        return $universidad instanceof Universidad
            ? $usuario->can('update', $universidad)
            : $usuario->can('create', Universidad::class);
    }

    public function rules(): array
    {
        $universidad = $this->route('universidad');

        return [
            'nombre' => [
                'required',
                'string',
                'max:200',
                Rule::unique('universidades', 'nombre')
                    ->ignore($universidad?->id),
            ],
            'sigla' => ['nullable', 'string', 'max:30'],
            'tipo' => [
                'required',
                'string',
                Rule::in(Universidad::TIPOS_UNIVERSIDAD),
            ],
            'estado' => ['sometimes', 'required', 'string', 'max:20'],
        ];
    }
}
