<?php

namespace Database\Seeders;

use App\Models\Colegio;
use Illuminate\Database\Seeder;

class ColegioSeeder extends Seeder
{
    public function run(): void
    {
        Colegio::insert([
            [
                'id' => 1,
                'nombre' => 'Colegio Departamental de La Paz',
                'tipo' => 'DEPARTAMENTAL',
                'colegio_padre_id' => null,
                'estado' => 'ACTIVO',
            ],
            [
                'id' => 2,
                'nombre' => 'Colegio Departamental de Santa Cruz',
                'tipo' => 'DEPARTAMENTAL',
                'colegio_padre_id' => null,
                'estado' => 'ACTIVO',
            ],
            [
                'id' => 3,
                'nombre' => 'Colegio Departamental de Cochabamba',
                'tipo' => 'DEPARTAMENTAL',
                'colegio_padre_id' => null,
                'estado' => 'ACTIVO',
            ],
            [
                'id' => 4,
                'nombre' => 'Colegio Departamental de Oruro',
                'tipo' => 'DEPARTAMENTAL',
                'colegio_padre_id' => null,
                'estado' => 'ACTIVO',
            ],
            [
                'id' => 5,
                'nombre' => 'Colegio Departamental de Potosí',
                'tipo' => 'DEPARTAMENTAL',
                'colegio_padre_id' => null,
                'estado' => 'ACTIVO',
            ],
            [
                'id' => 6,
                'nombre' => 'Colegio Departamental de Pando',
                'tipo' => 'DEPARTAMENTAL',
                'colegio_padre_id' => null,
                'estado' => 'ACTIVO',
            ],
            [
                'id' => 7,
                'nombre' => 'Colegio Departamental de Chuquisaca',
                'tipo' => 'DEPARTAMENTAL',
                'colegio_padre_id' => null,
                'estado' => 'ACTIVO',
            ],
            [
                'id' => 8,
                'nombre' => 'Colegio Departamental de Tarija',
                'tipo' => 'DEPARTAMENTAL',
                'colegio_padre_id' => null,
                'estado' => 'ACTIVO',
            ],
            [
                'id' => 9,
                'nombre' => 'Colegio Departamental de Beni',
                'tipo' => 'DEPARTAMENTAL',
                'colegio_padre_id' => null,
                'estado' => 'ACTIVO',
            ],
            [
                'id' => 10,
                'nombre' => 'Colegio Regional de El Alto',
                'tipo' => 'REGIONAL',
                'colegio_padre_id' => null,
                'estado' => 'ACTIVO',
            ],
            [
                'id' => 12,
                'nombre' => 'Colegio Regional de Camiri',
                'tipo' => 'REGIONAL',
                'colegio_padre_id' => 2,
                'estado' => 'ACTIVO',
            ],
            [
                'id' => 13,
                'nombre' => 'Colegio Regional de Catavi',
                'tipo' => 'REGIONAL',
                'colegio_padre_id' => 5,
                'estado' => 'ACTIVO',
            ],
            [
                'id' => 11,
                'nombre' => 'Colegio Regional de Tupiza',
                'tipo' => 'REGIONAL',
                'colegio_padre_id' => 5,
                'estado' => 'ACTIVO',
            ],
            [
                'id' => 14,
                'nombre' => 'Colegio Regional de Yacuiba',
                'tipo' => 'REGIONAL',
                'colegio_padre_id' => 8,
                'estado' => 'ACTIVO',
            ],
        ]);
    }
}