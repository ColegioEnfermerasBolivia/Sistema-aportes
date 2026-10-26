<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException(
                'UsuarioSeeder no se ejecuta en producción. Usa: php artisan app:crear-superadmin'
            );
        }

        $rolId = Rol::where('nombre', 'SUPER ADMIN')->value('id');

        if (!$rolId) {
            throw new \RuntimeException('No existe el rol SUPER ADMIN. Ejecuta primero RolesSeeder.');
        }

        $password = env('SUPERADMIN_INITIAL_PASSWORD') ?: Str::password(16);

        $usuario = Usuario::firstOrCreate(
            ['username' => 'superadmin'],
            [
                'ci'                    => '1234567',
                'nombres'               => 'Administrador',
                'apellidos'             => 'Sistema',
                'password'              => Hash::make($password),
                'debe_cambiar_password' => true,
                'cel'                   => '70000000',
                'rol_id'                => $rolId,
                'colegio_id'            => null,
                'estado'                => 'ACTIVO',
            ]
        );

        if ($usuario->wasRecentlyCreated && !env('SUPERADMIN_INITIAL_PASSWORD')) {
            $this->command->warn("Contraseña temporal generada: {$password}");
        }
    }
}