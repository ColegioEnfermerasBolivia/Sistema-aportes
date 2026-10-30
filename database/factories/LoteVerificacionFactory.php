<?php

namespace Database\Factories;

use App\Models\LoteVerificacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoteVerificacion>
 */
class LoteVerificacionFactory extends Factory
{
    protected $model = LoteVerificacion::class;

    public function definition(): array
    {
        $month = fake()->numberBetween(1, 12);
        $year = fake()->numberBetween(2020, (int) date('Y'));

        return [
            'colegio_id' => ColegioFactory::new(),
            'mes' => $month,
            'year' => $year,
            'codigo_lote' => fake()->unique()->bothify('LOT-####-??'),
            'cantidad_aportes' => 0,
            'monto_total' => '0.00',
            'estado' => 'ABIERTO',
            'fecha_cierre_departamental' => null,
            'fecha_recepcion_col_nacional' => null,
            'fecha_verificacion' => null,
            'enviado_por' => null,
            'verificado_por' => null,
            'observaciones_generales' => null,
        ];
    }

    public function inState(string $state): static
    {
        return $this->state(fn () => ['estado' => $state]);
    }
}
