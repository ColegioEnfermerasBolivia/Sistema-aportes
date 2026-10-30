<?php

namespace Database\Factories;

use App\Models\Aporte;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Aporte>
 */
class AporteFactory extends Factory
{
    protected $model = Aporte::class;

    public function definition(): array
    {
        return [
            'afiliado_id' => AfiliadoFactory::new(),
            'colegio_id' => ColegioFactory::new(),
            'pago_id' => PagoFactory::new(),
            'mes' => fake()->numberBetween(1, 12),
            'year' => fake()->numberBetween(2020, (int) date('Y')),
            'monto' => '100.00',
            'estado' => 'VERIFICADO',
            'observaciones' => null,
            'registrado_por' => UsuarioFactory::new(),
            'fecha_registro' => now(),
            'verificado_por' => null,
            'fecha_verificacion' => null,
        ];
    }
}
