<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Quita los bytes nulos (\0) y los caracteres de control de los datos recibidos
 * (ISO 25010 – Seguridad / Resistencia, HU-63). Es una defensa en profundidad:
 * PostgreSQL no admite el byte nulo en textos y estos caracteres se usan para
 * intentar saltarse validaciones o ensuciar los registros y reportes.
 */
class LimpiarEntrada
{
    public function handle(Request $request, Closure $next): Response
    {
        $limpiar = function ($valor) use (&$limpiar) {
            if (is_array($valor)) {
                return array_map($limpiar, $valor);
            }

            // Se conservan tabulación, salto de línea y retorno de carro.
            return is_string($valor) ? preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $valor) ?? '' : $valor;
        };

        $request->query->replace($limpiar($request->query->all()));
        $request->request->replace($limpiar($request->request->all()));

        if ($request->isJson()) {
            $request->json()->replace($limpiar($request->json()->all()));
        }

        return $next($request);
    }
}
