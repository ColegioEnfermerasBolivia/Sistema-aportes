<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObservacionAfiliado extends Model
{
    protected $table = 'observaciones_afiliados';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'afiliado_id',
        'observacion',
        'usuario_id',
    ];

    public function afiliado(): BelongsTo
    {
        return $this->belongsTo(Afiliado::class, 'afiliado_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
