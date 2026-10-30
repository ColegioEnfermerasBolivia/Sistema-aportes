<?php

namespace Database\Factories;

use App\Models\ObservacionAfiliado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ObservacionAfiliado>
 */
class ObservacionAfiliadoFactory extends Factory
{
    protected $model = ObservacionAfiliado::class;

    public function definition(): array
    {
        return [
            'afiliado_id' => AfiliadoFactory::new(),
            'observacion' => fake()->sentence(),
            'usuario_id' => UsuarioFactory::new(),
        ];
    }
}
