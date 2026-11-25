<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AfiliadoController;
use App\Http\Controllers\InformacionLaboralController;
use App\Http\Controllers\AfiliadoPostgradoController;
use App\Http\Controllers\LoteVerificacionController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\AportesController;
use App\Http\Controllers\InformeController;
use App\Http\Controllers\CargaDatosController;
use App\Http\Controllers\AportesHistoricosController;
use App\Http\Controllers\TransferenciasController;
use App\Http\Controllers\SaldoHistoricoController;
use App\Http\Controllers\ObservacionesHistoricasController;
use App\Http\Controllers\ReportePagosLoteController;
use App\Http\Controllers\DevengadoController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [\App\Http\Controllers\AuthController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->middleware('throttle:5,1');

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

Route::get('/afiliados/{afiliado}/detalle',[AfiliadoController::class, 'detalle'])
    ->middleware(['auth', 'debe-cambiar-password', 'rol:SUPER ADMIN,ADMIN,USUARIO'])
    ->name('afiliados.detalle');

Route::get('/afiliados/{afiliado}/editar', [AfiliadoController::class, 'formularioEdicion'])
    ->middleware(['auth', 'debe-cambiar-password', 'rol:SUPER ADMIN,ADMIN,USUARIO'])
    ->name('afiliados.editar');

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

// =====================================================
// LOTES DE VERIFICACIÓN
// =====================================================

// Listar lotes
Route::get('/aportes/lotes', [LoteVerificacionController::class, 'index'])
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:SUPER ADMIN,ADMIN,USUARIO',
        'can:viewAny,App\Models\LoteVerificacion',
    ])
    ->name('lotes.index');


// Historial de lotes
Route::get('/aportes/lotes/historial', [LoteVerificacionController::class, 'historial'])
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:SUPER ADMIN,ADMIN,USUARIO',
        'can:viewAny,App\Models\LoteVerificacion',
    ])
    ->name('lotes.historial');


// Mostrar formulario para crear lote
Route::get('/aportes/lotes/crear', [LoteVerificacionController::class, 'create'])
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:USUARIO',
        'can:create,App\Models\LoteVerificacion',
    ])
    ->name('lotes.create');

// Crear lote
Route::post('/aportes/lotes', [LoteVerificacionController::class, 'store'])
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:USUARIO',
        'can:create,App\Models\LoteVerificacion',
    ])
    ->name('lotes.store');

// Cerrar y enviar lote
Route::patch('/lotes/{lote}/cerrar', [LoteVerificacionController::class, 'cerrar'])
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:USUARIO',
        'can:cerrar,lote',
    ])
    ->name('lotes.cerrar');

// Reabrir lote
Route::patch('/lotes/{lote}/reabrir', [LoteVerificacionController::class, 'reabrir'])
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:SUPER ADMIN',
        'can:reabrir,lote',
    ])
    ->name('lotes.reabrir');

// Iniciar revisión del lote
Route::post('/lotes/{lote}/revisar', [LoteVerificacionController::class, 'revisar'])
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:ADMIN',
        'can:revisar,lote',
    ])
    ->name('lotes.revisar');

// Previsualizar aportes que se generarán
Route::post('/lotes/{lote}/preview-aportes', [LoteVerificacionController::class, 'previewAportes'])
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:ADMIN',
        'can:previewAportes,lote',
    ])
    ->name('lotes.preview-aportes');

// Confirmar aportes y finalizar revisión
Route::post('/lotes/{lote}/confirmar-aportes', [LoteVerificacionController::class, 'confirmarAportes'])
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:ADMIN',
        'can:confirmarAportes,lote',
    ])
    ->name('lotes.confirmar-aportes');


// =====================================================
// PAGOS
// =====================================================

// Registrar pago
Route::post('/pagos', [PagoController::class, 'store'])
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:ADMIN,USUARIO',
    ])
    ->name('pagos.store');

Route::middleware(['auth', 'debe-cambiar-password', 'rol:SUPER ADMIN,ADMIN,USUARIO'])
    ->prefix('devengados')
    ->name('devengados.')
    ->controller(DevengadoController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/lotes/crear', 'create')
            ->middleware('rol:USUARIO')
            ->name('create');
        Route::post('/lotes', 'store')
            ->middleware('rol:USUARIO')
            ->name('store');
        Route::get('/buscar-afiliada', 'buscarAfiliada')
            ->middleware('rol:ADMIN,USUARIO')
            ->name('buscar-afiliada');
        Route::post('/pagos', 'storePayment')
            ->middleware('rol:USUARIO')
            ->name('pagos.store');
        Route::get('/pagos/{pago}/editar', 'editPayment')
            ->middleware('rol:USUARIO')
            ->name('pagos.edit');
        Route::put('/pagos/{pago}', 'updatePayment')
            ->middleware('rol:USUARIO')
            ->name('pagos.update');
        Route::get('/lotes/{lote}', 'show')->name('show');
        Route::patch('/lotes/{lote}/enviar', 'submit')
            ->middleware('rol:USUARIO')
            ->name('submit');
        Route::get('/lotes/{lote}/excel', 'excel')
            ->name('lotes.excel');
        Route::post('/lotes/{lote}/confirmar-pagos', 'confirmarPagos')
            ->middleware('rol:ADMIN')
            ->name('confirmar-pagos');
        Route::post('/periodos/{periodo}/exoneraciones', 'exonerate')
            ->middleware('rol:ADMIN')
            ->name('exoneraciones.store');
    });

// Modificar pago
Route::patch('/pagos/{pago}', [PagoController::class, 'update'])
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:USUARIO',
        'can:update,pago',
    ])
    ->name('pagos.update');

// Aportes
Route::get('/aportes', [AportesController::class, 'index'])
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:SUPER ADMIN,ADMIN,USUARIO',
    ])
    ->name('aportes.index');

//buscar afiliado por CI
Route::get('/afiliados/buscar-ci', [AfiliadoController::class, 'buscarPorCi'])
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:SUPER ADMIN,ADMIN,USUARIO',
    ])
    ->name('afiliados.buscar-ci');

//Rutas de Informes Afiliados
Route::get('reports/afiliados', [InformeController::class,'afiliados'])
    ->middleware([
        'auth', 
        'debe-cambiar-password',
        'rol:SUPER ADMIN,ADMIN,USUARIO',
    ])
    ->name('afiliadosReports.index');

//Rutas de exportacion de informes
Route::get('/reports/afiliados/excel', [InformeController::class, 'afiliadosExcel'])
    ->middleware(([
        'auth',
        'debe-cambiar-password',
        'rol:SUPER ADMIN,ADMIN,USUARIO',
    ]))
    ->name('afiliadosReports.excel');

Route::get('/reports/afiliados/pdf', [InformeController::class, 'afiliadosPdf'])
    ->middleware(([
        'auth',
        'debe-cambiar-password',
        'rol:SUPER ADMIN,ADMIN,USUARIO',
    ]))
    ->name('afiliadosReports.pdf');

//Rutas de Informes de Aportes
Route::get('/reports/aportes', [InformeController::class, 'aportes'])
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:SUPER ADMIN,ADMIN,USUARIO',
    ])
    ->name('aportesReports.index');

//Informes Aportes Excel
Route::get('/reports/aportes/excel',[InformeController::class, 'aportesExcel'])
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:SUPER ADMIN,ADMIN,USUARIO',
    ])
    ->name('aportesReports.excel');

//Informes Aportes PDF
Route::get('/reports/aportes/pdf',[InformeController::class, 'aportesPdf'])
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:SUPER ADMIN,ADMIN,USUARIO',
    ])
    ->name('aportesReports.pdf');

// Reporte de pagos por lote
Route::get('/reports/lotes', [ReportePagosLoteController::class, 'index'])
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:SUPER ADMIN,ADMIN,USUARIO',
    ])
    ->name('lotesReports.index');

Route::get(
    '/reports/lotes/{lote}/preview',
    [ReportePagosLoteController::class, 'preview']
)
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:SUPER ADMIN,ADMIN,USUARIO',
        'can:viewReport,lote',
    ])
    ->name('lotesReports.preview');

Route::get(
    '/reports/lotes/{lote}/excel',
    [ReportePagosLoteController::class, 'excel']
)
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:SUPER ADMIN,ADMIN,USUARIO',
        'can:viewReport,lote',
    ])
    ->name('lotesReports.excel');

//CARGA DE DATOS
Route::get('/carga-datos',[CargaDatosController::class, 'index'])
    ->middleware([
        'auth',
        'debe-cambiar-password',
        'rol:SUPER ADMIN',
    ])
    ->name('carga-datos.index');


Route::post('/carga-datos/afiliados/validar', [CargaDatosController::class, 'validarAfiliados'])
    ->middleware([
            'auth',
            'debe-cambiar-password',
            'rol:SUPER ADMIN',
        ])
    ->name('carga-datos.afiliados.validar');

Route::post('/carga-datos/afiliados/confirmar', [CargaDatosController::class, 'confirmarAfiliados'])
    ->middleware([
            'auth',
            'debe-cambiar-password',
            'rol:SUPER ADMIN',
        ])
    ->name('carga-datos.afiliados.confirmar');

Route::middleware(['auth','debe-cambiar-password','rol:SUPER ADMIN',])->group(function() {
     Route::get(
        '/aportes-historicos',
        [AportesHistoricosController::class, 'index']
    )->name('aportesHistoricos.index');

    Route::post(
        '/aportes-historicos/analizar',
        [AportesHistoricosController::class, 'analizar']
    )->name('aportesHistoricos.analizar');

    Route::post(
        '/aportes-historicos/importar',
        [AportesHistoricosController::class, 'importar']
    )->name('aportesHistoricos.importar');

    Route::post(
        '/aportes-historicos/cancelar',
        [AportesHistoricosController::class, 'cancelar']
    )->name('aportesHistoricos.cancelar');
});

Route::middleware(['auth','debe-cambiar-password','rol:SUPER ADMIN',])
    ->prefix('transferencias')
    ->name('transferencias.')
    ->controller(TransferenciasController::class)
    ->group(function () {
        // Pantalla principal
        Route::get('/', 'index')
            ->name('index');

        // Subir y analizar el CSV
        Route::post('/analizar', 'analizar')
            ->name('analizar');

        // Ver resultados del análisis
        Route::get('/revision', 'revision')
            ->name('revision');

        // Confirmar la importación
        Route::post('/confirmar', 'confirmar')
            ->name('confirmar');

        // Cancelar la importación pendiente
        Route::post('/cancelar', 'cancelar')
            ->name('cancelar');

        Route::post('/validar-fila', 'validarFila')
            ->name('validar-fila');
    });

Route::middleware(['auth','debe-cambiar-password','rol:SUPER ADMIN',])
    ->prefix('saldos-historicos')
    ->name('saldos.')
    ->controller(SaldoHistoricoController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/previsualizar', 'preview')->name('preview');
        Route::put('/{token}/corregir', 'corregir')->name('corregir');
        Route::post('/{token}/confirmar', 'confirmar')->name('confirmar');
    });

Route::middleware(['auth','debe-cambiar-password','rol:SUPER ADMIN',])
    ->prefix('observaciones-historicas')
     ->name('observacionesHistoricas.')
    ->controller(ObservacionesHistoricasController::class)
    ->group(function () {
        Route::post('/previsualizar', 'preview')
            ->name('previsualizarObservaciones');
        Route::post('/{token}/confirmar', 'confirmar')
            ->name('confirmar');
    });
