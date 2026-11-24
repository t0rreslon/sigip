<?php

namespace App\Support;

use App\Models\Espacio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Verifica si un espacio está libre en un horario, considerando tanto las
 * reservas como las actividades programadas (HU-48, HU-52, HU-169).
 */
class Disponibilidad
{
    // Estados de actividad programada que ocupan el espacio.
    public const ESTADOS_PROGRAMACION_ACTIVOS = ['Solicitada', 'Pendiente', 'En curso'];

    public const HORA_APERTURA = '06:00';
    public const HORA_CIERRE = '21:00';

    /**
     * Devuelve un mensaje con el motivo del conflicto, o null si el espacio está libre.
     */
    public static function conflicto(
        int $espacioId,
        Carbon $inicio,
        Carbon $fin,
        ?int $ignorarReserva = null,
        ?int $ignorarProgramacion = null
    ): ?string {
        return self::revisar($espacioId, $inicio, $fin, $ignorarReserva, $ignorarProgramacion)[1] ?? null;
    }

    /**
     * Como conflicto(), pero indica también el campo del formulario afectado:
     * ['espacio_id', mensaje] si el espacio está deshabilitado, ['hora_inicio', mensaje] si hay cruce.
     */
    public static function revisar(
        int $espacioId,
        Carbon $inicio,
        Carbon $fin,
        ?int $ignorarReserva = null,
        ?int $ignorarProgramacion = null
    ): ?array {
        $espacio = DB::table('espacio as e')
            ->join('estado_espacio as ee', 'ee.estado_espacio_id', '=', 'e.estado_espacio_id')
            ->where('e.espacio_id', $espacioId)
            ->select('e.nombre_espacio', 'ee.nombre_estado')
            ->first();

        if (!$espacio) {
            return ['espacio_id', 'El espacio no existe.'];
        }

        if (in_array($espacio->nombre_estado, Espacio::ESTADOS_DESHABILITADOS, true)) {
            return ['espacio_id', "{$espacio->nombre_espacio} está en " . mb_strtolower($espacio->nombre_estado) . ' y no se puede usar.'];
        }

        $reserva = DB::table('reserva as r')
            ->join('estado_reserva as er', 'er.estado_reserva_id', '=', 'r.estado_reserva_id')
            ->where('r.espacio_id', $espacioId)
            ->whereIn('er.nombre_estado', Espacio::ESTADOS_RESERVA_ACTIVOS)
            ->where('r.fecha_inicio', '<', $fin)
            ->where('r.fecha_fin', '>', $inicio)
            ->when($ignorarReserva, fn ($q) => $q->where('r.reserva_id', '!=', $ignorarReserva))
            ->orderBy('r.fecha_inicio')
            ->first(['r.fecha_inicio', 'r.fecha_fin']);

        if ($reserva) {
            return ['hora_inicio', "{$espacio->nombre_espacio} ya está reservado de " . self::rango($reserva->fecha_inicio, $reserva->fecha_fin) . '. Elige otro horario.'];
        }

        $actividad = DB::table('programacion_actividad as pa')
            ->join('estado_programacion_actividad as ep', 'ep.estado_programacion_actividad_id', '=', 'pa.estado_programacion_actividad_id')
            ->join('actividad as a', 'a.actividad_id', '=', 'pa.actividad_id')
            ->where('pa.espacio_id', $espacioId)
            ->whereIn('ep.nombre_estado', self::ESTADOS_PROGRAMACION_ACTIVOS)
            ->where('pa.fecha_inicio', '<', $fin)
            ->where('pa.fecha_fin', '>', $inicio)
            ->when($ignorarProgramacion, fn ($q) => $q->where('pa.programacion_actividad_id', '!=', $ignorarProgramacion))
            ->orderBy('pa.fecha_inicio')
            ->first(['a.nombre_actividad', 'pa.fecha_inicio', 'pa.fecha_fin']);

        if ($actividad) {
            return ['hora_inicio', "En {$espacio->nombre_espacio} está programada \"{$actividad->nombre_actividad}\" de " . self::rango($actividad->fecha_inicio, $actividad->fecha_fin) . '. Elige otro horario.'];
        }

        return null;
    }

    /**
     * Valida el horario de atención y que el inicio sea futuro. Devuelve el error o null.
     */
    public static function validarHorario(Carbon $inicio, Carbon $fin): ?string
    {
        if ($fin->lte($inicio)) {
            return 'La hora final debe ser posterior a la hora inicial.';
        }

        if ($inicio->lte(now())) {
            return 'El horario debe comenzar después de la hora actual.';
        }

        if (!$inicio->isSameDay($fin)) {
            return 'El horario debe estar dentro del mismo día.';
        }

        if ($inicio->format('H:i') < self::HORA_APERTURA || $fin->format('H:i') > self::HORA_CIERRE) {
            return 'El horario debe estar entre las ' . self::HORA_APERTURA . ' y las ' . self::HORA_CIERRE . '.';
        }

        return null;
    }

    private static function rango($inicio, $fin): string
    {
        return Carbon::parse($inicio)->format('h:i a') . ' a ' . Carbon::parse($fin)->format('h:i a');
    }
}
