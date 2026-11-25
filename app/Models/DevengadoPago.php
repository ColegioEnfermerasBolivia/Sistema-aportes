<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DevengadoPago extends Model
{
    protected $table = 'devengado_pagos';

    const CREATED_AT = 'creado_en';

    const UPDATED_AT = null;

    protected $fillable = [
        'devengado_lote_id',
        'afiliado_id',
        'monto_total',
        'monto_mensual',
        'saldo_generado',
        'cantidad_periodos',
        'estado',
        'clave_idempotencia',
        'observaciones',
        'registrado_por',
        'verificado_por',
        'fecha_verificacion',
        'fecha_pago',
    ];

    protected $casts = [
        'devengado_lote_id' => 'integer',
        'afiliado_id' => 'integer',
        'monto_total' => 'decimal:2',
        'monto_mensual' => 'decimal:2',
        'saldo_generado' => 'decimal:2',
        'cantidad_periodos' => 'integer',
        'registrado_por' => 'integer',
        'verificado_por' => 'integer',
        'fecha_pago' => 'datetime',
        'fecha_verificacion' => 'datetime',
    ];

    /** @return BelongsTo<Afiliado, $this> */
    public function afiliado(): BelongsTo
    {
        return $this->belongsTo(Afiliado::class);
    }

    /** @return BelongsTo<Usuario, $this> */
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'registrado_por');
    }

    /** @return BelongsTo<DevengadoLote, $this> */
    public function lote(): BelongsTo
    {
        return $this->belongsTo(DevengadoLote::class, 'devengado_lote_id');
    }

    /** @return BelongsTo<Usuario, $this> */
    public function verificadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'verificado_por');
    }

    /** @return HasMany<DevengadoPagoDetalle, $this> */
    public function detalles(): HasMany
    {
        return $this->hasMany(DevengadoPagoDetalle::class, 'devengado_pago_id');
    }
}
