<?php

namespace Database\Factories;

use App\Models\AfiliadoPostgrado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AfiliadoPostgrado>
 */
class AfiliadoPostgradoFactory extends Factory
{
    protected $model = AfiliadoPostgrado::class;

    public function definition(): array
    {
        return [
            'afiliado_id' => AfiliadoFactory::new(),
            'postgrado_id' => PostgradoFactory::new(),
            'grado_academico_id' => GradoAcademicoFactory::new(),
            'institucion' => fake()->company(),
            'numero_titulo' => fake()->unique()->bothify('TIT-######'),
            'fecha_titulo' => fake()->date(),
            'numero_certificado' => fake()->unique()->bothify('CERT-######'),
        ];
    }
}
