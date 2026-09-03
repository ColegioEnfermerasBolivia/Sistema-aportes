<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $usuario = auth()->user();

        $esAdministrador = in_array($usuario->rol->nombre, [
            'ADMIN',
            'SUPER ADMIN',
        ]);

        return view('dashboard', [
            'usuario' => $usuario,
            'esAdministrador' => $esAdministrador,
        ]);
    }
}