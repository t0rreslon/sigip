<?php

namespace App\Http\Controllers;

use App\Models\Espacio;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservaController extends Controller
{
    // Horario de atención del gimnasio para las reservas.
    private const HORA_APERTURA = '06:00';
    private const HORA_CIERRE = '21:00';

    // HU-48: consultar la disponibilidad de los espacios según el horario.
    public function index(Request $request)
    {
        $fecha = $this->fechaValida($request->input('fecha')) ?? today();
        $horaInicio = $this->horaValida($request->input('hora_inicio'));
        $horaFin = $this->horaValida($request->input('hora_fin'));

        $espacios = DB::table('espacio as e')
            ->join('estado_espacio as ee', 'ee.estado_espacio_id', '=', 'e.estado_espacio_id')
            ->when($request->filled('espacio'), fn ($q) => $q->where('e.espacio_id', $request->input('espacio')))
            ->select('e.espacio_id', 'e.nombre_espacio', 'e.capacidad', 'ee.nombre_estado')
            ->orderBy('e.nombre_espacio')
            ->get();

        // Reservas activas del día consultado, agrupadas por espacio.
        $reservasDia = $this->consultaReservas()
            ->whereIn('er.nombre_estado', Espacio::ESTADOS_RESERVA_ACTIVOS)
            ->where('r.fecha_inicio', '<', $fecha->copy()->endOfDay())
            ->where('r.fecha_fin', '>', $fecha->copy()->startOfDay())
            ->orderBy('r.fecha_inicio')
            ->get()
            ->groupBy('espacio_id');

        // Si se indicó un horario, se marca qué espacios están libres en ese rango.
        $rangoConsultado = null;

        if ($horaInicio && $horaFin && $horaFin > $horaInicio) {
            $rangoConsultado = [
                $fecha->copy()->setTimeFromTimeString($horaInicio),
                $fecha->copy()->setTimeFromTimeString($horaFin),
            ];
        }

        $espacios = $espacios->map(function ($espacio) use ($reservasDia, $rangoConsultado) {
            $espacio->reservas = $reservasDia->get($espacio->espacio_id, collect());
            $espacio->habilitado = !in_array($espacio->nombre_estado, Espacio::ESTADOS_DESHABILITADOS, true);
            $espacio->libre_en_rango = null;

            if ($rangoConsultado) {
                [$desde, $hasta] = $rangoConsultado;

                $espacio->libre_en_rango = $espacio->habilitado && $espacio->reservas->every(
                    fn ($reserva) => Carbon::parse($reserva->fecha_fin)->lte($desde)
                        || Carbon::parse($reserva->fecha_inicio)->gte($hasta)
                );
            }

            return $espacio;
        });

        // Listado general de reservas con filtros.
        $consultaListado = $this->consultaReservas();

        if ($request->filled('estado_reserva')) {
            $consultaListado->where('r.estado_reserva_id', $request->input('estado_reserva'));
        } else {
            $consultaListado->where('r.fecha_fin', '>', now()->startOfDay());
        }

        if ($request->filled('buscar')) {
            $buscar = trim($request->input('buscar'));

            $consultaListado->where(function ($q) use ($buscar) {
                $q->where('u.documento', 'ILIKE', "%{$buscar}%")
                    ->orWhere('per.documento', 'ILIKE', "%{$buscar}%")
                    ->orWhereRaw("CONCAT_WS(' ', u.nombre, u.apellido) ILIKE ?", ["%{$buscar}%"])
                    ->orWhereRaw("CONCAT_WS(' ', per.nombre, per.apellido) ILIKE ?", ["%{$buscar}%"])
                    ->orWhere('e.nombre_espacio', 'ILIKE', "%{$buscar}%");
            });
        }

        $reservas = $consultaListado
            ->orderBy('r.fecha_inicio')
            ->paginate(10, ['*'], 'pagina')
            ->withQueryString();

        $estadosReserva = DB::table('estado_reserva')->orderBy('estado_reserva_id')->get();
        $espaciosReservables = $espacios->where('habilitado', true)->values();
        $todosLosEspacios = DB::table('espacio')->orderBy('nombre_espacio')->get(['espacio_id', 'nombre_espacio']);

        return view('reservas', [
            'fecha' => $fecha,
            'horaInicio' => $horaInicio,
            'horaFin' => $horaFin,
            'rangoConsultado' => $rangoConsultado,
            'espacios' => $espacios,
            'espaciosReservables' => $espaciosReservables,
            'todosLosEspacios' => $todosLosEspacios,
            'reservas' => $reservas,
            'estadosReserva' => $estadosReserva,
            'horaApertura' => self::HORA_APERTURA,
            'horaCierre' => self::HORA_CIERRE,
        ]);
    }

    // HU-49: registrar reservas de espacios.
    public function store(Request $request)
    {
        $datos = $request->validateWithBag('reservar', [
            'tipo_destinatario' => ['required', 'in:usuario,instructor'],
            'documento' => ['required', 'string', 'max:12'],
            'espacio_id' => ['required', 'integer', 'exists:espacio,espacio_id'],
            'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
        ], [
            'hora_fin.after' => 'La hora final debe ser posterior a la hora inicial.',
            'fecha.after_or_equal' => 'No se pueden registrar reservas en fechas pasadas.',
        ]);

        $inicio = Carbon::parse("{$datos['fecha']} {$datos['hora_inicio']}");
        $fin = Carbon::parse("{$datos['fecha']} {$datos['hora_fin']}");

        $errores = [];

        if ($inicio->lte(now())) {
            $errores['hora_inicio'] = 'La reserva debe comenzar después de la hora actual.';
        }

        if ($datos['hora_inicio'] < self::HORA_APERTURA || $datos['hora_fin'] > self::HORA_CIERRE) {
            $errores['hora_inicio'] = 'Las reservas deben estar entre las ' . self::HORA_APERTURA . ' y las ' . self::HORA_CIERRE . '.';
        }

        if ($errores) {
            throw ValidationException::withMessages($errores)->errorBag('reservar');
        }

        DB::transaction(function () use ($datos, $inicio, $fin) {
            // Bloquear el espacio evita que dos reservas simultáneas se crucen.
            $espacio = DB::table('espacio as e')
                ->join('estado_espacio as ee', 'ee.estado_espacio_id', '=', 'e.estado_espacio_id')
                ->where('e.espacio_id', $datos['espacio_id'])
                ->select('e.espacio_id', 'e.nombre_espacio', 'ee.nombre_estado')
                ->lockForUpdate()
                ->first();

            if (in_array($espacio->nombre_estado, Espacio::ESTADOS_DESHABILITADOS, true)) {
                throw ValidationException::withMessages([
                    'espacio_id' => "{$espacio->nombre_espacio} está en " . mb_strtolower($espacio->nombre_estado) . ' y no se puede reservar.',
                ])->errorBag('reservar');
            }

            $cruce = $this->consultaReservas()
                ->where('r.espacio_id', $espacio->espacio_id)
                ->whereIn('er.nombre_estado', Espacio::ESTADOS_RESERVA_ACTIVOS)
                ->where('r.fecha_inicio', '<', $fin)
                ->where('r.fecha_fin', '>', $inicio)
                ->orderBy('r.fecha_inicio')
                ->first();

            if ($cruce) {
                throw ValidationException::withMessages([
                    'hora_inicio' => "{$espacio->nombre_espacio} ya está reservado de "
                        . Carbon::parse($cruce->fecha_inicio)->format('h:i a') . ' a '
                        . Carbon::parse($cruce->fecha_fin)->format('h:i a') . '. Elige otro horario.',
                ])->errorBag('reservar');
            }

            [$usuarioId, $personalId] = $this->resolverDestinatario($datos['tipo_destinatario'], $datos['documento']);

            DB::table('reserva')->insert([
                'usuario_id' => $usuarioId,
                'personal_id' => $personalId,
                'espacio_id' => $espacio->espacio_id,
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                // Las reservas que registra el personal quedan confirmadas.
                'estado_reserva_id' => $this->idEstadoReserva('Confirmada'),
            ]);

            // HU-50: actualizar el estado del espacio.
            Espacio::actualizarEstadoSegunReservas($espacio->espacio_id);
        });

        return redirect()
            ->route('reservas.index', ['fecha' => $datos['fecha']])
            ->with('success', 'La reserva se registró correctamente.');
    }

    // HU-50: cancelar una reserva y liberar el espacio.
    public function cancelar(Request $request, $id)
    {
        DB::transaction(function () use ($id) {
            // Bloquear solo la fila de la reserva (PostgreSQL no permite FOR UPDATE
            // sobre el lado opcional de un LEFT JOIN).
            DB::table('reserva')->where('reserva_id', $id)->lockForUpdate()->first() ?? abort(404);

            $reserva = $this->consultaReservas()
                ->where('r.reserva_id', $id)
                ->first();

            if (!in_array($reserva->nombre_estado, ['Pendiente', 'Confirmada'], true)) {
                throw ValidationException::withMessages([
                    'reserva' => "No se puede cancelar una reserva en estado \"{$reserva->nombre_estado}\".",
                ]);
            }

            if (Carbon::parse($reserva->fecha_fin)->lte(now())) {
                throw ValidationException::withMessages([
                    'reserva' => 'No se puede cancelar una reserva que ya terminó.',
                ]);
            }

            DB::table('reserva')
                ->where('reserva_id', $reserva->reserva_id)
                ->update(['estado_reserva_id' => $this->idEstadoReserva('Cancelada')]);

            Espacio::actualizarEstadoSegunReservas($reserva->espacio_id);
        });

        return redirect()
            ->back()
            ->with('success', 'La reserva se canceló y el espacio quedó liberado en ese horario.');
    }

    private function consultaReservas()
    {
        return DB::table('reserva as r')
            ->join('estado_reserva as er', 'er.estado_reserva_id', '=', 'r.estado_reserva_id')
            ->join('espacio as e', 'e.espacio_id', '=', 'r.espacio_id')
            ->leftJoin('usuario as u', 'u.usuario_id', '=', 'r.usuario_id')
            ->leftJoin('personal as per', 'per.personal_id', '=', 'r.personal_id')
            ->select(
                'r.*',
                'er.nombre_estado',
                'e.nombre_espacio',
                DB::raw("COALESCE(NULLIF(CONCAT_WS(' ', u.nombre, u.apellido), ''), CONCAT_WS(' ', per.nombre, per.apellido)) AS nombre_destinatario"),
                DB::raw('COALESCE(u.documento, per.documento) AS documento_destinatario'),
                DB::raw("CASE WHEN r.usuario_id IS NOT NULL THEN 'Usuario' ELSE 'Instructor' END AS tipo_destinatario")
            );
    }

    private function resolverDestinatario(string $tipo, string $documento): array
    {
        $documento = trim($documento);

        if ($tipo === 'usuario') {
            $usuario = DB::table('usuario')->where('documento', $documento)->first();

            if (!$usuario) {
                throw ValidationException::withMessages([
                    'documento' => 'No existe un usuario con ese documento.',
                ])->errorBag('reservar');
            }

            if (!$usuario->estado_usuario) {
                throw ValidationException::withMessages([
                    'documento' => 'El usuario se encuentra inactivo.',
                ])->errorBag('reservar');
            }

            return [$usuario->usuario_id, null];
        }

        $personal = DB::table('personal as p')
            ->join('estado_personal as ep', 'ep.estado_personal_id', '=', 'p.estado_personal_id')
            ->where('p.documento', $documento)
            ->select('p.personal_id', 'ep.nombre_estado')
            ->first();

        if (!$personal) {
            throw ValidationException::withMessages([
                'documento' => 'No existe un instructor con ese documento.',
            ])->errorBag('reservar');
        }

        if ($personal->nombre_estado !== 'Activo') {
            throw ValidationException::withMessages([
                'documento' => 'El instructor no se encuentra activo.',
            ])->errorBag('reservar');
        }

        return [null, $personal->personal_id];
    }

    private function idEstadoReserva(string $nombre): int
    {
        return (int) DB::table('estado_reserva')->where('nombre_estado', $nombre)->value('estado_reserva_id');
    }

    private function fechaValida(?string $valor): ?Carbon
    {
        if (!$valor || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $valor)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function horaValida(?string $valor): ?string
    {
        return $valor && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $valor) ? $valor : null;
    }
}
