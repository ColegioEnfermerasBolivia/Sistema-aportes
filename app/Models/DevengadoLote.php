<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DevengadoLote extends Model
{
    protected $table = 'devengado_lotes';

    const CREATED_AT = 'creado_en';

    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'colegio_id',
        'mes',
        'year',
        'codigo_lote',
        'estado',
        'observaciones',
        'creado_por',
        'enviado_por',
        'revisado_por',
        'fecha_envio',
        'fecha_revision',
    ];

    protected $casts = [
        'colegio_id' => 'integer',
        'mes' => 'integer',
        'year' => 'integer',
        'creado_por' => 'integer',
        'enviado_por' => 'integer',
        'revisado_por' => 'integer',
        'fecha_envio' => 'datetime',
        'fecha_revision' => 'datetime',
    ];

    /** @return BelongsTo<Colegio, $this> */
    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class);
    }

    /** @return HasMany<DevengadoPeriodo, $this> */
    public function periodos(): HasMany
    {
        return $this->hasMany(DevengadoPeriodo::class, 'devengado_lote_id');
    }

    /** @return HasMany<DevengadoPago, $this> */
    public function pagos(): HasMany
    {
        return $this->hasMany(DevengadoPago::class, 'devengado_lote_id');
    }

    /** @return BelongsTo<Usuario, $this> */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'creado_por');
    }

    /** @return BelongsTo<Usuario, $this> */
    public function enviadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'enviado_por');
    }

    /** @return BelongsTo<Usuario, $this> */
    public function revisadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'revisado_por');
    }
}
