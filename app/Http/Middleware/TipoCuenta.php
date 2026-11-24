<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe una ruta a los tipos de cuenta indicados.
 * Uso: ->middleware('tipo_cuenta:Administrador,Operador')
 */
class TipoCuenta
{
    public function handle(Request $request, Closure $next, string ...$tipos): Response
    {
        $cuenta = $request->user();

        if (!$cuenta || !$cuenta->estado_cuenta || !$cuenta->tieneTipo(...$tipos)) {
            abort(403, 'Tu cuenta no tiene permiso para realizar esta acción.');
        }

        return $next($request);
    }
}
