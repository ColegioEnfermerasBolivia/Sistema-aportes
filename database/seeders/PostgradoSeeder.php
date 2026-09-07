<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PostgradoSeeder extends Seeder
{
    public function run(): void
    {
        $postgrados = [
            [
                'nombre' => 'Médico Quirúrgica',
                'estado' => 'ACTIVO',
            ],
            [
                'nombre' => 'Enfermería Quirúrgica',
                'estado' => 'ACTIVO',
            ],
            [
                'nombre' => 'Enfermería en Medicina Crítica y Terapia Intensiva',
                'estado' => 'ACTIVO',
            ],
            [
                'nombre' => 'Enfermería Ginecoobstetricia',
                'estado' => 'ACTIVO',
            ],
            [
                'nombre' => 'Enfermería Pediátrica',
                'estado' => 'ACTIVO',
            ],
            [
                'nombre' => 'Enfermería de Salud Mental',
                'estado' => 'ACTIVO',
            ],
            [
                'nombre' => 'Enfermería en Salud Pública',
                'estado' => 'ACTIVO',
            ],
            [
                'nombre' => 'Enfermería en Administración de Servicios en Salud',
                'estado' => 'ACTIVO',
            ],
            [
                'nombre' => 'Educación Superior',
                'estado' => 'ACTIVO',
            ],
            [
                'nombre' => 'Investigación',
                'estado' => 'ACTIVO',
            ],
            [
                'nombre' => 'Geriatría y Gerontología',
                'estado' => 'ACTIVO',
            ],
        ];

        DB::table('postgrados')->insert($postgrados);
    }
}