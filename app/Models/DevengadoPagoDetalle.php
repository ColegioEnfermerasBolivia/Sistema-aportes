<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevengadoPagoDetalle extends Model
{
    protected $table = 'devengado_pago_detalles';

    const CREATED_AT = 'creado_en';

    const UPDATED_AT = null;

    protected $fillable = [
        'devengado_pago_id',
        'devengado_periodo_id',
        'monto',
    ];

    protected $casts = [
        'devengado_pago_id' => 'integer',
        'devengado_periodo_id' => 'integer',
        'monto' => 'decimal:2',
    ];

    /** @return BelongsTo<DevengadoPago, $this> */
    public function pago(): BelongsTo
    {
        return $this->belongsTo(DevengadoPago::class, 'devengado_pago_id');
    }

    /** @return BelongsTo<DevengadoPeriodo, $this> */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(DevengadoPeriodo::class, 'devengado_periodo_id');
    }
}
