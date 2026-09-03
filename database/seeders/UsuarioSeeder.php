<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        Usuario::create([
            'ci' => '1234567',
            'nombres' => 'Administrador',
            'apellidos' => 'Sistema',
            'username' => 'superadmin',
            'password' => Hash::make('Admin12345'),
            'debe_cambiar_password' => false,
            'cel' => '70000000',
            'rol_id' => 1,
            'colegio_id' => null,
            'estado' => 'ACTIVO',
        ]);
    }
}