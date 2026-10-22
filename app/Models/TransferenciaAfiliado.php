<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferenciaAfiliado extends Model
{
    protected $table = 'transferencias_afiliados';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'afiliado_id',
        'colegio_id_origen',
        'colegio_id_destino',
        'fecha_transferencia',
    ];

    protected $casts = [
        'fecha_transferencia' => 'date',
    ];

    /**
     * Afiliada que realizó la transferencia.
     */
    public function afiliado(): BelongsTo
    {
        return $this->belongsTo(
            Afiliado::class,
            'afiliado_id'
        );
    }

    /**
     * Colegio del que proviene la afiliada.
     */
    public function colegioOrigen(): BelongsTo
    {
        return $this->belongsTo(
            Colegio::class,
            'colegio_id_origen'
        );
    }

    /**
     * Colegio al que pasa la afiliada.
     */
    public function colegioDestino(): BelongsTo
    {
        return $this->belongsTo(
            Colegio::class,
            'colegio_id_destino'
        );
    }
}
