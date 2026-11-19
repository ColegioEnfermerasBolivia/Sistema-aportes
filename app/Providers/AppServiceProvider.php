<?php

namespace App\Providers;

use App\Models\Afiliado;
use App\Models\LoteVerificacion;
use App\Models\Pago;
use App\Models\Usuario;
use App\Policies\AfiliadoPolicy;
use App\Policies\LoteVerificacionPolicy;
use App\Policies\PagoPolicy;
use App\Policies\UsuarioPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('public-api', function (Request $request) {
            return Limit::perMinute(30)
                ->by($request->ip() ?? 'unknown');
        });

        Gate::policy(Usuario::class, UsuarioPolicy::class);
        Gate::policy(Afiliado::class, AfiliadoPolicy::class);
        Gate::policy(Pago::class, PagoPolicy::class);
        Gate::policy(LoteVerificacion::class, LoteVerificacionPolicy::class);
    }
}