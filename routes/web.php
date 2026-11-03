<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ControlAccesoController;

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

Route::get('/prestamos', function () {
    return view('prestamos');
})->name('prestamos');


Route::get('/inventario', function () {
    return view('inventario');
})->name('inventario');


Route::get('/pago', function () {
    return view('pago');
})->name('pago');

Route::get('/planes', function () {
    return view('planes');
})->name('planes');


Route::get('/index', function () {
    return view('index');
})->name('index');

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


require __DIR__.'/auth.php';