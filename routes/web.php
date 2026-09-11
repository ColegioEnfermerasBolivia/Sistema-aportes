<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AfiliadoController;
use App\Http\Controllers\InformacionLaboralController;
use App\Http\Controllers\AfiliadoPostgradoController;
use App\Http\Controllers\LoteVerificacionController;

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

Route::get('/usuarios', [UsuarioController::class, 'index'])
    ->middleware(['auth', 'debe-cambiar-password', 'rol:SUPER ADMIN,ADMIN'])
    ->name('usuarios.index');

Route::post('/usuarios', [UsuarioController::class, 'store'])
    ->middleware(['auth', 'debe-cambiar-password', 'rol:SUPER ADMIN,ADMIN'])
    ->name('usuarios.store');

Route::get('/usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])
    ->middleware(['auth', 'debe-cambiar-password', 'rol:SUPER ADMIN,ADMIN'])
    ->name('usuarios.edit');

Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])
    ->middleware(['auth', 'debe-cambiar-password', 'rol:SUPER ADMIN,ADMIN'])
    ->name('usuarios.update');

Route::patch('/usuarios/{usuario}/estado', [UsuarioController::class, 'cambiarEstado'])
    ->middleware(['auth', 'debe-cambiar-password', 'rol:SUPER ADMIN,ADMIN'])
    ->name('usuarios.estado');

Route::patch('/usuarios/{usuario}/reset-password', [UsuarioController::class, 'resetPassword'])
    ->middleware(['auth', 'debe-cambiar-password', 'rol:SUPER ADMIN,ADMIN'])
    ->name('usuarios.reset-password');

Route::get('/afiliados', [AfiliadoController::class, 'index'])
    ->middleware(['auth', 'debe-cambiar-password', 'rol:SUPER ADMIN,ADMIN,USUARIO'])
    ->name('afiliados.index');

Route::post('/afiliados', [AfiliadoController::class, 'store'])
    ->middleware(['auth', 'debe-cambiar-password', 'rol:SUPER ADMIN,ADMIN,USUARIO'])
    ->name('afiliados.store');

Route::put('/afiliados/{afiliado}', [AfiliadoController::class, 'update'])
    ->middleware(['auth', 'debe-cambiar-password', 'rol:SUPER ADMIN,ADMIN,USUARIO'])
    ->name('afiliados.update');

Route::patch('/afiliados/{afiliado}/estado', [AfiliadoController::class, 'cambiarEstado'])
    ->middleware(['auth', 'debe-cambiar-password', 'rol:SUPER ADMIN,ADMIN,USUARIO'])
    ->name('afiliados.estado');

Route::get(
    '/afiliados/{afiliado}/informacion-laboral',
    [InformacionLaboralController::class, 'index']
)
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:SUPER ADMIN,ADMIN,USUARIO'
    ])
    ->name('informacion-laboral.index');

Route::post(
    '/afiliados/{afiliado}/informacion-laboral',
    [InformacionLaboralController::class, 'store']
)
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:SUPER ADMIN,ADMIN,USUARIO'
    ])
    ->name('informacion-laboral.store');

Route::put(
    '/informacion-laboral/{informacionLaboral}',
    [InformacionLaboralController::class, 'update']
)
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:SUPER ADMIN,ADMIN,USUARIO'
    ])
    ->name('informacion-laboral.update');

Route::post('/afiliados/{afiliado}/postgrados', [AfiliadoPostgradoController::class, 'store'])
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:SUPER ADMIN,ADMIN,USUARIO'
    ])
    ->name('afiliado-postgrado.store');

Route::put('/afiliado-postgrado/{afiliadoPostgrado}', [AfiliadoPostgradoController::class, 'update'])
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:SUPER ADMIN,ADMIN,USUARIO'
    ])
    ->name('afiliado-postgrado.update');

Route::delete('/afiliado-postgrado/{afiliadoPostgrado}', [AfiliadoPostgradoController::class, 'destroy'])
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:SUPER ADMIN,ADMIN,USUARIO'
    ])
    ->name('afiliado-postgrado.destroy');

/*LOTES*/

Route::get('/aportes/lotes', [LoteVerificacionController::class, 'index'])
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:SUPER ADMIN,ADMIN,USUARIO',
        'can:viewAny,App\Models\LoteVerificacion',
    ])
    ->name('lotes.index');

Route::post('/lotes/{lote}/revisar', [LoteVerificacionController::class, 'revisar'])
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:ADMIN',
        'can:revisar,lote',
    ])
    ->name('lotes.revisar');

Route::post('/lotes/{lote}/preview-aportes', [LoteVerificacionController::class, 'previewAportes'])
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:ADMIN',
        'can:previewAportes,lote',
    ])
    ->name('lotes.preview-aportes');

Route::post('/lotes/{lote}/confirmar-aportes', [LoteVerificacionController::class, 'confirmarAportes'])
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:ADMIN',
        'can:confirmarAportes,lote',
    ])
    ->name('lotes.confirmar-aportes');

Route::get('/aportes/lotes/{lote}', [LoteVerificacionController::class, 'show'])
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:SUPER ADMIN,ADMIN,USUARIO',
        'can:view,lote',
    ])
    ->name('lotes.show');