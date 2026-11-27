<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Universidad extends Model
{
    protected $table = 'universidades';

    public const TIPOS_UNIVERSIDAD = [
        'PUBLICA',
        'PRIVADA',
    ];

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'nombre',
        'sigla',
        'tipo',
        'estado',
    ];

    public function formaciones(): HasMany
    {
        return $this->hasMany(
            EgresadaFormacion::class,
            'universidad_id'
        );
    }
}
