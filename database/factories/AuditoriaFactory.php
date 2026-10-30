<?php

namespace Database\Factories;

use App\Models\Auditoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Auditoria>
 */
class AuditoriaFactory extends Factory
{
    protected $model = Auditoria::class;

    public function definition(): array
    {
        return [
            'usuario_id' => UsuarioFactory::new(),
            'accion' => 'CREAR',
            'tabla_afectada' => 'afiliados',
            'registro_id' => null,
            'datos_anteriores' => null,
            'datos_nuevos' => null,
            'fecha_registro' => now(),
        ];
    }
}
