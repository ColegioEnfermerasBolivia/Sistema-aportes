<?php

namespace App\Providers;

use App\Models\Usuario;
use App\Models\Afiliado;
use App\Policies\UsuarioPolicy;
use App\Policies\AfiliadoPolicy;
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
    }
}