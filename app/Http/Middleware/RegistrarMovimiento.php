<?php

namespace App\Http\Middleware;

use App\Support\Auditoria;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HU-207: registra en auditoria_movimientos cada acción que modifica datos
 * (POST, PUT, PATCH, DELETE) hecha por una cuenta autenticada.
 */
class RegistrarMovimiento
{
    private const ACCIONES = [
        'POST' => 'Crear',
        'PUT' => 'Actualizar',
        'PATCH' => 'Actualizar',
        'DELETE' => 'Eliminar',
    ];

    // Rutas POST que no modifican datos y registran su propia acción.
    private const EXCLUIDAS = ['asistente.preguntar'];

    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        $accion = self::ACCIONES[$request->method()] ?? null;
        $exitosa = $respuesta->getStatusCode() < 400 && !session()->has('errors');

        if ($accion && $request->user() && $exitosa && !in_array($request->route()?->getName(), self::EXCLUIDAS, true)) {
            try {
                Auditoria::movimiento($accion, [
                    'ruta' => $request->route()?->getName(),
                    'url' => $request->path(),
                    'parametros' => $request->route()?->parameters() ?? [],
                    'campos' => array_values(array_diff(array_keys($request->except(['_token', '_method'])), ['password', 'password_confirmation', 'clave'])),
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $respuesta;
    }
}
