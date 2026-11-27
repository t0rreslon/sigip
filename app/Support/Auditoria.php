<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Registro de cambios (auditoria_tablas) y de acciones (auditoria_movimientos).
 * Alimenta los historiales de usuarios, instructores, planes, inventario
 * (HU-160, 193, 194, 197), préstamos, pagos y suscripciones, el historial de acciones
 * de los operadores (HU-207) y la página de auditoría del administrador.
 */
class Auditoria
{
    // Campos que nunca se guardan en el historial.
    private const CAMPOS_OCULTOS = ['clave', 'password', 'password_confirmation', '_token', '_method', 'token'];

    // Llaves foráneas que se guardan con su nombre legible: campo => [tabla, llave, columna, nuevo campo].
    private const REFERENCIAS = [
        'estado_espacio_id' => ['estado_espacio', 'estado_espacio_id', 'nombre_estado', 'estado'],
        'estado_maquina_id' => ['estado_maquina', 'estado_maquina_id', 'nombre_estado', 'estado'],
        'estado_implemento_id' => ['estado_implemento', 'estado_implemento_id', 'nombre_estado', 'estado'],
        'estado_personal_id' => ['estado_personal', 'estado_personal_id', 'nombre_estado', 'estado'],
        'rol_usuario_id' => ['rol_usuario', 'rol_usuario_id', 'nombre', 'rol'],
        'categoria_implemento_id' => ['categoria_implemento', 'categoria_implemento_id', 'nombre_categoria', 'categoria'],
        'plan_id' => ['plan', 'plan_id', 'nombre_plan', 'plan'],
        'espacio_id' => ['espacio', 'espacio_id', 'nombre_espacio', 'espacio'],
        'actividad_id' => ['actividad', 'actividad_id', 'nombre_actividad', 'actividad'],
        'metodo_pago_id' => ['metodo_pago', 'metodo_pago_id', 'nombre_metodo', 'metodo_de_pago'],
    ];

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

        // Un cambio de estado que deja el mismo estado no es un cambio.
        if ($operacion === 'ESTADO' && $antes !== null && $antes == $despues) {
            return;
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

    /**
     * Nombre legible del módulo al que pertenece cada tabla auditada.
     */
    public const MODULOS = [
        'usuario' => 'Usuarios',
        'plan' => 'Planes',
        'suscripcion' => 'Suscripciones',
        'pago' => 'Pagos',
        'prestamo' => 'Préstamos',
        'implemento' => 'Inventario · implementos',
        'maquina' => 'Inventario · máquinas',
        'categoria_implemento' => 'Inventario · categorías de implementos',
        'categoria_maquina' => 'Inventario · categorías de máquinas',
        'espacio' => 'Espacios',
        'reserva' => 'Reservas',
        'actividad' => 'Actividades · catálogo',
        'programacion_actividad' => 'Actividades · programación',
        'personal' => 'Personal e instructores',
        'cuenta' => 'Cuentas de acceso',
    ];

    public const OPERACIONES = [
        'INSERT' => 'Registro',
        'UPDATE' => 'Modificación',
        'ESTADO' => 'Cambio de estado',
        'DEVOLUCION' => 'Devolución',
        'DELETE' => 'Eliminación',
    ];

    /**
     * Enlace al detalle o historial del registro auditado, si existe una página para él.
     */
    public static function enlace(string $tabla, int|string $registroId): ?string
    {
        return match ($tabla) {
            'usuario' => route('usuarios.historial', $registroId),
            'plan' => route('planes.historial', $registroId),
            'prestamo' => route('prestamos.show', $registroId),
            'espacio' => route('espacios.show', $registroId),
            'maquina' => route('inventario.show', ['maquina', $registroId]),
            'implemento' => route('inventario.show', ['implemento', $registroId]),
            'programacion_actividad' => route('programaciones.show', $registroId),
            'pago', 'suscripcion', 'reserva' => route('historial.registro', [$tabla, $registroId]),
            default => null,
        };
    }

    /**
     * ISO 25010 – No repudio: comprueba la cadena de hashes de las dos tablas de auditoría.
     * Cada fila guarda el hash de la anterior; si alguien cambia o borra una fila
     * directamente en la base, la cadena se rompe en ese punto.
     *
     * @return array<string, array{total:int, alterados:int, primer_id:?int}>
     */
    public static function verificarIntegridad(): array
    {
        $tablas = [
            'auditoria_tablas' => ['id', "concat_ws('|', r.id, r.nombre_tabla, r.operacion, r.registro_id, r.datos_anteriores::text, r.datos_nuevos::text, r.usuario, r.creado_en::text)"],
            'auditoria_movimientos' => ['auditoria_id', "concat_ws('|', r.auditoria_id, host(r.ip), r.cuenta_id, r.tipo_accion_id, r.datos::text, extract(epoch from r.fecha_hora)::text)"],
        ];

        $resultado = [];

        foreach ($tablas as $tabla => [$id, $contenido]) {
            $filas = DB::select(<<<SQL
                SELECT {$id} AS id,
                       (r.hash_anterior IS DISTINCT FROM lag(r.hash) OVER (ORDER BY {$id})) AS enlace_roto,
                       (r.hash IS DISTINCT FROM encode(digest(coalesce(r.hash_anterior, '') || {$contenido}, 'sha256'), 'hex')) AS contenido_alterado
                  FROM {$tabla} r
                 ORDER BY {$id}
            SQL);

            $malos = array_values(array_filter($filas, fn ($fila) => $fila->enlace_roto || $fila->contenido_alterado));

            $resultado[$tabla] = [
                'total' => count($filas),
                'alterados' => count($malos),
                'primer_id' => $malos[0]->id ?? null,
            ];
        }

        return $resultado;
    }

    private static function limpiar($datos): ?array
    {
        if ($datos === null) {
            return null;
        }

        $datos = is_object($datos) ? (array) $datos : $datos;
        $datos = array_diff_key($datos, array_flip(self::CAMPOS_OCULTOS));

        // Se reemplazan los identificadores de catálogos por su nombre ("estado: Mantenimiento" en vez de "estado_espacio_id: 3").
        $legibles = [];

        foreach ($datos as $campo => $valor) {
            if (isset(self::REFERENCIAS[$campo]) && is_scalar($valor) && $valor !== '') {
                [$tabla, $llave, $columna, $nuevo] = self::REFERENCIAS[$campo];
                $legibles[$nuevo] = DB::table($tabla)->where($llave, $valor)->value($columna) ?? $valor;
            } else {
                $legibles[$campo] = $valor;
            }
        }

        return $legibles;
    }
}
