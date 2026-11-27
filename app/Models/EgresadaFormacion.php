<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EgresadaFormacion extends Model
{
    protected $table = 'egresada_formacion';

    public const ESTADOS_TITULACION = [
        'EGRESADA',
        'TITULADA',
    ];

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'egresada_id',
        'universidad_id',
        'departamento_id',
        'carrera',
        'fecha_egreso',
        'estado_titulacion',
        'numero_titulo',
        'registrado_por',
        'actualizado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_egreso' => 'date',
        ];
    }

    public function egresada(): BelongsTo
    {
        return $this->belongsTo(
            Egresada::class,
            'egresada_id'
        );
    }

    public function universidad(): BelongsTo
    {
        return $this->belongsTo(
            Universidad::class,
            'universidad_id'
        );
    }

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(
            Departamento::class,
            'departamento_id'
        );
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(
            Usuario::class,
            'registrado_por'
        );
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(
            Usuario::class,
            'actualizado_por'
        );
    }
}
