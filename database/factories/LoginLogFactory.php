<?php

namespace Database\Factories;

use App\Models\LoginLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoginLog>
 */
class LoginLogFactory extends Factory
{
    protected $model = LoginLog::class;

    public function definition(): array
    {
        return [
            'usuario_id' => UsuarioFactory::new(),
            'username' => fake()->userName(),
            'resultado' => 'EXITOSO',
            'motivo' => null,
            'ip' => fake()->ipv4(),
        ];
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'resultado' => 'FALLIDO',
            'motivo' => 'CREDENCIALES_INVALIDAS',
        ]);
    }
}
