<?php

namespace App\Support;

use App\Models\Suscripcion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Datos agregados del gimnasio (sin datos personales). Los usan las gráficas del
 * inicio y el asistente con IA, para que ambos muestren exactamente las mismas cifras.
 */
class Indicadores
{
    /** Accesos (ingresos) por día en los últimos $dias días, incluidos los días en cero. */
    public static function accesosPorDia(int $dias): array
    {
        $desde = today()->subDays($dias - 1);
        $porDia = DB::table('acceso')
            ->where('fecha_ingreso', '>=', $desde)
            ->select(DB::raw("to_char(fecha_ingreso, 'YYYY-MM-DD') AS dia"), DB::raw('COUNT(*) AS total'))
            ->groupBy('dia')
            ->pluck('total', 'dia');

        $resultado = ['etiquetas' => [], 'valores' => []];
        for ($d = $desde->copy(); $d <= today(); $d->addDay()) {
            $resultado['etiquetas'][] = $d->format('d/m');
            $resultado['valores'][] = (int) ($porDia[$d->format('Y-m-d')] ?? 0);
        }

        return $resultado;
    }

    /** Accesos de hoy por hora; amplía el rango si hubo ingresos fuera de 6:00–20:00. */
    public static function accesosHoyPorHora(): array
    {
        $porHora = DB::table('acceso')
            ->whereDate('fecha_ingreso', today())
            ->select(DB::raw('EXTRACT(HOUR FROM fecha_ingreso)::int AS hora'), DB::raw('COUNT(*) AS total'))
            ->groupBy('hora')
            ->pluck('total', 'hora');

        $horas = ['etiquetas' => [], 'valores' => []];
        $primera = min(6, $porHora->keys()->min() ?? 6);
        $ultima = max(20, $porHora->keys()->max() ?? 20);
        for ($h = $primera; $h <= $ultima; $h++) {
            $horas['etiquetas'][] = sprintf('%02d:00', $h);
            $horas['valores'][] = (int) ($porHora[$h] ?? 0);
        }

        return $horas;
    }

    /** Ingresos verificados por mes (últimos $meses meses, incluidos los meses en cero). */
    public static function ingresosPorMes(int $meses = 6): array
    {
        $desde = now()->subMonths($meses - 1)->startOfMonth();
        $porMes = DB::table('pago')
            ->where('estado_pago', 'Verificado')
            ->where('fecha_pago', '>=', $desde)
            ->select(DB::raw("to_char(date_trunc('month', fecha_pago), 'YYYY-MM') AS mes"), DB::raw('SUM(monto) AS total'))
            ->groupBy('mes')
            ->pluck('total', 'mes');

        $ingresos = ['etiquetas' => [], 'valores' => []];
        for ($m = $desde->copy(); $m <= now(); $m->addMonth()) {
            $ingresos['etiquetas'][] = self::mes($m);
            $ingresos['valores'][] = (float) ($porMes[$m->format('Y-m')] ?? 0);
        }

        return $ingresos;
    }

    /**
     * Planes más asignados: cuántas suscripciones se han asignado de cada plan,
     * separando las que siguen activas de las demás (vencidas o por activar).
     */
    public static function planesMasAsignados(int $limite = 8): array
    {
        $filas = DB::table('suscripcion as s')
            ->join('plan as pl', 'pl.plan_id', '=', 's.plan_id')
            ->select(
                'pl.nombre_plan',
                DB::raw('COUNT(*) AS total'),
                DB::raw('COUNT(*) FILTER (WHERE (' . Suscripcion::sqlEstadoCalculado() . ") = 'activa') AS activas")
            )
            ->groupBy('pl.nombre_plan')
            ->orderByDesc('total')
            ->orderBy('pl.nombre_plan')
            ->limit($limite)
            ->get();

        return [
            'etiquetas' => $filas->pluck('nombre_plan')->all(),
            'series' => [
                ['nombre' => 'Activas', 'valores' => $filas->map(fn ($f) => (int) $f->activas)->all()],
                ['nombre' => 'Vencidas o por activar', 'valores' => $filas->map(fn ($f) => (int) $f->total - (int) $f->activas)->all()],
            ],
        ];
    }

    /** Suscripciones por estado calculado (activa, por activar, vencida). */
    public static function suscripcionesPorEstado(): array
    {
        $porEstado = DB::table('suscripcion as s')
            ->join('plan as pl', 'pl.plan_id', '=', 's.plan_id')
            // Alias distinto de la columna suscripcion.estado: en GROUP BY, PostgreSQL prefiere la columna.
            ->select(DB::raw('(' . Suscripcion::sqlEstadoCalculado() . ') AS estado_calculado'), DB::raw('COUNT(*) AS total'))
            ->groupBy('estado_calculado')
            ->pluck('total', 'estado_calculado');

        $nombres = ['activa' => 'Activas', 'por_activar' => 'Por activar', 'vencida' => 'Vencidas'];
        $resultado = ['etiquetas' => [], 'valores' => []];

        foreach ($nombres as $clave => $nombre) {
            $resultado['etiquetas'][] = $nombre;
            $resultado['valores'][] = (int) ($porEstado[$clave] ?? 0);
        }

        return $resultado;
    }

    /** Implementos por categoría: disponibles y prestados. */
    public static function implementosPorCategoria(): array
    {
        $inventario = DB::table('categoria_implemento as c')
            ->leftJoin('implemento as i', 'i.categoria_implemento_id', '=', 'c.categoria_implemento_id')
            ->leftJoin('estado_implemento as e', 'e.estado_implemento_id', '=', 'i.estado_implemento_id')
            ->select('c.nombre_categoria',
                DB::raw("COUNT(i.implemento_id) FILTER (WHERE e.nombre_estado = 'Disponible') AS disponibles"),
                DB::raw("COUNT(i.implemento_id) FILTER (WHERE e.nombre_estado = 'Ocupado') AS prestados"))
            ->groupBy('c.nombre_categoria')
            ->orderBy('c.nombre_categoria')
            ->get();

        return [
            'etiquetas' => $inventario->pluck('nombre_categoria')->all(),
            'series' => [
                ['nombre' => 'Disponibles', 'valores' => $inventario->pluck('disponibles')->map(fn ($v) => (int) $v)->all()],
                ['nombre' => 'Prestados', 'valores' => $inventario->pluck('prestados')->map(fn ($v) => (int) $v)->all()],
            ],
        ];
    }

    /** Usuarios activos por rol. */
    public static function usuariosPorRol(): array
    {
        $filas = DB::table('usuario as u')
            ->join('rol_usuario as r', 'r.rol_usuario_id', '=', 'u.rol_usuario_id')
            ->where('u.estado_usuario', true)
            ->select('r.nombre', DB::raw('COUNT(*) AS total'))
            ->groupBy('r.nombre')
            ->orderByDesc('total')
            ->get();

        return ['etiquetas' => $filas->pluck('nombre')->all(), 'valores' => $filas->pluck('total')->map(fn ($v) => (int) $v)->all()];
    }

    /** Cifras sueltas del día. $conIngresos solo para el administrador. */
    public static function resumen(bool $conIngresos): array
    {
        $resumen = [
            'usuarios_activos' => DB::table('usuario')->where('estado_usuario', true)->count(),
            'usuarios_registrados' => DB::table('usuario')->count(),
            'personas_dentro_ahora' => DB::table('acceso')->whereNull('fecha_salida')->count(),
            'accesos_hoy' => DB::table('acceso')->whereDate('fecha_ingreso', today())->count(),
            'pagos_por_verificar' => DB::table('pago')->where('estado_pago', 'Pendiente')->count(),
            'prestamos_activos' => DB::table('prestamo')->where('activo', true)->count(),
            'reservas_hoy' => DB::table('reserva as r')
                ->join('estado_reserva as er', 'er.estado_reserva_id', '=', 'r.estado_reserva_id')
                ->whereDate('r.fecha_inicio', today())
                ->whereNotIn('er.nombre_estado', ['Cancelada', 'Rechazada'])
                ->count(),
            'actividades_hoy' => DB::table('programacion_actividad as pa')
                ->join('estado_programacion_actividad as ep', 'ep.estado_programacion_actividad_id', '=', 'pa.estado_programacion_actividad_id')
                ->whereDate('pa.fecha_inicio', today())
                ->whereIn('ep.nombre_estado', ['Pendiente', 'En curso'])
                ->count(),
        ];

        if ($conIngresos) {
            $resumen['ingresos_verificados_mes_actual'] = (float) DB::table('pago')
                ->where('estado_pago', 'Verificado')
                ->where('fecha_pago', '>=', now()->startOfMonth())
                ->sum('monto');
        }

        return $resumen;
    }

    public static function mes(Carbon $fecha): string
    {
        return ucfirst($fecha->locale('es')->translatedFormat('M Y'));
    }
}
