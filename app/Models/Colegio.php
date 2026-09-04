<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Colegio extends Model
{
    protected $table = 'colegios';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'nombre',
        'tipo',
        'colegio_padre_id',
        'estado',
    ];

    public function padre(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'colegio_padre_id');
    }

    public function hijos(): HasMany
    {
        return $this->hasMany(Colegio::class, 'colegio_padre_id');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'colegio_id');
    }

    public function afiliados(): HasMany
    {
        return $this->hasMany(Afiliado::class, 'colegio_id');
    }
}