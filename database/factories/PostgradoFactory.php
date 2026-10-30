<?php

namespace Database\Factories;

use App\Models\Postgrado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Postgrado>
 */
class PostgradoFactory extends Factory
{
    protected $model = Postgrado::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->jobTitle(),
            'estado' => 'ACTIVO',
        ];
    }
}
