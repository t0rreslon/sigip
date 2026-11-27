<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Fotos de usuarios y espacios. Se guardan en el disco privado (storage/app/private/imagenes)
 * con un nombre aleatorio y se sirven por la ruta "imagenes.ver", que exige sesión del personal.
 */
class Imagenes
{
    public const CARPETAS = ['usuarios', 'espacios', 'personal'];

    // Reglas de validación del campo de foto (máx. 2 MB, solo imágenes comunes).
    public const REGLAS = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'];

    public static function guardar(UploadedFile $archivo, string $carpeta): string
    {
        $nombre = Str::uuid() . '.' . strtolower($archivo->extension());
        $archivo->storeAs("imagenes/{$carpeta}", $nombre, 'local');

        return $nombre;
    }

    public static function borrar(?string $nombre, string $carpeta): void
    {
        if ($nombre) {
            Storage::disk('local')->delete("imagenes/{$carpeta}/{$nombre}");
        }
    }

    public static function url(?string $nombre, string $carpeta): ?string
    {
        return $nombre ? route('imagenes.ver', [$carpeta, $nombre]) : null;
    }
}
