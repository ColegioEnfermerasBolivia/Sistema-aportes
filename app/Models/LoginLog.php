<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginLog extends Model
{
    protected $table = 'login_logs';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = null;

    protected $fillable = [
        'usuario_id',
        'username',
        'resultado',
        'motivo',
        'ip',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}