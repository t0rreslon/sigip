<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ISO 25010 – Autenticidad: política de contraseñas para todas las cuentas
        // (Password::defaults() se usa al crear, cambiar y restablecer contraseñas).
        Password::defaults(fn () => Password::min(10)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols());

        if ($this->app->isProduction()) {
            // ISO 25010 – Confidencialidad / Resistencia: en producción todo va por HTTPS.
            URL::forceScheme('https');

            // Con APP_DEBUG activo los errores muestran código y variables de entorno.
            if (config('app.debug')) {
                Log::critical('APP_DEBUG está activo en producción. Cámbialo a false en el archivo .env.');
            }
        }
    }
}
