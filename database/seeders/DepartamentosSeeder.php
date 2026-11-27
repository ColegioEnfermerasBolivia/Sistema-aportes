<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartamentosSeeder extends Seeder
{
    public function run(): void
    {
        $departamentos = [
            ['codigo' => 'LPZ', 'nombre' => 'La Paz'],
            ['codigo' => 'SCZ', 'nombre' => 'Santa Cruz'],
            ['codigo' => 'CBB', 'nombre' => 'Cochabamba'],
            ['codigo' => 'CHU', 'nombre' => 'Chuquisaca'],
            ['codigo' => 'ORU', 'nombre' => 'Oruro'],
            ['codigo' => 'POT', 'nombre' => 'Potosí'],
            ['codigo' => 'TJA', 'nombre' => 'Tarija'],
            ['codigo' => 'BEN', 'nombre' => 'Beni'],
            ['codigo' => 'PAN', 'nombre' => 'Pando'],
        ];

        foreach ($departamentos as $departamento) {
            DB::table('departamentos')->updateOrInsert(
                ['codigo' => $departamento['codigo']],
                [
                    'nombre' => $departamento['nombre'],
                    'actualizado_en' => now(),
                ]
            );
        }
    }
}
