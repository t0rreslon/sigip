<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tipo_cuenta' => \App\Http\Middleware\TipoCuenta::class,
        ]);

        // HU-207: registra las acciones que modifican datos.
        $middleware->web(append: [
            \App\Http\Middleware\RegistrarMovimiento::class,
        ]);

        // ISO 25010 – Resistencia: cabeceras de seguridad en todas las respuestas.
        $middleware->append(\App\Http\Middleware\CabecerasSeguridad::class);

        // HU-63: limpia bytes nulos y caracteres de control antes de validar.
        $middleware->prepend(\App\Http\Middleware\LimpiarEntrada::class);

        // Los invitados que intenten entrar a una ruta protegida van al login.
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('index'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
