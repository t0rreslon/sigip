<?php

namespace App\Support;

/**
 * Utilidades para búsquedas seguras (ISO 25010 – Seguridad / Integridad, HU-63).
 */
class Busqueda
{
    /**
     * Patrón para LIKE/ILIKE que busca el texto literal: escapa los comodines
     * % y _ (y la barra invertida) que escriba el usuario. Sin esto, buscar "%"
     * devolvería todos los registros. El valor siempre va como parámetro enlazado.
     */
    public static function contiene(?string $texto): string
    {
        return '%' . addcslashes(trim((string) $texto), '\\%_') . '%';
    }

    public static function empiezaPor(?string $texto): string
    {
        return addcslashes(trim((string) $texto), '\\%_') . '%';
    }

    /**
     * URL de destino de un formulario reabierto tras un error de validación.
     * Solo se acepta si apunta a este mismo sitio; si no, se usa la URL por defecto.
     */
    public static function accionSegura(?string $accion, ?string $porDefecto = null): string
    {
        $base = rtrim(url('/'), '/');

        if ($accion !== null && ($accion === $base || str_starts_with($accion, $base . '/'))) {
            return $accion;
        }

        return $porDefecto ?? $base;
    }
}
