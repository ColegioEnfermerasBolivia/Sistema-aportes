<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [\App\Http\Controllers\AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login']);

Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('/cambiar-password', [\App\Http\Controllers\AuthController::class, 'showCambiarPassword'])
    ->middleware('auth')
    ->name('cambiar-password');

Route::post('/cambiar-password', [\App\Http\Controllers\AuthController::class, 'cambiarPassword'])
    ->middleware('auth')
    ->name('cambiar-password.actualizar');

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'debe-cambiar-password'])
    ->name('dashboard');
