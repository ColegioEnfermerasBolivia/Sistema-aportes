<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Egresada extends Model
{
    protected $table = 'egresadas';

    public const MENSAJE_REQUIERE_TITULACION =
        'Solo se puede convertir en afiliada si tiene al menos una formación con estado de titulación TITULADA.';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'ci',
        'nombres',
        'apellidos',
        'estado',
        'celular',
        'registrado_por',
        'actualizado_por',
        'convertida_por',
        'convertida_en',
        'colegio_asignado_id',
    ];

    protected $casts = [
        'convertida_en' => 'datetime',
    ];

    public function formaciones(): HasMany
    {
        return $this->hasMany(
            EgresadaFormacion::class,
            'egresada_id'
        );
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(
            Usuario::class,
            'registrado_por'
        );
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(
            Usuario::class,
            'actualizado_por'
        );
    }

    public function afiliado(): HasOne
    {
        return $this->hasOne(Afiliado::class, 'egresada_id');
    }

    public function convertidaPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'convertida_por');
    }

    public function colegioAsignado(): BelongsTo
    {
        return $this->belongsTo(Colegio::class, 'colegio_asignado_id');
    }

    public function estaConvertida(): bool
    {
        if (!$this->exists) {
            return false;
        }

        return $this->estado === 'AFILIADA'
            || ($this->relationLoaded('afiliado')
                ? $this->getRelation('afiliado') !== null
                : $this->afiliado()->exists());
    }

    public function tieneFormacionTitulada(): bool
    {
        if (!$this->exists) {
            return false;
        }

        if ($this->relationLoaded('formaciones')) {
            return $this->formaciones->contains(
                fn (EgresadaFormacion $formacion): bool =>
                    $formacion->estado_titulacion === 'TITULADA'
            );
        }

        return $this->formaciones()
            ->where('estado_titulacion', 'TITULADA')
            ->exists();
    }
}
