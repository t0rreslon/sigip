<?php

namespace App\Http\Controllers;

use App\Support\Asistente;
use App\Support\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Chat del asistente con IA (Ollama) en la página de inicio del personal.
 */
class AsistenteController extends Controller
{
    public function preguntar(Request $request)
    {
        $datos = $request->validate([
            'mensaje' => ['required', 'string', 'max:500'],
            'formato' => ['required', Rule::in(Asistente::FORMATOS)],
            'historial' => ['nullable', 'array', 'max:12'],
            'historial.*.rol' => ['required', Rule::in(['usuario', 'asistente'])],
            'historial.*.texto' => ['required', 'string', 'max:3000'],
        ]);

        $esAdmin = $request->user()->tieneTipo('Administrador');

        // Un modelo local puede tardar (sobre todo sin tarjeta de video): se amplía el tiempo de PHP.
        set_time_limit((int) config('services.ollama.tiempo') * 2 + 30);

        try {
            $respuesta = Asistente::responder(trim($datos['mensaje']), $datos['historial'] ?? [], $datos['formato'], $esAdmin);
        } catch (RuntimeException $e) {
            report($e);

            return response()->json(['error' => $e->getMessage()], 503);
        }

        // Auditoría sin el texto de la pregunta (podría contener datos personales).
        Auditoria::movimiento('Consultar asistente', [
            'formato' => $datos['formato'],
            'grafica' => $respuesta['grafica']['id'] ?? null,
            'caracteres' => mb_strlen($datos['mensaje']),
        ]);

        return response()->json($respuesta);
    }

    public function estado()
    {
        return response()->json(Asistente::estado());
    }
}
