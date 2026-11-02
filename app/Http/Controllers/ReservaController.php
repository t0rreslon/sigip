<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use Illuminate\Validation\ValidationException;

class ReservaController extends Controller
{
    private function validarDisponibilidad(
        int $espacioId,
        string $fechaInicio,
        string $fechaFin,
        array $estadosQueBloquean,
        ?int $reservaId = null
    ): void {
        $consulta = Reserva::query()
            ->where('espacio_id', $espacioId)
            ->whereIn('estado_reserva_id', $estadosQueBloquean)
            ->where('fecha_inicio', '<', $fechaFin)
            ->where('fecha_fin', '>', $fechaInicio);

        if ($reservaId !== null) {
            $consulta->where('reserva_id', '<>', $reservaId);
        }

        if ($consulta->exists()) {
            throw ValidationException::withMessages([
                'fecha_inicio' =>
                'El espacio ya tiene una reserva en ese horario.',
            ]);
        }
    }
}
