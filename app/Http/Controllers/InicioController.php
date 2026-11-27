<?php

namespace App\Http\Controllers;

use App\Models\Suscripcion;
use App\Support\Indicadores;
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

        return [
            'indicadores' => $indicadores,
            'graficas' => [
                ['id' => 'accesos-14', 'titulo' => 'Accesos por día', 'subtitulo' => 'Últimos 14 días', 'tipo' => 'line', 'serie' => 'Accesos', 'datos' => Indicadores::accesosPorDia(14)],
                ['id' => 'ingresos-mes', 'titulo' => 'Ingresos verificados por mes', 'subtitulo' => 'Últimos 6 meses, en pesos', 'tipo' => 'bar', 'serie' => 'Ingresos', 'moneda' => true, 'datos' => Indicadores::ingresosPorMes(6)],
                ['id' => 'planes-asignados', 'titulo' => 'Planes y suscripciones más asignados', 'subtitulo' => 'Suscripciones asignadas por plan: activas y vencidas o por activar', 'tipo' => 'barh-apilada', 'ancho' => true, 'datos' => Indicadores::planesMasAsignados(8)],
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
                ['id' => 'accesos-hora', 'titulo' => 'Accesos de hoy por hora', 'subtitulo' => 'Ingresos registrados hoy, hora por hora', 'tipo' => 'bar', 'serie' => 'Accesos', 'datos' => Indicadores::accesosHoyPorHora()],
                ['id' => 'accesos-7', 'titulo' => 'Accesos por día', 'subtitulo' => 'Últimos 7 días', 'tipo' => 'line', 'serie' => 'Accesos', 'datos' => Indicadores::accesosPorDia(7)],
                ['id' => 'implementos', 'titulo' => 'Implementos por categoría', 'subtitulo' => 'Disponibles y prestados', 'tipo' => 'barh-apilada', 'datos' => Indicadores::implementosPorCategoria()],
            ],
            'listas' => [
                ['titulo' => 'Reservas de hoy', 'vacio' => 'No hay reservas para hoy.', 'filas' => $reservasHoy->map(fn ($r) => [
                    $this->rango($r->fecha_inicio, $r->fecha_fin), $r->nombre_espacio, $r->nombre_estado])],
                ['titulo' => 'Actividades de hoy', 'vacio' => 'No hay actividades programadas para hoy.', 'filas' => $actividadesHoy->map(fn ($a) => [
                    $this->rango($a->fecha_inicio, $a->fecha_fin), $a->nombre_actividad, $a->nombre_espacio])],
            ],
        ];
    }

    private function suscripcionesActivas(): int
    {
        return DB::table('suscripcion as s')
            ->join('plan as pl', 'pl.plan_id', '=', 's.plan_id')
            ->whereRaw('(' . Suscripcion::sqlEstadoCalculado() . ") = 'activa'")
            ->count();
    }

    private function mes(Carbon $fecha): string
    {
        return Indicadores::mes($fecha);
    }

    private function rango($inicio, $fin): string
    {
        return Carbon::parse($inicio)->format('h:i a') . ' – ' . Carbon::parse($fin)->format('h:i a');
    }
}
