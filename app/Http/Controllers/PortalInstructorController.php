<?php

namespace App\Http\Controllers;

use App\Models\Espacio;
use App\Support\Auditoria;
use App\Support\Disponibilidad;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Funciones del instructor con su propia cuenta (tipo de cuenta "Instructor").
 */
class PortalInstructorController extends Controller
{
    // HU-149: actividades y horarios asignados al instructor.
    public function horario(Request $request)
    {
        $instructor = $this->instructor($request);

        return view('instructores.horario', InstructorController::datosHorario($instructor, $request) + [
            'instructor' => $instructor,
            'turnos' => collect(),
            'puedeAsignar' => false,
        ]);
    }

    // HU-173: mis reservas (y disponibilidad para solicitar una nueva).
    public function reservas(Request $request)
    {
        $instructor = $this->instructor($request);
        $fecha = ReservaController::fechaValida($request->input('fecha')) ?? today();

        $reservas = ReservaController::consultaReservas()
            ->where('r.personal_id', $instructor->personal_id)
            ->orderByDesc('r.fecha_inicio')
            ->paginate(10)
            ->withQueryString();

        $espacios = ReservaController::disponibilidadDelDia(
            $fecha,
            ReservaController::horaValida($request->input('hora_inicio')),
            ReservaController::horaValida($request->input('hora_fin'))
        );

        return view('portal.reservas', [
            'reservas' => $reservas,
            'espacios' => $espacios,
            'fecha' => $fecha,
            'horaApertura' => Disponibilidad::HORA_APERTURA,
            'horaCierre' => Disponibilidad::HORA_CIERRE,
        ]);
    }

    // HU-169: solicitar la reserva de un espacio (queda pendiente de confirmación).
    public function solicitarReserva(Request $request)
    {
        $instructor = $this->instructor($request);
        $datos = $request->validate(ReservaController::reglasHorario(), ReservaController::mensajes());
        [$inicio, $fin] = ReservaController::horario($datos);

        DB::transaction(function () use ($instructor, $datos, $inicio, $fin) {
            ReservaController::bloquearEspacio($datos['espacio_id']);
            ReservaController::exigirDisponible((int) $datos['espacio_id'], $inicio, $fin);

            $id = DB::table('reserva')->insertGetId([
                'personal_id' => $instructor->personal_id,
                'espacio_id' => $datos['espacio_id'],
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'estado_reserva_id' => ReservaController::idEstado('Pendiente'),
            ], 'reserva_id');

            Auditoria::cambio('reserva', 'INSERT', $id, null, ['espacio_id' => $datos['espacio_id'], 'inicio' => $inicio->toDateTimeString(), 'fin' => $fin->toDateTimeString(), 'estado' => 'Pendiente']);
            Espacio::actualizarEstadoSegunReservas((int) $datos['espacio_id']);
        });

        return redirect()->route('portal.reservas')->with('success', 'Tu solicitud quedó registrada. El personal del gimnasio la confirmará.');
    }

    // HU-171: modificar una de mis reservas (vuelve a quedar pendiente de confirmación).
    public function modificarReserva(Request $request, $id)
    {
        $instructor = $this->instructor($request);
        $datos = $request->validate(ReservaController::reglasHorario(), ReservaController::mensajes());
        [$inicio, $fin] = ReservaController::horario($datos);

        DB::transaction(function () use ($instructor, $datos, $inicio, $fin, $id) {
            $reserva = $this->miReserva($instructor, $id);
            ReservaController::validarCancelable($reserva);

            ReservaController::bloquearEspacio($datos['espacio_id']);
            ReservaController::exigirDisponible((int) $datos['espacio_id'], $inicio, $fin, 'default', (int) $reserva->reserva_id);

            DB::table('reserva')->where('reserva_id', $reserva->reserva_id)->update([
                'espacio_id' => $datos['espacio_id'],
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'estado_reserva_id' => ReservaController::idEstado('Pendiente'),
            ]);

            Auditoria::cambio('reserva', 'UPDATE', $reserva->reserva_id,
                ['espacio_id' => $reserva->espacio_id, 'inicio' => $reserva->fecha_inicio, 'fin' => $reserva->fecha_fin, 'estado' => $reserva->nombre_estado],
                ['espacio_id' => $datos['espacio_id'], 'inicio' => $inicio->toDateTimeString(), 'fin' => $fin->toDateTimeString(), 'estado' => 'Pendiente']);

            Espacio::actualizarEstadoSegunReservas((int) $reserva->espacio_id);
            Espacio::actualizarEstadoSegunReservas((int) $datos['espacio_id']);
        });

        return redirect()->route('portal.reservas')->with('success', 'La reserva se modificó y quedó pendiente de confirmación.');
    }

    // HU-172: cancelar una de mis reservas.
    public function cancelarReserva(Request $request, $id)
    {
        $instructor = $this->instructor($request);

        DB::transaction(function () use ($instructor, $id) {
            $reserva = $this->miReserva($instructor, $id);
            ReservaController::validarCancelable($reserva);

            DB::table('reserva')->where('reserva_id', $reserva->reserva_id)->update(['estado_reserva_id' => ReservaController::idEstado('Cancelada')]);
            Auditoria::cambio('reserva', 'ESTADO', $reserva->reserva_id, ['estado' => $reserva->nombre_estado], ['estado' => 'Cancelada']);
            Espacio::actualizarEstadoSegunReservas((int) $reserva->espacio_id);
        });

        return redirect()->route('portal.reservas')->with('success', 'Tu reserva se canceló.');
    }

    // HU-203: actividades que he solicitado.
    public function actividades(Request $request)
    {
        $instructor = $this->instructor($request);

        $solicitudes = ActividadController::consultaProgramaciones()
            ->where('pa.personal_id', $instructor->personal_id)
            ->orderByDesc('pa.fecha_inicio')
            ->paginate(10)
            ->withQueryString();

        return view('portal.actividades', [
            'solicitudes' => $solicitudes,
            'actividades' => DB::table('actividad')->where('estado', true)->orderBy('nombre_actividad')->get(),
            'espacios' => DB::table('espacio as e')
                ->join('estado_espacio as ee', 'ee.estado_espacio_id', '=', 'e.estado_espacio_id')
                ->whereNotIn('ee.nombre_estado', Espacio::ESTADOS_DESHABILITADOS)
                ->orderBy('e.nombre_espacio')
                ->get(['e.espacio_id', 'e.nombre_espacio']),
            'horaApertura' => Disponibilidad::HORA_APERTURA,
            'horaCierre' => Disponibilidad::HORA_CIERRE,
        ]);
    }

    // HU-200: solicitar una actividad (queda "Solicitada" hasta que el administrador la valide).
    public function solicitarActividad(Request $request)
    {
        $instructor = $this->instructor($request);
        $datos = $request->validate($this->reglasActividad(), ReservaController::mensajes());
        [$inicio, $fin] = ReservaController::horario($datos);

        DB::transaction(function () use ($instructor, $datos, $inicio, $fin) {
            ReservaController::bloquearEspacio($datos['espacio_id']);
            ActividadController::exigirHorarioLibre((int) $datos['espacio_id'], $instructor->personal_id, $inicio, $fin);

            $id = DB::table('programacion_actividad')->insertGetId([
                'actividad_id' => $datos['actividad_id'],
                'personal_id' => $instructor->personal_id,
                'espacio_id' => $datos['espacio_id'],
                'registro_obligatorio' => (bool) ($datos['registro_obligatorio'] ?? false),
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'estado_programacion_actividad_id' => ActividadController::idEstado('Solicitada'),
            ], 'programacion_actividad_id');

            Auditoria::cambio('programacion_actividad', 'INSERT', $id, null, ['actividad_id' => $datos['actividad_id'], 'estado' => 'Solicitada']);
        });

        return redirect()->route('portal.actividades')->with('success', 'Tu solicitud de actividad quedó registrada. El administrador la validará.');
    }

    // HU-202: modificar una actividad que propuse (vuelve a quedar "Solicitada").
    public function modificarActividad(Request $request, $id)
    {
        $instructor = $this->instructor($request);
        $datos = $request->validate($this->reglasActividad(), ReservaController::mensajes());
        [$inicio, $fin] = ReservaController::horario($datos);

        DB::transaction(function () use ($instructor, $datos, $inicio, $fin, $id) {
            $programacion = $this->miProgramacion($instructor, $id);

            ReservaController::bloquearEspacio($datos['espacio_id']);
            ActividadController::exigirHorarioLibre((int) $datos['espacio_id'], $instructor->personal_id, $inicio, $fin, (int) $programacion->programacion_actividad_id);

            DB::table('programacion_actividad')->where('programacion_actividad_id', $programacion->programacion_actividad_id)->update([
                'actividad_id' => $datos['actividad_id'],
                'espacio_id' => $datos['espacio_id'],
                'registro_obligatorio' => (bool) ($datos['registro_obligatorio'] ?? false),
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'estado_programacion_actividad_id' => ActividadController::idEstado('Solicitada'),
            ]);

            Auditoria::cambio('programacion_actividad', 'UPDATE', $programacion->programacion_actividad_id,
                ['inicio' => $programacion->fecha_inicio, 'espacio_id' => $programacion->espacio_id, 'estado' => $programacion->nombre_estado],
                ['inicio' => $inicio->toDateTimeString(), 'espacio_id' => $datos['espacio_id'], 'estado' => 'Solicitada']);
        });

        return redirect()->route('portal.actividades')->with('success', 'La actividad se modificó y quedó pendiente de validación.');
    }

    // HU-202: cancelar una actividad que propuse.
    public function cancelarActividad(Request $request, $id)
    {
        $instructor = $this->instructor($request);

        DB::transaction(function () use ($instructor, $id) {
            $programacion = $this->miProgramacion($instructor, $id);

            DB::table('programacion_actividad')->where('programacion_actividad_id', $programacion->programacion_actividad_id)
                ->update(['estado_programacion_actividad_id' => ActividadController::idEstado('Cancelada')]);

            Auditoria::cambio('programacion_actividad', 'ESTADO', $programacion->programacion_actividad_id, ['estado' => $programacion->nombre_estado], ['estado' => 'Cancelada']);
        });

        return redirect()->route('portal.actividades')->with('success', 'La actividad se canceló.');
    }

    private function reglasActividad(): array
    {
        return ReservaController::reglasHorario() + [
            'actividad_id' => ['required', 'integer', Rule::exists('actividad', 'actividad_id')->where('estado', true)],
            'registro_obligatorio' => ['nullable', 'boolean'],
        ];
    }

    private function instructor(Request $request): object
    {
        $instructor = DB::table('personal as p')
            ->join('estado_personal as ep', 'ep.estado_personal_id', '=', 'p.estado_personal_id')
            ->where('p.personal_id', $request->user()->personal_id)
            ->select('p.*', 'ep.nombre_estado')
            ->first();

        abort_if(!$instructor, 403);

        return $instructor;
    }

    private function miReserva(object $instructor, $id): object
    {
        DB::table('reserva')->where('reserva_id', $id)->where('personal_id', $instructor->personal_id)->lockForUpdate()->first() ?? abort(404);

        return ReservaController::consultaReservas()->where('r.reserva_id', $id)->first();
    }

    private function miProgramacion(object $instructor, $id): object
    {
        DB::table('programacion_actividad')->where('programacion_actividad_id', $id)->where('personal_id', $instructor->personal_id)->lockForUpdate()->first() ?? abort(404);

        $programacion = ActividadController::consultaProgramaciones()->where('pa.programacion_actividad_id', $id)->first();

        if (!in_array($programacion->nombre_estado, ['Solicitada', 'Pendiente'], true) || Carbon::parse($programacion->fecha_inicio)->lte(now())) {
            throw ValidationException::withMessages(['actividad_id' => "Solo puedes cambiar actividades futuras que estén solicitadas o programadas (estado actual: {$programacion->nombre_estado})."]);
        }

        return $programacion;
    }
}
