<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoteVerificacion extends Model
{
    protected $table = 'lote_verificacion';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'colegio_id',
        'mes',
        'year',
        'codigo_lote',
        'cantidad_aportes',
        'monto_total',
        'estado',
        'fecha_cierre_departamental',
        'fecha_recepcion_col_nacional',
        'fecha_verificacion',
        'enviado_por',
        'verificado_por',
        'observaciones_generales',
    ];

    protected $casts = [
        'colegio_id' => 'integer',
        'mes' => 'integer',
        'year' => 'integer',
        'cantidad_aportes' => 'integer',
        'monto_total' => 'decimal:2',
        'fecha_cierre_departamental' => 'datetime',
        'fecha_recepcion_col_nacional' => 'datetime',
        'fecha_verificacion' => 'datetime',
        'enviado_por' => 'integer',
        'verificado_por' => 'integer',
        'creado_en' => 'datetime', 
        'actualizado_en' => 'datetime',
    ];

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'colegio_id');
    }

    public function enviadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'enviado_por');
    }

    public function verificadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'verificado_por');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'lote_verificacion_id');
    }
}