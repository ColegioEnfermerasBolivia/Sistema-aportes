<?php

namespace Database\Factories;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    public function definition(): array
    {
        return [
            'ci' => fake()->unique()->numerify('########'),
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'username' => fake()->unique()->userName(),
            'password' => Hash::make('Password123!'),
            'debe_cambiar_password' => false,
            'cel' => fake()->numerify('7#######'),
            'rol_id' => RolFactory::new(),
            'colegio_id' => ColegioFactory::new(),
            'estado' => 'ACTIVO',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['estado' => 'INACTIVO']);
    }

    public function mustChangePassword(): static
    {
        return $this->state(fn () => ['debe_cambiar_password' => true]);
    }
}
