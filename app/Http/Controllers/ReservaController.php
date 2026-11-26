<?php

namespace App\Http\Controllers;

use App\Models\Espacio;
use App\Support\Auditoria;
use App\Support\Disponibilidad;
use App\Support\PdfReporte;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservaController extends Controller
{
    // HU-48: consultar la disponibilidad de los espacios según el horario.
    public function index(Request $request)
    {
        $fecha = self::fechaValida($request->input('fecha')) ?? today();
        $horaInicio = self::horaValida($request->input('hora_inicio'));
        $horaFin = self::horaValida($request->input('hora_fin'));

        $espacios = self::disponibilidadDelDia($fecha, $horaInicio, $horaFin, $request->input('espacio'));

        // Listado general de reservas con filtros.
        $consultaListado = self::consultaReservas();

        if ($request->filled('estado_reserva')) {
            $consultaListado->where('r.estado_reserva_id', $request->input('estado_reserva'));
        } else {
            $consultaListado->where('r.fecha_fin', '>', now()->startOfDay());
        }

        if ($request->filled('buscar')) {
            self::filtrarBusqueda($consultaListado, $request->input('buscar'));
        }

        $reservas = $consultaListado
            ->orderBy('r.fecha_inicio')
            ->paginate(10, ['*'], 'pagina')
            ->withQueryString();

        // HU-170: solicitudes pendientes de respuesta.
        $solicitudes = self::consultaReservas()
            ->where('er.nombre_estado', 'Pendiente')
            ->where('r.fecha_fin', '>', now())
            ->orderBy('r.fecha_inicio')
            ->get();

        return view('reservas', [
            'fecha' => $fecha,
            'horaInicio' => $horaInicio,
            'horaFin' => $horaFin,
            'rangoConsultado' => $horaInicio && $horaFin && $horaFin > $horaInicio,
            'espacios' => $espacios,
            'espaciosReservables' => $espacios->where('habilitado', true)->values(),
            'todosLosEspacios' => DB::table('espacio')->orderBy('nombre_espacio')->get(['espacio_id', 'nombre_espacio']),
            'reservas' => $reservas,
            'solicitudes' => $solicitudes,
            'estadosReserva' => DB::table('estado_reserva')->orderBy('estado_reserva_id')->get(),
            'horaApertura' => Disponibilidad::HORA_APERTURA,
            'horaCierre' => Disponibilidad::HORA_CIERRE,
        ]);
    }

    // HU-49: registrar reservas de espacios (personal del gimnasio).
    public function store(Request $request)
    {
        $datos = $request->validateWithBag('reservar', self::reglasHorario() + [
            'tipo_destinatario' => ['required', 'in:usuario,instructor'],
            'documento' => ['required', 'string', 'max:12'],
        ], self::mensajes());

        [$inicio, $fin] = self::horario($datos, 'reservar');

        DB::transaction(function () use ($datos, $inicio, $fin) {
            self::bloquearEspacio($datos['espacio_id']);
            self::exigirDisponible((int) $datos['espacio_id'], $inicio, $fin, 'reservar');

            [$usuarioId, $personalId] = $this->resolverDestinatario($datos['tipo_destinatario'], $datos['documento']);

            $id = DB::table('reserva')->insertGetId([
                'usuario_id' => $usuarioId,
                'personal_id' => $personalId,
                'espacio_id' => $datos['espacio_id'],
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                // Las reservas que registra el personal quedan confirmadas.
                'estado_reserva_id' => self::idEstado('Confirmada'),
            ], 'reserva_id');

            Auditoria::cambio('reserva', 'INSERT', $id, null, ['espacio_id' => $datos['espacio_id'], 'inicio' => $inicio->toDateTimeString(), 'fin' => $fin->toDateTimeString()]);

            // HU-50: actualizar el estado del espacio.
            Espacio::actualizarEstadoSegunReservas((int) $datos['espacio_id']);
        });

        return redirect()
            ->route('reservas.index', ['fecha' => $datos['fecha']])
            ->with('success', 'La reserva se registró correctamente.');
    }

    // HU-170: confirmar o rechazar una reserva solicitada.
    public function responder(Request $request, $id)
    {
        $datos = $request->validate([
            'accion' => ['required', 'in:confirmar,rechazar'],
        ]);

        $mensaje = DB::transaction(function () use ($id, $datos) {
            DB::table('reserva')->where('reserva_id', $id)->lockForUpdate()->first() ?? abort(404);
            $reserva = self::consultaReservas()->where('r.reserva_id', $id)->first();

            if ($reserva->nombre_estado !== 'Pendiente') {
                throw ValidationException::withMessages(['accion' => "La reserva ya fue respondida ({$reserva->nombre_estado})."]);
            }

            if ($datos['accion'] === 'confirmar') {
                if (Carbon::parse($reserva->fecha_inicio)->lte(now())) {
                    throw ValidationException::withMessages(['accion' => 'La reserva ya comenzó; no se puede confirmar.']);
                }

                self::bloquearEspacio($reserva->espacio_id);
                $conflicto = Disponibilidad::conflicto(
                    (int) $reserva->espacio_id,
                    Carbon::parse($reserva->fecha_inicio),
                    Carbon::parse($reserva->fecha_fin),
                    (int) $reserva->reserva_id
                );

                if ($conflicto) {
                    throw ValidationException::withMessages(['accion' => $conflicto]);
                }
            }

            $estado = $datos['accion'] === 'confirmar' ? 'Confirmada' : 'Rechazada';

            DB::table('reserva')->where('reserva_id', $id)->update(['estado_reserva_id' => self::idEstado($estado)]);
            Auditoria::cambio('reserva', 'ESTADO', $id, ['estado' => 'Pendiente'], ['estado' => $estado]);
            Espacio::actualizarEstadoSegunReservas((int) $reserva->espacio_id);

            return "La reserva de {$reserva->nombre_destinatario} quedó " . mb_strtolower($estado) . '.';
        });

        return back()->with('success', $mensaje);
    }

    // HU-50: cancelar una reserva y liberar el espacio.
    public function cancelar(Request $request, $id)
    {
        DB::transaction(function () use ($id) {
            DB::table('reserva')->where('reserva_id', $id)->lockForUpdate()->first() ?? abort(404);
            $reserva = self::consultaReservas()->where('r.reserva_id', $id)->first();

            self::validarCancelable($reserva);

            DB::table('reserva')->where('reserva_id', $id)->update(['estado_reserva_id' => self::idEstado('Cancelada')]);
            Auditoria::cambio('reserva', 'ESTADO', $id, ['estado' => $reserva->nombre_estado], ['estado' => 'Cancelada']);
            Espacio::actualizarEstadoSegunReservas((int) $reserva->espacio_id);
        });

        return back()->with('success', 'La reserva se canceló y el espacio quedó liberado en ese horario.');
    }

    // HU-174: historial de todas las reservas registradas y solicitadas.
    public function historial(Request $request)
    {
        [$desde, $hasta] = self::periodo($request);

        $reservas = self::consultaHistorial($request, $desde, $hasta)
            ->paginate(15)
            ->withQueryString();

        $conteo = self::consultaHistorial($request, $desde, $hasta)
            ->reorder()
            ->select('er.nombre_estado', DB::raw('COUNT(*) AS total'))
            ->groupBy('er.nombre_estado')
            ->pluck('total', 'nombre_estado');

        return view('reservas-historial', [
            'reservas' => $reservas,
            'conteo' => $conteo,
            'desde' => $desde,
            'hasta' => $hasta,
            'estadosReserva' => DB::table('estado_reserva')->orderBy('estado_reserva_id')->get(),
            'espacios' => DB::table('espacio')->orderBy('nombre_espacio')->get(['espacio_id', 'nombre_espacio']),
        ]);
    }

    // HU-175: reporte PDF de las reservas.
    public function exportar(Request $request)
    {
        [$desde, $hasta] = self::periodo($request);
        $reservas = self::consultaHistorial($request, $desde, $hasta)->get();

        Auditoria::movimiento('Exportar', ['reporte' => 'reservas', 'filtros' => $request->query()]);

        $pdf = PdfReporte::tabla('Reservas de espacios', [
            ['titulo' => 'Espacio', 'ancho' => 150],
            ['titulo' => 'Reservado para', 'ancho' => 170],
            ['titulo' => 'Tipo', 'ancho' => 70],
            ['titulo' => 'Fecha', 'ancho' => 75],
            ['titulo' => 'Horario', 'ancho' => 120],
            ['titulo' => 'Estado', 'ancho' => 80],
            ['titulo' => 'Creada', 'ancho' => 117],
        ], $reservas->map(fn ($r) => [
            $r->nombre_espacio,
            $r->nombre_destinatario,
            $r->tipo_destinatario,
            Carbon::parse($r->fecha_inicio)->format('d/m/Y'),
            Carbon::parse($r->fecha_inicio)->format('h:i a') . ' – ' . Carbon::parse($r->fecha_fin)->format('h:i a'),
            $r->nombre_estado,
            Carbon::parse($r->fecha_creacion)->format('d/m/Y h:i a'),
        ]), [
            'Periodo: ' . $desde->format('d/m/Y') . ' a ' . $hasta->format('d/m/Y') . ' · Reservas: ' . $reservas->count(),
            $reservas->groupBy('nombre_estado')->map->count()->map(fn ($n, $estado) => "{$estado}: {$n}")->implode(' · '),
        ]);

        return PdfReporte::descargar($pdf, 'reservas-' . $desde->format('Ymd') . '-' . $hasta->format('Ymd') . '.pdf');
    }

    /*
    |--------------------------------------------------------------------------
    | Utilidades compartidas con el portal del instructor
    |--------------------------------------------------------------------------
    */

    public static function consultaReservas()
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

    /**
     * Ocupación de cada espacio en un día: reservas y actividades programadas.
     */
    public static function disponibilidadDelDia(Carbon $fecha, ?string $horaInicio, ?string $horaFin, $espacioId = null)
    {
        $inicioDia = $fecha->copy()->startOfDay();
        $finDia = $fecha->copy()->endOfDay();

        $espacios = DB::table('espacio as e')
            ->join('estado_espacio as ee', 'ee.estado_espacio_id', '=', 'e.estado_espacio_id')
            ->when($espacioId, fn ($q) => $q->where('e.espacio_id', $espacioId))
            ->select('e.espacio_id', 'e.nombre_espacio', 'e.capacidad', 'ee.nombre_estado')
            ->orderBy('e.nombre_espacio')
            ->get();

        $reservas = self::consultaReservas()
            ->whereIn('er.nombre_estado', Espacio::ESTADOS_RESERVA_ACTIVOS)
            ->where('r.fecha_inicio', '<', $finDia)
            ->where('r.fecha_fin', '>', $inicioDia)
            ->get()
            ->map(fn ($r) => (object) [
                'espacio_id' => $r->espacio_id,
                'fecha_inicio' => $r->fecha_inicio,
                'fecha_fin' => $r->fecha_fin,
                'descripcion' => "Reserva · {$r->nombre_destinatario}" . ($r->nombre_estado === 'Pendiente' ? ' (por confirmar)' : ''),
            ]);

        $actividades = DB::table('programacion_actividad as pa')
            ->join('actividad as a', 'a.actividad_id', '=', 'pa.actividad_id')
            ->join('estado_programacion_actividad as ep', 'ep.estado_programacion_actividad_id', '=', 'pa.estado_programacion_actividad_id')
            ->whereIn('ep.nombre_estado', Disponibilidad::ESTADOS_PROGRAMACION_ACTIVOS)
            ->where('pa.fecha_inicio', '<', $finDia)
            ->where('pa.fecha_fin', '>', $inicioDia)
            ->get(['pa.espacio_id', 'pa.fecha_inicio', 'pa.fecha_fin', 'a.nombre_actividad', 'ep.nombre_estado'])
            ->map(fn ($a) => (object) [
                'espacio_id' => $a->espacio_id,
                'fecha_inicio' => $a->fecha_inicio,
                'fecha_fin' => $a->fecha_fin,
                'descripcion' => "Actividad · {$a->nombre_actividad}" . ($a->nombre_estado === 'Solicitada' ? ' (solicitada)' : ''),
            ]);

        $ocupaciones = $reservas->concat($actividades)->sortBy('fecha_inicio')->groupBy('espacio_id');

        $rango = null;

        if ($horaInicio && $horaFin && $horaFin > $horaInicio) {
            $rango = [$fecha->copy()->setTimeFromTimeString($horaInicio), $fecha->copy()->setTimeFromTimeString($horaFin)];
        }

        return $espacios->map(function ($espacio) use ($ocupaciones, $rango) {
            $espacio->reservas = $ocupaciones->get($espacio->espacio_id, collect())->values();
            $espacio->habilitado = !in_array($espacio->nombre_estado, Espacio::ESTADOS_DESHABILITADOS, true);
            $espacio->libre_en_rango = null;

            if ($rango) {
                [$desde, $hasta] = $rango;

                $espacio->libre_en_rango = $espacio->habilitado && $espacio->reservas->every(
                    fn ($o) => Carbon::parse($o->fecha_fin)->lte($desde) || Carbon::parse($o->fecha_inicio)->gte($hasta)
                );
            }

            return $espacio;
        });
    }

    public static function reglasHorario(): array
    {
        return [
            'espacio_id' => ['required', 'integer', 'exists:espacio,espacio_id'],
            'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
        ];
    }

    public static function mensajes(): array
    {
        return [
            'hora_fin.after' => 'La hora final debe ser posterior a la hora inicial.',
            'fecha.after_or_equal' => 'No se pueden usar fechas pasadas.',
        ];
    }

    /**
     * Convierte fecha + horas en el rango [inicio, fin] y valida el horario de atención.
     */
    public static function horario(array $datos, string $bolsa = 'default'): array
    {
        $inicio = Carbon::parse("{$datos['fecha']} {$datos['hora_inicio']}");
        $fin = Carbon::parse("{$datos['fecha']} {$datos['hora_fin']}");

        if ($error = Disponibilidad::validarHorario($inicio, $fin)) {
            throw ValidationException::withMessages(['hora_inicio' => $error])->errorBag($bolsa);
        }

        return [$inicio, $fin];
    }

    public static function exigirDisponible(int $espacioId, Carbon $inicio, Carbon $fin, string $bolsa = 'default', ?int $ignorarReserva = null, ?int $ignorarProgramacion = null): void
    {
        if ($conflicto = Disponibilidad::revisar($espacioId, $inicio, $fin, $ignorarReserva, $ignorarProgramacion)) {
            [$campo, $mensaje] = $conflicto;
            throw ValidationException::withMessages([$campo => $mensaje])->errorBag($bolsa);
        }
    }

    /**
     * Bloquea la fila del espacio para que dos reservas simultáneas no se crucen.
     */
    public static function bloquearEspacio($espacioId): void
    {
        DB::table('espacio')->where('espacio_id', $espacioId)->lockForUpdate()->first();
    }

    public static function validarCancelable($reserva): void
    {
        if (!$reserva) {
            abort(404);
        }

        if (!in_array($reserva->nombre_estado, ['Pendiente', 'Confirmada'], true)) {
            throw ValidationException::withMessages(['reserva' => "No se puede cancelar una reserva en estado \"{$reserva->nombre_estado}\"."]);
        }

        if (Carbon::parse($reserva->fecha_fin)->lte(now())) {
            throw ValidationException::withMessages(['reserva' => 'No se puede cancelar una reserva que ya terminó.']);
        }
    }

    public static function idEstado(string $nombre): int
    {
        return (int) DB::table('estado_reserva')->where('nombre_estado', $nombre)->value('estado_reserva_id');
    }

    public static function fechaValida(?string $valor): ?Carbon
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

    public static function horaValida(?string $valor): ?string
    {
        return $valor && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $valor) ? $valor : null;
    }

    private static function filtrarBusqueda($consulta, string $buscar): void
    {
        $buscar = trim($buscar);

        $consulta->where(function ($q) use ($buscar) {
            $q->where('u.documento', 'ILIKE', \App\Support\Busqueda::contiene($buscar))
                ->orWhere('per.documento', 'ILIKE', \App\Support\Busqueda::contiene($buscar))
                ->orWhereRaw("CONCAT_WS(' ', u.nombre, u.apellido) ILIKE ?", [\App\Support\Busqueda::contiene($buscar)])
                ->orWhereRaw("CONCAT_WS(' ', per.nombre, per.apellido) ILIKE ?", [\App\Support\Busqueda::contiene($buscar)])
                ->orWhere('e.nombre_espacio', 'ILIKE', \App\Support\Busqueda::contiene($buscar));
        });
    }

    private static function periodo(Request $request): array
    {
        $desde = self::fechaValida($request->input('desde')) ?? now()->subDays(30)->startOfDay();
        $hasta = (self::fechaValida($request->input('hasta')) ?? now()->addDays(30))->copy()->endOfDay();

        return $hasta->lt($desde) ? [$hasta->copy()->startOfDay(), $desde->copy()->endOfDay()] : [$desde, $hasta];
    }

    private static function consultaHistorial(Request $request, Carbon $desde, Carbon $hasta)
    {
        $consulta = self::consultaReservas()
            ->whereBetween('r.fecha_inicio', [$desde, $hasta])
            ->when($request->filled('estado_reserva'), fn ($q) => $q->where('r.estado_reserva_id', $request->input('estado_reserva')))
            ->when($request->filled('espacio'), fn ($q) => $q->where('r.espacio_id', $request->input('espacio')))
            ->when($request->input('tipo') === 'usuario', fn ($q) => $q->whereNotNull('r.usuario_id'))
            ->when($request->input('tipo') === 'instructor', fn ($q) => $q->whereNotNull('r.personal_id'))
            ->orderByDesc('r.fecha_inicio');

        if ($request->filled('buscar')) {
            self::filtrarBusqueda($consulta, $request->input('buscar'));
        }

        return $consulta;
    }

    private function resolverDestinatario(string $tipo, string $documento): array
    {
        $documento = trim($documento);

        if ($tipo === 'usuario') {
            $usuario = DB::table('usuario')->where('documento', $documento)->first();

            if (!$usuario) {
                throw ValidationException::withMessages(['documento' => 'No existe un usuario con ese documento.'])->errorBag('reservar');
            }

            if (!$usuario->estado_usuario) {
                throw ValidationException::withMessages(['documento' => 'El usuario se encuentra inactivo.'])->errorBag('reservar');
            }

            return [$usuario->usuario_id, null];
        }

        $personal = DB::table('personal as p')
            ->join('estado_personal as ep', 'ep.estado_personal_id', '=', 'p.estado_personal_id')
            ->where('p.documento', $documento)
            ->select('p.personal_id', 'ep.nombre_estado')
            ->first();

        if (!$personal) {
            throw ValidationException::withMessages(['documento' => 'No existe un instructor con ese documento.'])->errorBag('reservar');
        }

        if ($personal->nombre_estado !== 'Activo') {
            throw ValidationException::withMessages(['documento' => 'El instructor no se encuentra activo.'])->errorBag('reservar');
        }

        return [null, $personal->personal_id];
    }
}
