<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

/**
 * Igual que la validación CSRF de Laravel, pero sin la cookie XSRF-TOKEN.
 *
 * Esa cookie no puede ser HttpOnly (la lee JavaScript) y SIGIP no la necesita:
 * los formularios envían el campo _token y las peticiones fetch usan la etiqueta
 * <meta name="csrf-token">. Hallazgo de OWASP ZAP "Cookie No HttpOnly Flag" (ISO 25010 – Seguridad).
 */
class ValidarTokenCsrf extends ValidateCsrfToken
{
    protected $addHttpCookie = false;
}
