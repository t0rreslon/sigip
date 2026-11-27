<?php

namespace App\Http\Controllers;

use App\Models\Suscripcion;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Página de inicio del personal: bienvenida, indicadores y gráficas.
 * El Administrador ve la gestión general (usuarios, ingresos, planes);
 * el Operador ve la operación del día (accesos, pagos por verificar, préstamos, reservas).
 */
class InicioController extends Controller
{
    public function index(Request $request)
    {
        $cuenta = $request->user();
        $esAdmin = $cuenta->tieneTipo('Administrador');

        $datos = [
            'nombre' => DB::table('personal')->where('personal_id', $cuenta->personal_id)->value('nombre') ?? $cuenta->login,
            'rol' => $cuenta->nombreTipo(),
            'esAdmin' => $esAdmin,
            'hoy' => now(),
        ];

        return view('inicio', $datos + ($esAdmin ? $this->administrador() : $this->operador()));
    }

    private function administrador(): array
    {
        $inicioMes = now()->startOfMonth();

        $indicadores = [
            ['Usuarios activos', DB::table('usuario')->where('estado_usuario', true)->count(), 'de ' . DB::table('usuario')->count() . ' registrados'],
            ['Suscripciones activas', $this->suscripcionesActivas(), 'con plan vigente'],
            ['Ingresos del mes', '$' . number_format((float) DB::table('pago')->where('estado_pago', 'Verificado')->where('fecha_pago', '>=', $inicioMes)->sum('monto'), 0, ',', '.'), 'pagos verificados en ' . $this->mes($inicioMes)],
            ['Accesos hoy', DB::table('acceso')->whereDate('fecha_ingreso', today())->count(), 'ingresos registrados'],
        ];

        // Ingresos verificados por mes (últimos 6 meses, incluidos los meses en cero).
        $desde = now()->subMonths(5)->startOfMonth();
        $porMes = DB::table('pago')
            ->where('estado_pago', 'Verificado')
            ->where('fecha_pago', '>=', $desde)
            ->select(DB::raw("to_char(date_trunc('month', fecha_pago), 'YYYY-MM') AS mes"), DB::raw('SUM(monto) AS total'))
            ->groupBy('mes')
            ->pluck('total', 'mes');

        $ingresos = ['etiquetas' => [], 'valores' => []];
        for ($m = $desde->copy(); $m <= now(); $m->addMonth()) {
            $ingresos['etiquetas'][] = $this->mes($m);
            $ingresos['valores'][] = (float) ($porMes[$m->format('Y-m')] ?? 0);
        }

        $usuariosPorRol = DB::table('usuario as u')
            ->join('rol_usuario as r', 'r.rol_usuario_id', '=', 'u.rol_usuario_id')
            ->where('u.estado_usuario', true)
            ->select('r.nombre', DB::raw('COUNT(*) AS total'))
            ->groupBy('r.nombre')
            ->orderByDesc('total')
            ->get();

        $suscripcionesPorPlan = DB::table('suscripcion as s')
            ->join('plan as pl', 'pl.plan_id', '=', 's.plan_id')
            ->whereRaw('(' . Suscripcion::sqlEstadoCalculado() . ") = 'activa'")
            ->select('pl.nombre_plan', DB::raw('COUNT(*) AS total'))
            ->groupBy('pl.nombre_plan')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'indicadores' => $indicadores,
            'graficas' => [
                ['id' => 'accesos-14', 'titulo' => 'Accesos por día', 'subtitulo' => 'Últimos 14 días', 'tipo' => 'line', 'serie' => 'Accesos', 'datos' => $this->accesosPorDia(14)],
                ['id' => 'ingresos-mes', 'titulo' => 'Ingresos verificados por mes', 'subtitulo' => 'Últimos 6 meses, en pesos', 'tipo' => 'bar', 'serie' => 'Ingresos', 'moneda' => true, 'datos' => $ingresos],
                ['id' => 'usuarios-rol', 'titulo' => 'Usuarios activos por rol', 'subtitulo' => 'Comunidad que usa el gimnasio', 'tipo' => 'barh', 'serie' => 'Usuarios', 'datos' => $this->serie($usuariosPorRol, 'nombre')],
                ['id' => 'planes', 'titulo' => 'Suscripciones activas por plan', 'subtitulo' => 'Planes con más usuarios', 'tipo' => 'barh', 'serie' => 'Suscripciones', 'datos' => $this->serie($suscripcionesPorPlan, 'nombre_plan')],
            ],
            'listas' => [],
        ];
    }

    private function operador(): array
    {
        $indicadores = [
            ['Personas dentro ahora', DB::table('acceso')->whereNull('fecha_salida')->count(), 'ingresos sin salida'],
            ['Accesos hoy', DB::table('acceso')->whereDate('fecha_ingreso', today())->count(), 'ingresos registrados'],
            ['Pagos por verificar', DB::table('pago')->where('estado_pago', 'Pendiente')->count(), 'pendientes de revisión'],
            ['Préstamos activos', DB::table('prestamo')->where('activo', true)->count(), 'implementos sin devolver'],
        ];

        // Accesos de hoy por hora, dentro del horario de atención.
        $porHora = DB::table('acceso')
            ->whereDate('fecha_ingreso', today())
            ->select(DB::raw('EXTRACT(HOUR FROM fecha_ingreso)::int AS hora'), DB::raw('COUNT(*) AS total'))
            ->groupBy('hora')
            ->pluck('total', 'hora');

        // Se amplía el rango si hubo ingresos fuera del horario, para que ninguno quede por fuera.
        $horas = ['etiquetas' => [], 'valores' => []];
        $primera = min(6, $porHora->keys()->min() ?? 6);
        $ultima = max(20, $porHora->keys()->max() ?? 20);
        for ($h = $primera; $h <= $ultima; $h++) {
            $horas['etiquetas'][] = sprintf('%02d:00', $h);
            $horas['valores'][] = (int) ($porHora[$h] ?? 0);
        }

        $inventario = DB::table('categoria_implemento as c')
            ->leftJoin('implemento as i', 'i.categoria_implemento_id', '=', 'c.categoria_implemento_id')
            ->leftJoin('estado_implemento as e', 'e.estado_implemento_id', '=', 'i.estado_implemento_id')
            ->select('c.nombre_categoria',
                DB::raw("COUNT(i.implemento_id) FILTER (WHERE e.nombre_estado = 'Disponible') AS disponibles"),
                DB::raw("COUNT(i.implemento_id) FILTER (WHERE e.nombre_estado = 'Ocupado') AS prestados"))
            ->groupBy('c.nombre_categoria')
            ->orderBy('c.nombre_categoria')
            ->get();

        $reservasHoy = DB::table('reserva as r')
            ->join('espacio as e', 'e.espacio_id', '=', 'r.espacio_id')
            ->join('estado_reserva as er', 'er.estado_reserva_id', '=', 'r.estado_reserva_id')
            ->whereDate('r.fecha_inicio', today())
            ->whereNotIn('er.nombre_estado', ['Cancelada', 'Rechazada'])
            ->orderBy('r.fecha_inicio')
            ->get(['e.nombre_espacio', 'r.fecha_inicio', 'r.fecha_fin', 'er.nombre_estado']);

        $actividadesHoy = DB::table('programacion_actividad as pa')
            ->join('actividad as a', 'a.actividad_id', '=', 'pa.actividad_id')
            ->join('espacio as e', 'e.espacio_id', '=', 'pa.espacio_id')
            ->join('estado_programacion_actividad as ep', 'ep.estado_programacion_actividad_id', '=', 'pa.estado_programacion_actividad_id')
            ->whereDate('pa.fecha_inicio', today())
            ->whereIn('ep.nombre_estado', ['Pendiente', 'En curso'])
            ->orderBy('pa.fecha_inicio')
            ->get(['a.nombre_actividad', 'e.nombre_espacio', 'pa.fecha_inicio', 'pa.fecha_fin']);

        return [
            'indicadores' => $indicadores,
            'graficas' => [
                ['id' => 'accesos-hora', 'titulo' => 'Accesos de hoy por hora', 'subtitulo' => 'Ingresos registrados hoy, hora por hora', 'tipo' => 'bar', 'serie' => 'Accesos', 'datos' => $horas],
                ['id' => 'accesos-7', 'titulo' => 'Accesos por día', 'subtitulo' => 'Últimos 7 días', 'tipo' => 'line', 'serie' => 'Accesos', 'datos' => $this->accesosPorDia(7)],
                ['id' => 'implementos', 'titulo' => 'Implementos por categoría', 'subtitulo' => 'Disponibles y prestados', 'tipo' => 'barh-apilada', 'datos' => [
                    'etiquetas' => $inventario->pluck('nombre_categoria'),
                    'series' => [
                        ['nombre' => 'Disponibles', 'valores' => $inventario->pluck('disponibles')->map(fn ($v) => (int) $v)],
                        ['nombre' => 'Prestados', 'valores' => $inventario->pluck('prestados')->map(fn ($v) => (int) $v)],
                    ],
                ]],
            ],
            'listas' => [
                ['titulo' => 'Reservas de hoy', 'vacio' => 'No hay reservas para hoy.', 'filas' => $reservasHoy->map(fn ($r) => [
                    $this->rango($r->fecha_inicio, $r->fecha_fin), $r->nombre_espacio, $r->nombre_estado])],
                ['titulo' => 'Actividades de hoy', 'vacio' => 'No hay actividades programadas para hoy.', 'filas' => $actividadesHoy->map(fn ($a) => [
                    $this->rango($a->fecha_inicio, $a->fecha_fin), $a->nombre_actividad, $a->nombre_espacio])],
            ],
        ];
    }

    private function accesosPorDia(int $dias): array
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

    private function suscripcionesActivas(): int
    {
        return DB::table('suscripcion as s')
            ->join('plan as pl', 'pl.plan_id', '=', 's.plan_id')
            ->whereRaw('(' . Suscripcion::sqlEstadoCalculado() . ") = 'activa'")
            ->count();
    }

    private function serie($filas, string $campo): array
    {
        return ['etiquetas' => $filas->pluck($campo), 'valores' => $filas->pluck('total')->map(fn ($v) => (int) $v)];
    }

    private function mes(Carbon $fecha): string
    {
        return ucfirst($fecha->locale('es')->translatedFormat('M Y'));
    }

    private function rango($inicio, $fin): string
    {
        return Carbon::parse($inicio)->format('h:i a') . ' – ' . Carbon::parse($fin)->format('h:i a');
    }
}
