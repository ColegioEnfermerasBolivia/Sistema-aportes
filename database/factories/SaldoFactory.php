<?php

namespace Database\Factories;

use App\Models\Saldo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Saldo>
 */
class SaldoFactory extends Factory
{
    protected $model = Saldo::class;

    public function definition(): array
    {
        return [
            'afiliado_id' => AfiliadoFactory::new(),
            'pago_id' => null,
            'monto' => '10.00',
            'monto_disponible' => '10.00',
            'estado' => 'ACTIVO',
            'observacion' => null,
        ];
    }
}
