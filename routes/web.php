<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ControlAccesoController;
use App\Http\Controllers\PrestamoController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SuscripcionController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\EspacioController;
use App\Http\Controllers\ReservaController;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('index')
        : redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Rutas protegidas
|--------------------------------------------------------------------------
| Todas requieren iniciar sesión con una cuenta activa de Administrador u
| Operador. Las acciones marcadas "solo Administrador" usan además el
| middleware tipo_cuenta:Administrador.
*/

Route::middleware(['auth', 'tipo_cuenta:Administrador,Operador'])->group(function () {

    // Página de inicio después de iniciar sesión.
    Route::get('/index', fn () => redirect()->route('usuarios'))->name('index');

    Route::get('/dashboard', fn () => redirect()->route('index'))->name('dashboard');

    /*
    |----------------------------------------------------------------------
    | Usuarios
    |----------------------------------------------------------------------
    */
    Route::get('/usuarios', [UsuarioController::class, 'index'])
        ->name('usuarios');

    Route::post('/usuarios', [UsuarioController::class, 'store'])
        ->name('usuarios.store');

    Route::put('/usuarios/{id}', [UsuarioController::class, 'update'])
        ->whereNumber('id')
        ->name('usuarios.update');

    /*
    |----------------------------------------------------------------------
    | Control de acceso
    |----------------------------------------------------------------------
    */
    Route::get('/control-acceso', [ControlAccesoController::class, 'index'])
        ->name('control-acceso.index');

    Route::get('/control-acceso/consultar', [ControlAccesoController::class, 'consultar'])
        ->name('control-acceso.consultar');

    Route::post('/control-acceso/registrar-ingreso', [ControlAccesoController::class, 'registrarIngreso'])
        ->name('control-acceso.registrar-ingreso');

    Route::post('/control-acceso/salida/{usuario_id}', [ControlAccesoController::class, 'registrarSalida'])
        ->whereNumber('usuario_id')
        ->name('control-acceso.salida');

    /*
    |----------------------------------------------------------------------
    | Préstamos
    |----------------------------------------------------------------------
    */
    Route::get('/prestamos', [PrestamoController::class, 'index'])
        ->name('prestamos');

    Route::post('/prestamos', [PrestamoController::class, 'store'])
        ->name('prestamos.store');

    Route::post('/prestamos/{prestamoId}/devolucion', [PrestamoController::class, 'devolver'])
        ->whereNumber('prestamoId')
        ->name('prestamos.devolver');

    /*
    |----------------------------------------------------------------------
    | Inventario
    |----------------------------------------------------------------------
    */
    Route::get('/inventario', [InventarioController::class, 'index'])
        ->name('inventario.index');

    Route::post('/inventario/registrar', [InventarioController::class, 'store'])
        ->middleware('tipo_cuenta:Administrador')
        ->name('inventario.store');

    /*
    |----------------------------------------------------------------------
    | Planes y suscripciones
    |----------------------------------------------------------------------
    */
    Route::get('/planes', [PlanController::class, 'index'])
        ->name('planes.index');

    Route::middleware('tipo_cuenta:Administrador')->group(function () {
        Route::post('/planes', [PlanController::class, 'store'])
            ->name('planes.store');

        Route::put('/planes/{id}', [PlanController::class, 'update'])
            ->whereNumber('id')
            ->name('planes.update');

        Route::delete('/planes/{id}', [PlanController::class, 'destroy'])
            ->whereNumber('id')
            ->name('planes.destroy');

        Route::patch('/planes/{id}/activar', [PlanController::class, 'activar'])
            ->whereNumber('id')
            ->name('planes.activar');
    });

    Route::get('/suscripciones', fn () => redirect()->route('planes.index'))
        ->name('suscripciones.index');

    Route::post('/suscripciones', [SuscripcionController::class, 'store'])
        ->name('suscripciones.store');

    Route::put('/suscripciones/{id}', [SuscripcionController::class, 'update'])
        ->whereNumber('id')
        ->name('suscripciones.update');

    /*
    |----------------------------------------------------------------------
    | Pagos
    |----------------------------------------------------------------------
    */
    Route::get('/pago', [PagoController::class, 'index'])
        ->name('pago.index');

    Route::post('/pago', [PagoController::class, 'store'])
        ->name('pago.store');

    Route::patch('/pago/{id}/revision', [PagoController::class, 'revisar'])
        ->whereNumber('id')
        ->name('pago.revisar');

    /*
    |----------------------------------------------------------------------
    | Espacios
    |----------------------------------------------------------------------
    */
    Route::get('/espacios', [EspacioController::class, 'index'])
        ->name('espacios.index');

    Route::middleware('tipo_cuenta:Administrador')->group(function () {
        Route::post('/espacios', [EspacioController::class, 'store'])
            ->name('espacios.store');

        Route::patch('/espacios/{id}/estado', [EspacioController::class, 'cambiarEstado'])
            ->whereNumber('id')
            ->name('espacios.estado');
    });

    /*
    |----------------------------------------------------------------------
    | Reservas de espacios
    |----------------------------------------------------------------------
    */
    Route::get('/reservas', [ReservaController::class, 'index'])
        ->name('reservas.index');

    Route::post('/reservas', [ReservaController::class, 'store'])
        ->name('reservas.store');

    Route::patch('/reservas/{id}/cancelar', [ReservaController::class, 'cancelar'])
        ->whereNumber('id')
        ->name('reservas.cancelar');
});

require __DIR__.'/auth.php';
