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
        'codigo',
        'tipo',
        'colegio_padre_id',
        'estado',
    ];

    /**@return BelongsTo<Colegio, $this> */
    public function padre(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'colegio_padre_id');
    }

    /** @return HasMany<Colegio, $this> */
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

    public function lotesVerificacion(): HasMany
    {
        return $this->hasMany(LoteVerificacion::class, 'colegio_id');
    }

    public function aportes(): HasMany
    {
        return $this->hasMany(Aporte::class, 'colegio_id');
    }

    /**
 * Transferencias en las que este colegio es el origen.
 */
    public function transferenciasOrigen(): HasMany
    {
        return $this->hasMany(
            TransferenciaAfiliado::class,
            'colegio_id_origen'
        );
    }

    /**
     * Transferencias en las que este colegio es el destino.
     */
    public function transferenciasDestino(): HasMany
    {
        return $this->hasMany(
            TransferenciaAfiliado::class,
            'colegio_id_destino'
        );
    }
}
