<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GradoAcademicoSeeder extends Seeder
{
    public function run(): void
    {
        $grados = [
            [
                'nombre' => 'ESPECIALIDAD',
                'estado' => 'ACTIVO',
            ],
            [
                'nombre' => 'MAESTRÍA',
                'estado' => 'ACTIVO',
            ],
        ];

        DB::table('grados_academicos')->insert($grados);
    }
}