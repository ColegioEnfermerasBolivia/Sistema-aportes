<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Departamento extends Model
{
    protected $table = 'departamentos';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'codigo',
        'nombre',
    ];

    public function formaciones(): HasMany
    {
        return $this->hasMany(
            EgresadaFormacion::class,
            'departamento_id'
        );
    }
}
