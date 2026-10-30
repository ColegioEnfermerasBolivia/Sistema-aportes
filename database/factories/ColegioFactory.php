<?php

namespace Database\Factories;

use App\Models\Colegio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Colegio>
 */
class ColegioFactory extends Factory
{
    protected $model = Colegio::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->city(),
            'codigo' => fake()->unique()->bothify('??##'),
            'tipo' => 'REGIONAL',
            'colegio_padre_id' => null,
            'estado' => 'ACTIVO',
        ];
    }

    public function departamental(): static
    {
        return $this->state(fn () => ['tipo' => 'DEPARTAMENTAL']);
    }
}
