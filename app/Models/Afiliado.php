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

    public static function normalizarIdentificador(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        return preg_replace('/[\s\p{Z}]+/u', '', $valor) ?? $valor;
    }

    public function setRegistroDepartamentalAttribute(?string $valor): void
    {
        $this->attributes['registro_departamental'] =
            self::normalizarIdentificador($valor);
    }

    public function setMatriculaProfesionalAttribute(?string $valor): void
    {
        $this->attributes['matricula_profesional'] =
            self::normalizarIdentificador($valor);
    }

    public function setCiAttribute(?string $valor): void
    {
        $this->attributes['ci'] = self::normalizarIdentificador($valor);
    }

    /** @return BelongsTo<Colegio, $this> */
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

    /** @return HasMany<Pago, $this> */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'afiliado_id');
    }

    /** @return HasMany<Aporte, $this> */
    public function aportes(): HasMany
    {
        return $this->hasMany(Aporte::class, 'afiliado_id');
    }

    /** @return HasMany<Saldo, $this> */
    public function saldosActivos(): HasMany
    {
        return $this->hasMany(Saldo::class, 'afiliado_id')
            ->where('estado', 'ACTIVO')
            ->where('monto_disponible', '>', 0);
    }

    /**
     * Historial de transferencias de la afiliada.
     */
    public function transferencias(): HasMany
    {
        return $this->hasMany(
            TransferenciaAfiliado::class,
            'afiliado_id'
        )->with(([
            'colegioOrigen',
            'colegioDestino',
        ]))
        ->orderBy('fecha_transferencia');
    }

    /** @return HasMany<ObservacionAfiliado, $this> */
    public function observaciones(): HasMany
    {
        return $this->hasMany(
            ObservacionAfiliado::class,
            'afiliado_id'
        );
    }

    public function informacionLaboral():HasOne {
        return $this->hasOne(InformacionLaboral::class, 'afiliado_id');
    }
}