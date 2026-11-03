<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ControlAccesoController;

use App\Http\Controllers\UsuarioController;


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
| Otras vistas
|--------------------------------------------------------------------------
*/

Route::get('/control-acceso', function () {
    return view('controlacceso');
})->name('controlacceso');

/* RUTAS DE TIPO GET Y POST DE CONTROL DE ACCESO USANDO EL CONTROLADOR */
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

/* CIERRAN RUTAS DE TIPO GET Y POST DE CONTROL DE ACCESO USANDO CONTROLADORES */

Route::get('/prestamos', function () {
    return view('prestamos');
})->name('prestamos');

require __DIR__.'/auth.php';