<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Registro de cambios (auditoria_tablas) y de acciones (auditoria_movimientos).
 * Alimenta los historiales de usuarios, instructores, planes, inventario
 * (HU-160, 193, 194, 197) y el historial de acciones de los operadores (HU-207).
 */
class Auditoria
{
    // Campos que nunca se guardan en el historial.
    private const CAMPOS_OCULTOS = ['clave', 'password', 'password_confirmation', '_token', '_method', 'token'];

    /**
     * Guarda un cambio sobre un registro. Solo se guardan los campos que cambiaron.
     */
    public static function cambio(string $tabla, string $operacion, int|string $registroId, $antes = null, $despues = null): void
    {
        $antes = self::limpiar($antes);
        $despues = self::limpiar($despues);

        if ($operacion === 'UPDATE' && $antes !== null && $despues !== null) {
            $modificados = [];

            foreach ($despues as $campo => $valor) {
                if (array_key_exists($campo, $antes) && (string) $antes[$campo] !== (string) $valor) {
                    $modificados[$campo] = $valor;
                }
            }

            if ($modificados === []) {
                return;
            }

            $antes = array_intersect_key($antes, $modificados);
            $despues = $modificados;
        }

        DB::table('auditoria_tablas')->insert([
            'nombre_tabla' => $tabla,
            'operacion' => $operacion,
            'registro_id' => (string) $registroId,
            'datos_anteriores' => $antes === null ? null : json_encode($antes, JSON_UNESCAPED_UNICODE),
            'datos_nuevos' => $despues === null ? null : json_encode($despues, JSON_UNESCAPED_UNICODE),
            'usuario' => auth()->user()?->login ?? 'sistema',
            'creado_en' => now(),
        ]);
    }

    /**
     * Historial de un registro, del más reciente al más antiguo.
     */
    public static function historial(string|array $tablas, int|string $registroId)
    {
        return DB::table('auditoria_tablas')
            ->whereIn('nombre_tabla', (array) $tablas)
            ->where('registro_id', (string) $registroId)
            ->orderByDesc('creado_en')
            ->orderByDesc('id')
            ->get()
            ->map(function ($fila) {
                $fila->anteriores = json_decode($fila->datos_anteriores ?? 'null', true) ?? [];
                $fila->nuevos = json_decode($fila->datos_nuevos ?? 'null', true) ?? [];

                return $fila;
            });
    }

    /**
     * Guarda una acción hecha por la cuenta autenticada.
     */
    public static function movimiento(string $accion, array $datos = [], ?int $cuentaId = null): void
    {
        $tipoAccionId = DB::table('tipo_accion')->where('nombre_accion', $accion)->value('tipo_accion_id');

        if (!$tipoAccionId) {
            return;
        }

        DB::table('auditoria_movimientos')->insert([
            'ip' => request()->ip(),
            'cuenta_id' => $cuentaId ?? auth()->id(),
            'tipo_accion_id' => $tipoAccionId,
            'datos' => json_encode(self::limpiar($datos) ?? [], JSON_UNESCAPED_UNICODE),
            'fecha_hora' => now(),
        ]);
    }

    private static function limpiar($datos): ?array
    {
        if ($datos === null) {
            return null;
        }

        $datos = is_object($datos) ? (array) $datos : $datos;

        return array_diff_key($datos, array_flip(self::CAMPOS_OCULTOS));
    }
}
