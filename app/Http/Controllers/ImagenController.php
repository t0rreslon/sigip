<?php

namespace App\Http\Controllers;

use App\Support\Imagenes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Entrega las fotos guardadas en el disco privado.
 * - Usuarios y espacios: solo Administrador y Operador.
 * - Foto de perfil del personal: Administrador, Operador o la propia cuenta (por ejemplo, un instructor).
 */
class ImagenController extends Controller
{
    public function ver(string $carpeta, string $archivo)
    {
        abort_unless(in_array($carpeta, Imagenes::CARPETAS, true), 404);

        $cuenta = request()->user();
        $esPersonal = $cuenta->tieneTipo('Administrador', 'Operador');
        $esSuya = $carpeta === 'personal'
            && (int) DB::table('personal')->where('imagen', $archivo)->value('personal_id') === (int) $cuenta->personal_id;

        abort_unless($esPersonal || $esSuya, 403);

        $ruta = "imagenes/{$carpeta}/{$archivo}";
        abort_unless(Storage::disk('local')->exists($ruta), 404);

        return response()->file(Storage::disk('local')->path($ruta), [
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
