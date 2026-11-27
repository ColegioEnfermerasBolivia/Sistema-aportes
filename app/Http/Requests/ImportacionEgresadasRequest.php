<?php

namespace App\Http\Requests;

use App\Models\Egresada;
use Illuminate\Foundation\Http\FormRequest;

class ImportacionEgresadasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('import', Egresada::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'archivo' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:10240',
            ],
        ];
    }
}
