<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Afiliado extends Model
{
    protected $table = 'afiliados';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'registro_departamental',
        'matricula_profesional',
        'ci',
        'colegio_id',
        'nombres',
        'apellidos',
        'celular',
        'estado',
        'fecha_afiliacion',
    ];

    protected $casts = [
        'fecha_afiliacion' => 'date',
        'colegio_id' => 'integer',
    ];

    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class);
    }

    public function informacionesLaborales(): HasMany
    {
        return $this->hasMany(
            InformacionLaboral::class,
            'afiliado_id'
        );
    }

    public function afiliadosPostgrados(): HasMany
    {
        return $this->hasMany(AfiliadoPostgrado::class, 'afiliado_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'afiliado_id');
    }

    public function aportes(): HasMany
    {
        return $this->hasMany(Aporte::class, 'afiliado_id');
    }

}