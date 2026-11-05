<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ControlAccesoController;
use App\Http\Controllers\PrestamoController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SuscripcionController;
use App\Http\Controllers\PagoController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
/*
|--------------------------------------------------------------------------
| Usuarios
|--------------------------------------------------------------------------
*/

Route::get('/usuarios', [UsuarioController::class, 'index'])
    ->name('usuarios');

Route::post('/usuarios', [UsuarioController::class, 'store'])
    ->name('usuarios.store');

/*
|--------------------------------------------------------------------------
| Controlador de accesos
|--------------------------------------------------------------------------
*/

// para mostrar la vista
Route::get('/control-acceso', [ControlAccesoController::class, 'index'])
    ->name('control-acceso.index');
// para consultar los registros
Route::get('/control-acceso/consultar', [ControlAccesoController::class, 'consultar'])
    ->name('control-acceso.consultar');
// Registrar un ingreso
Route::post('/control-acceso/registrar-ingreso', [ControlAccesoController::class, 'registrarIngreso'])
    ->name('control-acceso.registrar-ingreso');
// Registrar una salida
Route::post('/control-acceso/salida/{usuario_id}', [ControlAccesoController::class, 'registrarSalida'])
    ->name('control-acceso.salida');

/*
|--------------------------------------------------------------------------
| Prestamos
|--------------------------------------------------------------------------
*/
Route::get('/prestamos', [PrestamoController::class, 'index'])
    ->name('prestamos');

Route::post('/prestamos', [PrestamoController::class, 'store'])
    ->name('prestamos.store');

Route::post('/prestamos/{prestamoId}/devolucion', [PrestamoController::class, 'devolver'])
    ->name('prestamos.devolver');

/*
|--------------------------------------------------------------------------
| Inventario
|--------------------------------------------------------------------------
*/

/* ABRE RUTAS DE INVENTARIO */
// muestra el inventario
Route::get('/inventario', [InventarioController::class, 'index'])
    ->name('inventario.index');
// genera nombres
Route::post('/inventario/registrar', [InventarioController::class, 'store'])
    ->name('inventario.store');
/* CIERRA RUTAS DE INVENTARIO */


/*
|--------------------------------------------------------------------------
| Planes
|--------------------------------------------------------------------------
*/

Route::get('/planes', [PlanController::class, 'index'])
    ->name('planes.index');

Route::post('/planes', [PlanController::class, 'store'])
    ->name('planes.store');

Route::get('/planes/{id}/editar', [PlanController::class, 'edit'])
    ->name('planes.edit');

Route::put('/planes/{id}', [PlanController::class, 'update'])
    ->name('planes.update');

Route::delete('/planes/{id}', [PlanController::class, 'destroy'])
    ->name('planes.destroy');

Route::patch('/planes/{id}/activar', [PlanController::class, 'activar'])
    ->name('planes.activar');

/*
|--------------------------------------------------------------------------
| Suscripciones
|--------------------------------------------------------------------------
*/

Route::get('/suscripciones', [SuscripcionController::class, 'index']);
Route::get('/suscripciones/crear', [SuscripcionController::class, 'create']);
Route::post('/suscripciones', [SuscripcionController::class, 'store']);

// ==========================================
// RUTAS DEL MÓDULO DE PAGOS
// ==========================================

Route::get('/pago', [PagoController::class, 'index'])
    ->name('pago.index');

Route::post('/pago', [PagoController::class, 'store'])
    ->name('pago.store');

Route::patch('/pago/{id}/revision', [PagoController::class, 'revisar'])
    ->name('pago.revisar');



/*
|--------------------------------------------------------------------------
| Otras vistas
|--------------------------------------------------------------------------
*/
Route::get('/index', function () {
    return view('index');
})->name('index');


require __DIR__.'/auth.php';