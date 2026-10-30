<?php

namespace Database\Factories;

use App\Models\Afiliado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Afiliado>
 */
class AfiliadoFactory extends Factory
{
    protected $model = Afiliado::class;

    public function definition(): array
    {
        return [
            'registro_departamental' => fake()->unique()->bothify('RD-######'),
            'matricula_profesional' => fake()->unique()->bothify('MP-######'),
            'ci' => fake()->unique()->numerify('########'),
            'colegio_id' => ColegioFactory::new(),
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'celular' => fake()->numerify('7#######'),
            'estado' => 'ACTIVO',
            'fecha_afiliacion' => fake()->dateTimeBetween('-5 years', '-1 year')->format('Y-m-d'),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['estado' => 'INACTIVO']);
    }
}
