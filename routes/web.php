<?php

use Illuminate\Support\Facades\Route;
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

Route::get('/prestamos', function () {
    return view('prestamos');
})->name('prestamos');

require __DIR__.'/auth.php';