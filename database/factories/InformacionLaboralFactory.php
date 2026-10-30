<?php

namespace Database\Factories;

use App\Models\InformacionLaboral;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InformacionLaboral>
 */
class InformacionLaboralFactory extends Factory
{
    protected $model = InformacionLaboral::class;

    public function definition(): array
    {
        return [
            'afiliado_id' => AfiliadoFactory::new(),
            'institucion' => fake()->company(),
            'tipo_contrato' => 'INDEFINIDO',
            'haber_basico' => '5000.00',
            'monto_aporte' => '100.00',
            'fecha_ingreso_trabajo' => fake()->date(),
            'culminado' => false,
            'fecha_retiro' => null,
        ];
    }

    public function finished(): static
    {
        return $this->state(fn () => [
            'culminado' => true,
            'fecha_retiro' => fake()->date(),
        ]);
    }
}
