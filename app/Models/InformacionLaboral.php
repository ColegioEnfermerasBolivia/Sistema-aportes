<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InformacionLaboral extends Model
{
    protected $table = 'informacion_laboral';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'afiliado_id',
        'institucion',
        'tipo_contrato',
        'haber_basico',
        'monto_aporte',
        'fecha_ingreso_trabajo',
        'culminado',
        'fecha_retiro',
    ];

    protected $casts = [
        'culminado' => 'boolean',
        'haber_basico' => 'decimal:2',
        'monto_aporte' => 'decimal:2',
        'fecha_ingreso_trabajo' => 'date',
        'fecha_retiro' => 'date',
    ];

    public function afiliado(): BelongsTo
    {
        return $this->belongsTo(Afiliado::class);
    }
}