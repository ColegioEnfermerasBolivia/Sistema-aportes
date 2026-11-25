<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DevengadoPeriodo extends Model
{
    protected $table = 'devengado_periodos';

    const CREATED_AT = 'creado_en';

    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'devengado_lote_id',
        'afiliado_id',
        'mes',
        'year',
        'monto',
        'metodo_determinacion',
        'observaciones',
        'estado',
        'observacion_revision',
        'revisado_por',
        'fecha_revision',
        'aporte_id',
    ];

    protected $casts = [
        'devengado_lote_id' => 'integer',
        'afiliado_id' => 'integer',
        'mes' => 'integer',
        'year' => 'integer',
        'monto' => 'decimal:2',
        'revisado_por' => 'integer',
        'fecha_revision' => 'datetime',
        'aporte_id' => 'integer',
    ];

    /** @return BelongsTo<DevengadoLote, $this> */
    public function lote(): BelongsTo
    {
        return $this->belongsTo(DevengadoLote::class, 'devengado_lote_id');
    }

    /** @return BelongsTo<Afiliado, $this> */
    public function afiliado(): BelongsTo
    {
        return $this->belongsTo(Afiliado::class, 'afiliado_id');
    }

    /** @return BelongsTo<Usuario, $this> */
    public function revisadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'revisado_por');
    }

    /** @return BelongsTo<Aporte, $this> */
    public function aporte(): BelongsTo
    {
        return $this->belongsTo(Aporte::class, 'aporte_id');
    }

    /** @return HasMany<DevengadoPagoDetalle, $this> */
    public function pagosDetalles(): HasMany
    {
        return $this->hasMany(DevengadoPagoDetalle::class, 'devengado_periodo_id');
    }

    /** @return HasMany<DevengadoExoneracion, $this> */
    public function exoneraciones(): HasMany
    {
        return $this->hasMany(DevengadoExoneracion::class, 'devengado_periodo_id');
    }
}
