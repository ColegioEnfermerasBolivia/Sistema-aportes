<?php

namespace App\Http\Controllers;

use App\Models\LoginLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credenciales = $request->validate([
            'username' => ['required'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credenciales)) {
            $request->session()->regenerate();

            if (Auth::user()->estado !== 'ACTIVO') {
                LoginLog::create([
                    'usuario_id' => Auth::id(),
                    'username' => $credenciales['username'],
                    'resultado' => 'FALLIDO',
                    'motivo' => 'USUARIO_INACTIVO',
                    'ip' => $request->ip(),
                ]);

                Auth::logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'username' => 'El usuario se encuentra inactivo.',
                ])->onlyInput('username');
            }

            LoginLog::create([
                'usuario_id' => Auth::id(),
                'username' => $credenciales['username'],
                'resultado' => 'EXITOSO',
                'motivo' => null,
                'ip' => $request->ip(),
            ]);

            if (Auth::user()->debe_cambiar_password) {
                return redirect()->route('cambiar-password');
            }

            return redirect()->route('dashboard');
        }

        LoginLog::create([
            'usuario_id' => null,
            'username' => $credenciales['username'],
            'resultado' => 'FALLIDO',
            'motivo' => 'CREDENCIALES_INVALIDAS',
            'ip' => $request->ip(),
        ]);

        return back()
            ->with('login_error', true)
            ->onlyInput('username');
    }

    public function showCambiarPassword()
    {
        return view('auth.cambiar-password');
    }

    public function cambiarPassword(Request $request)
    {
        $datos = $request->validate([
            'password_actual' => ['required'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:password_actual'],
        ]);

        $usuario = Auth::user();

        if (!Hash::check($datos['password_actual'], $usuario->password)) {
            return back()->withErrors([
                'password_actual' => 'La contraseña actual no es correcta.',
            ]);
        }

        $usuario->password = Hash::make($datos['password']);
        $usuario->debe_cambiar_password = false;
        $usuario->save();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
