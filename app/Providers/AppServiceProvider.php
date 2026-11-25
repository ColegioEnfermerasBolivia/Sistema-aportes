<?php

namespace App\Providers;

use App\Models\Afiliado;
use App\Models\DevengadoLote;
use App\Models\DevengadoPago;
use App\Models\DevengadoPeriodo;
use App\Models\LoteVerificacion;
use App\Models\Pago;
use App\Models\Usuario;
use App\Policies\AfiliadoPolicy;
use App\Policies\DevengadoLotePolicy;
use App\Policies\DevengadoPagoPolicy;
use App\Policies\DevengadoPeriodoPolicy;
use App\Policies\LoteVerificacionPolicy;
use App\Policies\PagoPolicy;
use App\Policies\UsuarioPolicy;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(Usuario::class, UsuarioPolicy::class);
        Gate::policy(Afiliado::class, AfiliadoPolicy::class);
        Gate::policy(Pago::class, PagoPolicy::class);
        Gate::policy(LoteVerificacion::class, LoteVerificacionPolicy::class);
        Gate::policy(DevengadoLote::class, DevengadoLotePolicy::class);
        Gate::policy(DevengadoPeriodo::class, DevengadoPeriodoPolicy::class);
        Gate::policy(DevengadoPago::class, DevengadoPagoPolicy::class);
    }
}
