<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\RecuperarClaveController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // El registro público está deshabilitado: las cuentas del personal
    // las crea el administrador (tabla "cuenta").

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // HU-195 / HU-206: recuperación de contraseña por usuario (cuenta) y correo del personal.
    Route::get('forgot-password', [RecuperarClaveController::class, 'solicitar'])
        ->name('password.request');

    Route::post('forgot-password', [RecuperarClaveController::class, 'enviar'])
        ->middleware('throttle:10,1')
        ->name('password.email');

    Route::get('reset-password/{cuenta}/{token}', [RecuperarClaveController::class, 'formulario'])
        ->whereNumber('cuenta')
        ->name('password.reset');

    Route::post('reset-password', [RecuperarClaveController::class, 'guardar'])
        ->middleware('throttle:10,1')
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
