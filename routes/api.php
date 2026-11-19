<?php

use App\Http\Controllers\Api\AfiliadoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:public-api', 'api.key'])
    ->prefix('v1')
    ->group(function (): void {
        Route::get('/afiliados', [AfiliadoController::class, 'index'])
            ->name('api.v1.afiliados.index');
    });
