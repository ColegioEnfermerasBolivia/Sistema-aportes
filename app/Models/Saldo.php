<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Saldo extends Model
{
    protected $table = 'saldos';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'afiliado_id',
        'pago_id',
        'monto',
        'monto_disponible',
        'estado',
        'observacion',
    ];

    protected $casts = [
        'afiliado_id' => 'integer',
        'pago_id' => 'integer',
        'monto' => 'decimal:2',
        'monto_disponible' => 'decimal:2',
    ];

    public function afiliado(): BelongsTo
    {
        return $this->belongsTo(
            Afiliado::class,
            'afiliado_id'
        );
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(
            Pago::class,
            'pago_id'
        );
    }
}
