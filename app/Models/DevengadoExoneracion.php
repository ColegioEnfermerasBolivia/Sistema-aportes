<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevengadoExoneracion extends Model
{
    protected $table = 'devengado_exoneraciones';

    const CREATED_AT = 'creado_en';

    const UPDATED_AT = null;

    protected $fillable = [
        'devengado_periodo_id',
        'monto',
        'motivo',
        'presidente_nombre',
        'autorizacion_referencia',
        'registrado_por',
        'fecha_exoneracion',
    ];

    protected $casts = [
        'devengado_periodo_id' => 'integer',
        'monto' => 'decimal:2',
        'registrado_por' => 'integer',
        'fecha_exoneracion' => 'datetime',
    ];

    /** @return BelongsTo<DevengadoPeriodo, $this> */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(DevengadoPeriodo::class, 'devengado_periodo_id');
    }

    /** @return BelongsTo<Usuario, $this> */
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'registrado_por');
    }
}
