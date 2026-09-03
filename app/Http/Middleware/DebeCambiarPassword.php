<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DebeCambiarPassword
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->debe_cambiar_password) {
            if (!$request->routeIs('cambiar-password', 'cambiar-password.actualizar')) {
                return redirect()->route('cambiar-password');
            }
        }
        return $next($request);
    }
}
