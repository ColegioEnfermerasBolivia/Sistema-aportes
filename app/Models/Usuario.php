<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Usuario extends Authenticatable
{
    protected $table = 'usuarios';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'ci',
        'nombres',
        'apellidos',
        'username',
        'password',
        'debe_cambiar_password',
        'cel',
        'rol_id',
        'colegio_id',
        'estado',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'debe_cambiar_password' => 'boolean',
    ];
    /**
     * @return BelongsTo<Rol, $this>
     */
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class);
    }

    /**
     * @return BelongsTo<Colegio, $this>
     */
    public function colegio(): BelongsTo
    {
        return $this->belongsTo(Colegio::class);
    }

    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class, 'usuario_id');
    }

    public function pagosRegistrados(): HasMany
    {
        return $this->hasMany(Pago::class, 'registrado_por');
    }

    public function aportesRegistrados(): HasMany
    {
        return $this->hasMany(Aporte::class, 'registrado_por');
    }

    public function lotesEnviados(): HasMany
    {
        return $this->hasMany(LoteVerificacion::class, 'enviado_por');
    }

    public function lotesVerificados(): HasMany
    {
        return $this->hasMany(LoteVerificacion::class, 'verificado_por');
    }

    public function aportesVerificados(): HasMany
    {
        return $this->hasMany(Aporte::class, 'verificado_por');
    }

    public function pagosVerificados(): HasMany
    {
        return $this->hasMany(Pago::class, 'verificado_por');
    }

    public function egresadasRegistradas(): HasMany
    {
        return $this->hasMany(Egresada::class, 'registrado_por');
    }

    public function egresadasActualizadas(): HasMany
    {
        return $this->hasMany(Egresada::class, 'actualizado_por');
    }

    public function egresadasConvertidas(): HasMany
    {
        return $this->hasMany(Egresada::class, 'convertida_por');
    }

    public function formacionesRegistradas(): HasMany
    {
        return $this->hasMany(EgresadaFormacion::class, 'registrado_por');
    }

    public function formacionesActualizadas(): HasMany
    {
        return $this->hasMany(EgresadaFormacion::class, 'actualizado_por');
    }

   
}