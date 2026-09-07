<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradoAcademico extends Model
{
    protected $table = 'grados_academicos';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'nombre',
        'estado',
    ];

    public function afiliadosPostgrados(): HasMany
    {
        return $this->hasMany(AfiliadoPostgrado::class, 'grado_academico_id');
    }
}