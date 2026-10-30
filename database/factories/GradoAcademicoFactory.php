<?php

namespace Database\Factories;

use App\Models\GradoAcademico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradoAcademico>
 */
class GradoAcademicoFactory extends Factory
{
    protected $model = GradoAcademico::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->randomElement([
                'LICENCIATURA',
                'MAESTRIA',
                'DOCTORADO',
                'DIPLOMADO',
            ]).' '.fake()->unique()->numerify('###'),
            'estado' => 'ACTIVO',
        ];
    }
}
