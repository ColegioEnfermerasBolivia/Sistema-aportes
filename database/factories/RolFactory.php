<?php

namespace Database\Factories;

use App\Models\Rol;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rol>
 */
class RolFactory extends Factory
{
    protected $model = Rol::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->word(),
        ];
    }

    public function named(string $name): static
    {
        return $this->state(fn () => ['nombre' => $name]);
    }
}
