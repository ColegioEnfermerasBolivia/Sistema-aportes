<?php

namespace Database\Factories;

use App\Models\TransferenciaAfiliado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransferenciaAfiliado>
 */
class TransferenciaAfiliadoFactory extends Factory
{
    protected $model = TransferenciaAfiliado::class;

    public function definition(): array
    {
        return [
            'afiliado_id' => AfiliadoFactory::new(),
            'colegio_id_origen' => ColegioFactory::new(),
            'colegio_id_destino' => ColegioFactory::new(),
            'fecha_transferencia' => fake()->date(),
        ];
    }
}
