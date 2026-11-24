<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

/**
 * HU-199 / HU-204: avisos al personal cuando un instructor solicita
 * una reserva o una actividad.
 */
class NotificacionController extends Controller
{
    public function index()
    {
        return view('notificaciones', [
            'reservas' => ReservaController::consultaReservas()
                ->where('er.nombre_estado', 'Pendiente')
                ->where('r.fecha_fin', '>', now())
                ->orderBy('r.fecha_creacion', 'desc')
                ->get(),
            'actividades' => ActividadController::consultaProgramaciones()
                ->where('ep.nombre_estado', 'Solicitada')
                ->where('pa.fecha_fin', '>', now())
                ->orderBy('pa.fecha_inicio')
                ->get(),
        ]);
    }

    /**
     * Cantidad de solicitudes pendientes (para el contador de la barra superior).
     */
    public static function pendientes(): int
    {
        $reservas = DB::table('reserva as r')
            ->join('estado_reserva as er', 'er.estado_reserva_id', '=', 'r.estado_reserva_id')
            ->where('er.nombre_estado', 'Pendiente')
            ->where('r.fecha_fin', '>', now())
            ->count();

        $actividades = DB::table('programacion_actividad as pa')
            ->join('estado_programacion_actividad as ep', 'ep.estado_programacion_actividad_id', '=', 'pa.estado_programacion_actividad_id')
            ->where('ep.nombre_estado', 'Solicitada')
            ->where('pa.fecha_fin', '>', now())
            ->count();

        return $reservas + $actividades;
    }
}
