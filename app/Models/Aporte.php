<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $mes
 * @property int $year
 * @property string $monto
 */
class Aporte extends Model
{
    protected $table = 'aportes';

    const CREATED_AT = 'creado_en';

    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'afiliado_id',
        'colegio_id',
        'pago_id',
        'mes',
        'year',
        'monto',
        'estado',
        'observaciones',
        'registrado_por',
        'fecha_registro',
        'verificado_por',
        'fecha_verificacion',
    ];

    protected $casts = [
        'afiliado_id' => 'integer',
        'colegio_id' => 'integer',
        'pago_id' => 'integer',
        'mes' => 'integer',
        'year' => 'integer',
        'monto' => 'decimal:2',
        'registrado_por' => 'integer',
        'fecha_registro' => 'datetime',
        'verificado_por' => 'integer',
        'fecha_verificacion' => 'datetime',
    ];

    /** @return BelongsTo<Afiliado, $this> */
    public function afiliado(): BelongsTo
    {
        return $this->belongsTo(Afiliado::class, 'afiliado_id');
    }

    /** @return BelongsTo<Colegio, $this> */
    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'colegio_id');
    }

    /** @return BelongsTo<Pago, $this> */
    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'pago_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'registrado_por');
    }

    public function verificadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'verificado_por');
    }

    /** @return HasOne<DevengadoPeriodo, $this> */
    public function devengadoPeriodo(): HasOne
    {
        return $this->hasOne(DevengadoPeriodo::class, 'aporte_id');
    }
}
