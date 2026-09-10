<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AfiliadoPostgrado extends Model
{
    protected $table = 'afiliado_postgrado';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'afiliado_id',
        'postgrado_id',
        'grado_academico_id',
        'institucion',
        'numero_titulo',
        'fecha_titulo',
        'numero_certificado',
    ];

    protected $casts = [
        'afiliado_id' => 'integer',
        'postgrado_id' => 'integer',
        'grado_academico_id' => 'integer',
        'fecha_titulo' => 'date',
    ];

    public function afiliado(): BelongsTo
    {
        return $this->belongsTo(Afiliado::class, 'afiliado_id');
    }

    public function postgrado(): BelongsTo
    {
        return $this->belongsTo(Postgrado::class, 'postgrado_id');
    }

    public function gradoAcademico(): BelongsTo
    {
        return $this->belongsTo(
            GradoAcademico::class,
            'grado_academico_id'
        );
    }
}