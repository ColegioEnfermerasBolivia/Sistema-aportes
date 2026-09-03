<?php

namespace App\Http\Controllers;

use App\Models\Colegio;
use App\Models\usuario;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
   public function index()
    {
        $usuarios = Usuario::with(['rol', 'colegio'])
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get();

        $colegios = Colegio::where('estado', 'ACTIVO')
            ->orderBy('nombre')
            ->get();

        $roles = Rol::orderBy('id')->get();

        return view('usuarios.index', compact(
            'usuarios',
            'colegios',
            'roles'
        ));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'ci' => [
                'required',
                'string',
                'max:20',
                'unique:usuarios,ci',
            ],

            'nombres' => [
                'required',
                'string',
                'max:100',
            ],

            'apellidos' => [
                'required',
                'string',
                'max:100',
            ],

            'username' => [
                'required',
                'string',
                'max:50',
                'unique:usuarios,username',
            ],

            'cel' => [
                'required',
                'string',
                'max:20',
            ],

            'rol_id' => [
                'required',
                Rule::exists('roles', 'id'),
            ],

            'colegio_id' => [
                'nullable',
                Rule::exists('colegios', 'id'),
            ],
        ]);

        // Un USUARIO debe tener colegio.
        if ($datos['rol_id'] == 3 && empty($datos['colegio_id'])) {
            return back()
                ->withErrors([
                    'colegio_id' => 'El usuario con rol USUARIO debe tener un colegio asignado.',
                ])
                ->withInput();
        }

        // SUPER ADMIN y ADMIN no deben tener colegio.
        if (in_array($datos['rol_id'], [1, 2])) {
            $datos['colegio_id'] = null;
        }

        $passwordTemporal = Str::password(10);

        $datos['password'] = Hash::make($passwordTemporal);
        $datos['debe_cambiar_password'] = true;
        $datos['estado'] = 'ACTIVO';

        Usuario::create($datos);

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario creado correctamente.')
            ->with('usuario_creado', [
                'username' => $datos['username'],
                'password_temporal' => $passwordTemporal,
            ]);
    }

    public function edit(Usuario $usuario)
    {
        Gate::authorize('update', $usuario);

        $colegios = Colegio::where('estado', 'ACTIVO')
            ->orderBy('nombre')
            ->get();

        return view('usuarios.edit', compact('usuario', 'colegios'));
    }

    public function update(Request $request, Usuario $usuario)
    {
        Gate::authorize('update', $usuario);

        $datos = $request->validate([
            'ci' => [
                'required',
                'string',
                'max:20',
                Rule::unique('usuarios', 'ci')->ignore($usuario->id),
            ],

            'nombres' => [
                'required',
                'string',
                'max:100',
            ],

            'apellidos' => [
                'required',
                'string',
                'max:100',
            ],

            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('usuarios', 'username')->ignore($usuario->id),
            ],

            'cel' => [
                'required',
                'string',
                'max:20',
            ],

            'rol_id' => [
                'required',
                Rule::exists('roles', 'id'),
            ],

            'estado' => [
                'required',
                Rule::in([
                    'ACTIVO',
                    'INACTIVO',
                ]),
            ],

            'colegio_id' => [
                'nullable',
                Rule::exists('colegios', 'id'),
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Datos básicos
        |--------------------------------------------------------------------------
        */

        $usuario->ci = $datos['ci'];
        $usuario->nombres = $datos['nombres'];
        $usuario->apellidos = $datos['apellidos'];
        $usuario->username = $datos['username'];
        $usuario->cel = $datos['cel'];


        /*
        |--------------------------------------------------------------------------
        | Rol
        |--------------------------------------------------------------------------
        */

        if ($datos['rol_id'] != $usuario->rol_id) {

            Gate::authorize('changeRole', $usuario);

            $usuario->rol_id = $datos['rol_id'];
        }


        /*
        |--------------------------------------------------------------------------
        | Estado
        |--------------------------------------------------------------------------
        */

        if ($datos['estado'] !== $usuario->estado) {

            Gate::authorize('changeEstado', $usuario);

            $usuario->estado = $datos['estado'];
        }


        /*
        |--------------------------------------------------------------------------
        | Colegio
        |--------------------------------------------------------------------------
        */

        if ($datos['colegio_id'] != $usuario->colegio_id) {

            Gate::authorize('changeColegio', $usuario);

            $usuario->colegio_id = $datos['colegio_id'];
        }


        /*
        |--------------------------------------------------------------------------
        | Obtener rol final
        |--------------------------------------------------------------------------
        */

        $rolFinal = Rol::findOrFail($usuario->rol_id);


        /*
        |--------------------------------------------------------------------------
        | Reglas del colegio según el rol
        |--------------------------------------------------------------------------
        */

        if ($rolFinal->nombre === 'USUARIO') {

            if (empty($usuario->colegio_id)) {
                return back()
                    ->withErrors([
                        'colegio_id' =>
                            'El usuario con rol USUARIO debe tener un colegio asignado.',
                    ])
                    ->withInput();
            }

        } else {

            $usuario->colegio_id = null;
        }


        $usuario->save();

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function cambiarEstado(Usuario $usuario)
    {
        Gate::authorize('changeEstado', $usuario);

        $usuario->estado = $usuario->estado === 'ACTIVO'
            ? 'INACTIVO'
            : 'ACTIVO';

        $usuario->save();

        return redirect()
            ->route('usuarios.index')
            ->with(
                'success',
                $usuario->estado === 'ACTIVO'
                    ? 'Usuario activado correctamente.'
                    : 'Usuario desactivado correctamente.'
            );
    }

    public function resetPassword(Usuario $usuario)
    {
        Gate::authorize('resetPassword', $usuario);

        $passwordTemporal = Str::password(10);

        $usuario->password = Hash::make($passwordTemporal);
        $usuario->debe_cambiar_password = true;

        $usuario->save();

        return redirect()
            ->route('usuarios.index')
            ->with('password_restablecida', [
                'username' => $usuario->username,
                'password_temporal' => $passwordTemporal,
            ]);
    }
}