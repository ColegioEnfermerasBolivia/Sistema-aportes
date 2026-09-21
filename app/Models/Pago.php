<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pago extends Model
{
    protected $table = 'pagos';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'afiliado_id',
        'lote_verificacion_id',
        'monto_total',
        'cantidad_periodos',
        'estado',
        'observaciones',
        'registrado_por',
        'verificado_por',
        'fecha_verificacion',
    ];

    protected $casts = [
        'afiliado_id' => 'integer',
        'lote_verificacion_id' => 'integer',
        'monto_total' => 'decimal:2',
        'cantidad_periodos' => 'integer',
        'registrado_por' => 'integer',
        'verificado_por' => 'integer',
        'fecha_verificacion' => 'datetime',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    public function afiliado(): BelongsTo
    {
        return $this->belongsTo(Afiliado::class, 'afiliado_id');
    }

    public function loteVerificacion(): BelongsTo
    {
        return $this->belongsTo(LoteVerificacion::class, 'lote_verificacion_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'registrado_por');
    }

    public function verificadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'verificado_por');
    }

    public function aportes(): HasMany
    {
        return $this->hasMany(Aporte::class, 'pago_id');
    }
}