<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Espacio extends Model
{
    protected $table = 'espacio';

    protected $primaryKey = 'espacio_id';

    public $timestamps = false;

    protected $fillable = [
        'nombre_espacio',
        'descripcion',
        'capacidad',
        'estado_espacio_id',
        'imagen',
    ];

    // Estados que impiden usar o reservar el espacio.
    public const ESTADOS_DESHABILITADOS = ['Mantenimiento', 'Fuera de Servicio'];

    // Estados de reserva que ocupan el espacio.
    public const ESTADOS_RESERVA_ACTIVOS = ['Pendiente', 'Confirmada', 'En curso'];

    public static function idEstado(string $nombre): int
    {
        $id = DB::table('estado_espacio')->where('nombre_estado', $nombre)->value('estado_espacio_id');

        if ($id === null) {
            throw new \RuntimeException("No existe el estado de espacio \"{$nombre}\".");
        }

        return (int) $id;
    }

    /**
     * HU-50: recalcula el estado del espacio según sus reservas.
     * Un espacio deshabilitado (mantenimiento o fuera de servicio) no cambia.
     * Si tiene reservas activas que aún no terminan queda "Reservado";
     * en caso contrario vuelve a "Disponible".
     */
    public static function actualizarEstadoSegunReservas(int $espacioId): void
    {
        $estadoActual = DB::table('espacio as e')
            ->join('estado_espacio as ee', 'ee.estado_espacio_id', '=', 'e.estado_espacio_id')
            ->where('e.espacio_id', $espacioId)
            ->value('ee.nombre_estado');

        if ($estadoActual === null || in_array($estadoActual, self::ESTADOS_DESHABILITADOS, true)) {
            return;
        }

        $tieneReservas = DB::table('reserva as r')
            ->join('estado_reserva as er', 'er.estado_reserva_id', '=', 'r.estado_reserva_id')
            ->where('r.espacio_id', $espacioId)
            ->whereIn('er.nombre_estado', self::ESTADOS_RESERVA_ACTIVOS)
            ->where('r.fecha_fin', '>', now())
            ->exists();

        DB::table('espacio')
            ->where('espacio_id', $espacioId)
            ->update([
                'estado_espacio_id' => self::idEstado($tieneReservas ? 'Reservado' : 'Disponible'),
            ]);
    }
}
