<?php

namespace App\Http\Controllers;

use App\Support\Imagenes;
use Illuminate\Support\Facades\Storage;

/**
 * Entrega las fotos guardadas en el disco privado solo al personal con sesión.
 */
class ImagenController extends Controller
{
    public function ver(string $carpeta, string $archivo)
    {
        abort_unless(in_array($carpeta, Imagenes::CARPETAS, true), 404);

        $ruta = "imagenes/{$carpeta}/{$archivo}";
        abort_unless(Storage::disk('local')->exists($ruta), 404);

        return response()->file(Storage::disk('local')->path($ruta), [
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
