<?php

namespace App\Console\Commands;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CrearSuperAdmin extends Command
{
    protected $signature = 'app:crear-superadmin';
    protected $description = 'Crea el SUPER ADMIN inicial (pide la contraseña por consola)';

    public function handle(): int
    {
        $rolId = Rol::where('nombre', 'SUPER ADMIN')->value('id');

        if (!$rolId) {
            $this->error('No existe el rol SUPER ADMIN. Ejecuta primero: php artisan db:seed --class=RolesSeeder');
            return self::FAILURE;
        }

        if (Usuario::where('rol_id', $rolId)->exists()) {
            $this->error('Ya existe un SUPER ADMIN. Adminístralo desde la pantalla de usuarios.');
            return self::FAILURE;
        }

        $username = $this->ask('Usuario');
        $ci       = $this->ask('CI');
        $nombres  = $this->ask('Nombres');
        $apellidos = $this->ask('Apellidos');
        $cel      = $this->ask('Celular');
        $password = $this->secret('Contraseña (mínimo 12 caracteres)');
        $confirma = $this->secret('Repite la contraseña');

        if ($password !== $confirma || strlen((string) $password) < 12) {
            $this->error('Las contraseñas no coinciden o tienen menos de 12 caracteres.');
            return self::FAILURE;
        }

        if (Usuario::where('username', $username)->orWhere('ci', $ci)->exists()) {
            $this->error('Ya existe un usuario con ese nombre de usuario o CI.');
            return self::FAILURE;
        }

        Usuario::create([
            'ci'                    => $ci,
            'nombres'               => $nombres,
            'apellidos'             => $apellidos,
            'username'              => $username,
            'password'              => Hash::make($password),
            'debe_cambiar_password' => true,
            'cel'                   => $cel,
            'rol_id'                => $rolId,
            'colegio_id'            => null,
            'estado'                => 'ACTIVO',
        ]);

        $this->info('SUPER ADMIN creado. Deberá cambiar la contraseña en su primer ingreso.');
        return self::SUCCESS;
    }
}