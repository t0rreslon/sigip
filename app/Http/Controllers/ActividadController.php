<?php

namespace App\Http\Controllers;

use App\Support\Auditoria;
use App\Support\Disponibilidad;
use App\Support\PdfReporte;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ActividadController extends Controller
{
    // HU-53 / HU-177 / HU-179: actividades registradas y programadas (con historial).
    public function index(Request $request)
    {
        $vista = in_array($request->input('vista'), ['proximas', 'historial', 'solicitadas'], true) ? $request->input('vista') : 'proximas';

        $consulta = self::consultaProgramaciones()
            ->when($request->filled('actividad'), fn ($q) => $q->where('pa.actividad_id', $request->input('actividad')))
            ->when($request->filled('instructor'), fn ($q) => $q->where('pa.personal_id', $request->input('instructor')))
            ->when($request->filled('espacio'), fn ($q) => $q->where('pa.espacio_id', $request->input('espacio')));

        match ($vista) {
            'proximas' => $consulta->where('pa.fecha_fin', '>', now())->whereIn('ep.nombre_estado', ['Pendiente', 'En curso'])->orderBy('pa.fecha_inicio'),
            'solicitadas' => $consulta->where('ep.nombre_estado', 'Solicitada')->orderBy('pa.fecha_inicio'),
            'historial' => $consulta->orderByDesc('pa.fecha_inicio'),
        };

        return view('actividades.index', [
            'vista' => $vista,
            'programaciones' => $consulta->paginate(12)->withQueryString(),
            'actividades' => DB::table('actividad')->orderByDesc('estado')->orderBy('nombre_actividad')->get(),
            'instructores' => self::instructoresActivos(),
            'espacios' => DB::table('espacio')->orderBy('nombre_espacio')->get(['espacio_id', 'nombre_espacio', 'capacidad']),
            'solicitadas' => self::consultaProgramaciones()->where('ep.nombre_estado', 'Solicitada')->count(),
            'horaApertura' => Disponibilidad::HORA_APERTURA,
            'horaCierre' => Disponibilidad::HORA_CIERRE,
        ]);
    }

    // HU-51: registrar actividades (catálogo).
    public function store(Request $request)
    {
        $datos = $request->validate($this->reglasActividad());
        $datos['registro_obligatorio'] = (bool) ($datos['registro_obligatorio'] ?? false);
        $datos['estado'] = true;

        $id = DB::table('actividad')->insertGetId($datos, 'actividad_id');
        Auditoria::cambio('actividad', 'INSERT', $id, null, $datos);

        return back()->with('success', "La actividad \"{$datos['nombre_actividad']}\" se registró.");
    }

    // Modificar o activar/desactivar una actividad del catálogo.
    public function update(Request $request, $id)
    {
        $actividad = DB::table('actividad')->where('actividad_id', $id)->first();
        abort_if(!$actividad, 404);

        $datos = $request->validate($this->reglasActividad((int) $id) + ['estado' => ['required', 'boolean']]);
        $datos['registro_obligatorio'] = (bool) ($datos['registro_obligatorio'] ?? false);

        DB::table('actividad')->where('actividad_id', $id)->update($datos);
        Auditoria::cambio('actividad', 'UPDATE', $id, (array) $actividad, $datos);

        return back()->with('success', 'La actividad se actualizó.');
    }

    // HU-178 / HU-52: programar una actividad con instructor, espacio y horario.
    public function programar(Request $request)
    {
        $datos = $request->validate($this->reglasProgramacion(), ReservaController::mensajes());
        [$inicio, $fin] = ReservaController::horario($datos);

        $id = DB::transaction(function () use ($datos, $inicio, $fin) {
            ReservaController::bloquearEspacio($datos['espacio_id']);
            self::exigirHorarioLibre((int) $datos['espacio_id'], (int) $datos['personal_id'], $inicio, $fin);

            $actividad = DB::table('actividad')->where('actividad_id', $datos['actividad_id'])->first();

            $id = DB::table('programacion_actividad')->insertGetId([
                'actividad_id' => $datos['actividad_id'],
                'personal_id' => $datos['personal_id'],
                'espacio_id' => $datos['espacio_id'],
                'registro_obligatorio' => array_key_exists('registro_obligatorio', $datos) ? (bool) $datos['registro_obligatorio'] : (bool) $actividad->registro_obligatorio,
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'estado_programacion_actividad_id' => self::idEstado('Pendiente'),
            ], 'programacion_actividad_id');

            Auditoria::cambio('programacion_actividad', 'INSERT', $id, null, collect($datos)->only(['actividad_id', 'personal_id', 'espacio_id'])->all() + ['inicio' => $inicio->toDateTimeString()]);

            return $id;
        });

        return redirect()->route('programaciones.show', $id)->with('success', 'La actividad quedó programada y aparece en el horario del instructor.');
    }

    // HU-176: modificar una actividad programada (libera el horario anterior).
    public function actualizarProgramacion(Request $request, $id)
    {
        $datos = $request->validate($this->reglasProgramacion(), ReservaController::mensajes());
        [$inicio, $fin] = ReservaController::horario($datos);

        DB::transaction(function () use ($datos, $inicio, $fin, $id) {
            $programacion = $this->programacionModificable($id);

            ReservaController::bloquearEspacio($datos['espacio_id']);
            self::exigirHorarioLibre((int) $datos['espacio_id'], (int) $datos['personal_id'], $inicio, $fin, (int) $id);

            $nuevos = [
                'actividad_id' => $datos['actividad_id'],
                'personal_id' => $datos['personal_id'],
                'espacio_id' => $datos['espacio_id'],
                'registro_obligatorio' => (bool) ($datos['registro_obligatorio'] ?? false),
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
            ];

            DB::table('programacion_actividad')->where('programacion_actividad_id', $id)->update($nuevos);
            Auditoria::cambio('programacion_actividad', 'UPDATE', $id, (array) $programacion, array_map(fn ($v) => $v instanceof Carbon ? $v->toDateTimeString() : $v, $nuevos));
        });

        return back()->with('success', 'La actividad programada se actualizó.');
    }

    // HU-176: cancelar una actividad programada (el espacio queda libre).
    public function cancelarProgramacion($id)
    {
        DB::transaction(function () use ($id) {
            $programacion = $this->programacionModificable($id);

            DB::table('programacion_actividad')->where('programacion_actividad_id', $id)
                ->update(['estado_programacion_actividad_id' => self::idEstado('Cancelada')]);

            Auditoria::cambio('programacion_actividad', 'ESTADO', $id, ['estado' => $programacion->nombre_estado], ['estado' => 'Cancelada']);
        });

        return back()->with('success', 'La actividad se canceló y el espacio quedó disponible en ese horario.');
    }

    // HU-201: validar (aprobar o rechazar) una actividad solicitada por un instructor.
    public function validar(Request $request, $id)
    {
        $datos = $request->validate(['accion' => ['required', 'in:aprobar,rechazar']]);

        $mensaje = DB::transaction(function () use ($datos, $id) {
            DB::table('programacion_actividad')->where('programacion_actividad_id', $id)->lockForUpdate()->first() ?? abort(404);
            $programacion = self::consultaProgramaciones()->where('pa.programacion_actividad_id', $id)->first();

            if ($programacion->nombre_estado !== 'Solicitada') {
                throw ValidationException::withMessages(['accion' => "La actividad ya fue validada ({$programacion->nombre_estado})."]);
            }

            if ($datos['accion'] === 'aprobar') {
                if (Carbon::parse($programacion->fecha_inicio)->lte(now())) {
                    throw ValidationException::withMessages(['accion' => 'La fecha de la actividad ya pasó; no se puede aprobar.']);
                }

                ReservaController::bloquearEspacio($programacion->espacio_id);
                self::exigirHorarioLibre((int) $programacion->espacio_id, (int) $programacion->personal_id,
                    Carbon::parse($programacion->fecha_inicio), Carbon::parse($programacion->fecha_fin), (int) $id);
            }

            $estado = $datos['accion'] === 'aprobar' ? 'Pendiente' : 'Rechazada';

            DB::table('programacion_actividad')->where('programacion_actividad_id', $id)
                ->update(['estado_programacion_actividad_id' => self::idEstado($estado)]);

            Auditoria::cambio('programacion_actividad', 'ESTADO', $id, ['estado' => 'Solicitada'], ['estado' => $estado]);

            return $datos['accion'] === 'aprobar'
                ? "\"{$programacion->nombre_actividad}\" quedó programada."
                : "La solicitud \"{$programacion->nombre_actividad}\" fue rechazada.";
        });

        return back()->with('success', $mensaje);
    }

    // HU-181 / HU-182: detalle de una actividad programada, cupos e inscritos.
    public function show(Request $request, $id)
    {
        $programacion = self::consultaProgramaciones()->where('pa.programacion_actividad_id', $id)->first();
        abort_if(!$programacion, 404);
        $this->autorizarInstructor($request, $programacion);

        $inscritos = DB::table('registro_actividad as ra')
            ->join('usuario as u', 'u.usuario_id', '=', 'ra.usuario_id')
            ->where('ra.programacion_actividad_id', $id)
            ->orderBy('u.apellido')
            ->select('u.usuario_id', 'u.nombre', 'u.apellido', 'u.documento')
            ->get();

        $asistentes = DB::table('asistencia_actividad')->where('programacion_actividad_id', $id)->pluck('usuario_id')->map(fn ($v) => (int) $v)->all();

        // Asistentes sin inscripción previa (actividades de registro libre).
        $asistentesLibres = DB::table('asistencia_actividad as aa')
            ->join('usuario as u', 'u.usuario_id', '=', 'aa.usuario_id')
            ->where('aa.programacion_actividad_id', $id)
            ->whereNotIn('aa.usuario_id', $inscritos->pluck('usuario_id'))
            ->select('u.usuario_id', 'u.nombre', 'u.apellido', 'u.documento')
            ->get();

        return view('actividades.programacion', [
            'programacion' => $programacion,
            'inscritos' => $inscritos,
            'asistentes' => $asistentes,
            'asistentesLibres' => $asistentesLibres,
            'cupos' => $programacion->maximo_participantes ? max(0, $programacion->maximo_participantes - $inscritos->count()) : null,
            'puedeGestionar' => $request->user()->tieneTipo('Administrador', 'Operador'),
            'actividades' => DB::table('actividad')->where('estado', true)->orderBy('nombre_actividad')->get(),
            'instructores' => self::instructoresActivos(),
            'espacios' => DB::table('espacio')->orderBy('nombre_espacio')->get(['espacio_id', 'nombre_espacio']),
            'historial' => Auditoria::historial('programacion_actividad', $id),
            'horaApertura' => Disponibilidad::HORA_APERTURA,
            'horaCierre' => Disponibilidad::HORA_CIERRE,
        ]);
    }

    // HU-183: inscribir un usuario a una actividad.
    public function inscribir(Request $request, $id)
    {
        $datos = $request->validate(['documento' => ['required', 'string', 'max:12']]);

        $nombre = DB::transaction(function () use ($datos, $id) {
            DB::table('programacion_actividad')->where('programacion_actividad_id', $id)->lockForUpdate()->first() ?? abort(404);
            $programacion = self::consultaProgramaciones()->where('pa.programacion_actividad_id', $id)->first();

            if (!in_array($programacion->nombre_estado, ['Pendiente', 'En curso'], true) || Carbon::parse($programacion->fecha_fin)->lte(now())) {
                throw ValidationException::withMessages(['documento' => 'Solo se puede inscribir en actividades programadas que no han terminado.']);
            }

            $usuario = DB::table('usuario')->where('documento', trim($datos['documento']))->first();

            if (!$usuario) {
                throw ValidationException::withMessages(['documento' => 'No existe un usuario con ese documento.']);
            }

            if (!$usuario->estado_usuario) {
                throw ValidationException::withMessages(['documento' => 'El usuario se encuentra inactivo.']);
            }

            if (DB::table('registro_actividad')->where('programacion_actividad_id', $id)->where('usuario_id', $usuario->usuario_id)->exists()) {
                throw ValidationException::withMessages(['documento' => 'El usuario ya está inscrito en esta actividad.']);
            }

            $inscritos = DB::table('registro_actividad')->where('programacion_actividad_id', $id)->count();

            if ($programacion->maximo_participantes && $inscritos >= $programacion->maximo_participantes) {
                throw ValidationException::withMessages(['documento' => 'La actividad no tiene cupos disponibles.']);
            }

            DB::table('registro_actividad')->insert([
                'usuario_id' => $usuario->usuario_id,
                'programacion_actividad_id' => $id,
            ]);

            Auditoria::cambio('programacion_actividad', 'UPDATE', $id,
                ['inscripcion' => null],
                ['inscripcion' => "Inscribió a {$usuario->nombre} {$usuario->apellido} ({$usuario->documento})"]);

            return "{$usuario->nombre} {$usuario->apellido}";
        });

        return back()->with('success', "{$nombre} quedó inscrito en la actividad.");
    }

    // Retirar la inscripción de un usuario.
    public function desinscribir($id, $usuarioId)
    {
        DB::transaction(function () use ($id, $usuarioId) {
            $borrados = DB::table('registro_actividad')->where('programacion_actividad_id', $id)->where('usuario_id', $usuarioId)->delete();
            DB::table('asistencia_actividad')->where('programacion_actividad_id', $id)->where('usuario_id', $usuarioId)->delete();

            if ($borrados) {
                $usuario = DB::table('usuario')->where('usuario_id', $usuarioId)->first(['nombre', 'apellido', 'documento']);

                Auditoria::cambio('programacion_actividad', 'UPDATE', $id,
                    ['inscripcion' => "{$usuario?->nombre} {$usuario?->apellido} ({$usuario?->documento})"],
                    ['inscripcion' => 'Inscripción retirada']);
            }
        });

        return back()->with('success', 'Se retiró la inscripción del usuario.');
    }

    // HU-180 / HU-184: registrar o corregir la asistencia de una actividad.
    public function asistencia(Request $request, $id)
    {
        $programacion = self::consultaProgramaciones()->where('pa.programacion_actividad_id', $id)->first();
        abort_if(!$programacion, 404);
        $this->autorizarInstructor($request, $programacion);

        $datos = $request->validate([
            'asistentes' => ['nullable', 'array'],
            'asistentes.*' => ['integer'],
            'documento_libre' => ['nullable', 'string', 'max:12'],
        ]);

        if (in_array($programacion->nombre_estado, ['Solicitada', 'Cancelada', 'Rechazada'], true)) {
            throw ValidationException::withMessages(['asistentes' => 'No se registra asistencia en una actividad ' . mb_strtolower($programacion->nombre_estado) . '.']);
        }

        if (Carbon::parse($programacion->fecha_inicio)->subMinutes(30)->isFuture()) {
            throw ValidationException::withMessages(['asistentes' => 'La asistencia se registra desde 30 minutos antes del inicio de la actividad.']);
        }

        $inscritos = DB::table('registro_actividad')->where('programacion_actividad_id', $id)->pluck('usuario_id')->map(fn ($v) => (int) $v);
        $marcados = collect($datos['asistentes'] ?? [])->map(fn ($v) => (int) $v)->intersect($inscritos);

        // Asistente que no estaba inscrito (solo si la actividad no exige inscripción previa).
        if (!empty($datos['documento_libre'])) {
            if ($programacion->registro_obligatorio) {
                throw ValidationException::withMessages(['documento_libre' => 'Esta actividad exige inscripción previa: inscribe primero al usuario.']);
            }

            $usuario = DB::table('usuario')->where('documento', trim($datos['documento_libre']))->where('estado_usuario', true)->first();

            if (!$usuario) {
                throw ValidationException::withMessages(['documento_libre' => 'No existe un usuario activo con ese documento.']);
            }

            $marcados->push((int) $usuario->usuario_id);
        }

        // Se conservan los asistentes libres ya registrados.
        $libresPrevios = DB::table('asistencia_actividad')->where('programacion_actividad_id', $id)
            ->whereNotIn('usuario_id', $inscritos)->pluck('usuario_id')->map(fn ($v) => (int) $v);
        $marcados = $marcados->concat($libresPrevios)->unique()->values();

        DB::transaction(function () use ($id, $marcados, $programacion) {
            $antes = DB::table('asistencia_actividad')->where('programacion_actividad_id', $id)->pluck('usuario_id')->map(fn ($v) => (int) $v)->sort()->values()->all();

            DB::table('asistencia_actividad')->where('programacion_actividad_id', $id)->whereNotIn('usuario_id', $marcados)->delete();

            // asistencia_actividad no tiene índice único: solo se insertan los que faltan.
            foreach ($marcados->diff($antes) as $usuarioId) {
                DB::table('asistencia_actividad')->insert([
                    'usuario_id' => $usuarioId,
                    'programacion_actividad_id' => $id,
                ]);
            }

            // Una actividad que ya terminó queda finalizada al registrar su asistencia.
            if (Carbon::parse($programacion->fecha_fin)->isPast() && in_array($programacion->nombre_estado, ['Pendiente', 'En curso'], true)) {
                DB::table('programacion_actividad')->where('programacion_actividad_id', $id)
                    ->update(['estado_programacion_actividad_id' => self::idEstado('Finalizada')]);
            }

            Auditoria::cambio('programacion_actividad', 'UPDATE', $id,
                ['asistentes' => implode(', ', $antes)],
                ['asistentes' => $marcados->sort()->implode(', ')]);
        });

        return back()->with('success', "Asistencia guardada: {$marcados->count()} participante(s).");
    }

    // HU-185: historial de inscripciones y asistencia de un participante.
    public function participante(Request $request)
    {
        $usuario = $request->filled('documento')
            ? DB::table('usuario')->where('documento', trim($request->input('documento')))->first()
            : null;

        $registros = collect();

        if ($usuario) {
            $registros = self::consultaProgramaciones()
                ->where(function ($q) use ($usuario) {
                    $q->whereExists(fn ($s) => $s->select(DB::raw(1))->from('registro_actividad as ra')
                        ->whereColumn('ra.programacion_actividad_id', 'pa.programacion_actividad_id')->where('ra.usuario_id', $usuario->usuario_id))
                        ->orWhereExists(fn ($s) => $s->select(DB::raw(1))->from('asistencia_actividad as aa')
                            ->whereColumn('aa.programacion_actividad_id', 'pa.programacion_actividad_id')->where('aa.usuario_id', $usuario->usuario_id));
                })
                ->selectRaw('EXISTS (SELECT 1 FROM asistencia_actividad aa WHERE aa.programacion_actividad_id = pa.programacion_actividad_id AND aa.usuario_id = ?) AS asistio', [$usuario->usuario_id])
                ->selectRaw('EXISTS (SELECT 1 FROM registro_actividad ra WHERE ra.programacion_actividad_id = pa.programacion_actividad_id AND ra.usuario_id = ?) AS inscrito', [$usuario->usuario_id])
                ->orderByDesc('pa.fecha_inicio')
                ->get();
        }

        return view('actividades.participante', compact('usuario', 'registros'));
    }

    // HU-205: reporte de participación y asistencia por actividad y periodo.
    public function reporte(Request $request)
    {
        [$desde, $hasta, $filas] = $this->datosReporte($request);

        return view('actividades.reporte', compact('desde', 'hasta', 'filas') + [
            'actividades' => DB::table('actividad')->orderBy('nombre_actividad')->get(['actividad_id', 'nombre_actividad']),
        ]);
    }

    public function exportarReporte(Request $request)
    {
        [$desde, $hasta, $filas] = $this->datosReporte($request);

        Auditoria::movimiento('Exportar', ['reporte' => 'participación en actividades', 'filtros' => $request->query()]);

        $pdf = PdfReporte::tabla('Participación y asistencia por actividad', [
            ['titulo' => 'Actividad', 'ancho' => 210],
            ['titulo' => 'Sesiones', 'ancho' => 80],
            ['titulo' => 'Canceladas', 'ancho' => 80],
            ['titulo' => 'Inscripciones', 'ancho' => 100],
            ['titulo' => 'Asistencias', 'ancho' => 100],
            ['titulo' => 'Personas distintas', 'ancho' => 110],
            ['titulo' => '% asistencia', 'ancho' => 102],
        ], $filas->map(fn ($f) => [
            $f->nombre_actividad, $f->sesiones, $f->canceladas, $f->inscripciones, $f->asistencias, $f->personas,
            $f->inscripciones > 0 ? round($f->asistencias / $f->inscripciones * 100) . ' %' : '—',
        ]), [
            'Periodo: ' . $desde->format('d/m/Y') . ' a ' . $hasta->format('d/m/Y'),
            'Total de asistencias: ' . $filas->sum('asistencias') . ' · Total de inscripciones: ' . $filas->sum('inscripciones'),
        ]);

        return PdfReporte::descargar($pdf, 'participacion-' . $desde->format('Ymd') . '-' . $hasta->format('Ymd') . '.pdf');
    }

    /*
    |--------------------------------------------------------------------------
    | Utilidades compartidas
    |--------------------------------------------------------------------------
    */

    public static function consultaProgramaciones()
    {
        return DB::table('programacion_actividad as pa')
            ->join('actividad as a', 'a.actividad_id', '=', 'pa.actividad_id')
            ->join('personal as p', 'p.personal_id', '=', 'pa.personal_id')
            ->join('espacio as e', 'e.espacio_id', '=', 'pa.espacio_id')
            ->join('estado_programacion_actividad as ep', 'ep.estado_programacion_actividad_id', '=', 'pa.estado_programacion_actividad_id')
            ->select(
                'pa.*',
                'a.nombre_actividad',
                'a.descripcion',
                'a.maximo_participantes',
                'e.nombre_espacio',
                'e.capacidad',
                'ep.nombre_estado',
                DB::raw("CONCAT_WS(' ', p.nombre, p.apellido) AS instructor"),
                DB::raw('(SELECT COUNT(*) FROM registro_actividad ra WHERE ra.programacion_actividad_id = pa.programacion_actividad_id) AS inscritos'),
                DB::raw('(SELECT COUNT(*) FROM asistencia_actividad aa WHERE aa.programacion_actividad_id = pa.programacion_actividad_id) AS asistentes')
            );
    }

    /**
     * HU-52: el espacio debe estar libre y el instructor no puede tener otra actividad a la misma hora.
     */
    public static function exigirHorarioLibre(int $espacioId, int $personalId, Carbon $inicio, Carbon $fin, ?int $ignorar = null): void
    {
        ReservaController::exigirDisponible($espacioId, $inicio, $fin, 'default', null, $ignorar);

        $cruceInstructor = DB::table('programacion_actividad as pa')
            ->join('estado_programacion_actividad as ep', 'ep.estado_programacion_actividad_id', '=', 'pa.estado_programacion_actividad_id')
            ->join('actividad as a', 'a.actividad_id', '=', 'pa.actividad_id')
            ->where('pa.personal_id', $personalId)
            ->whereIn('ep.nombre_estado', Disponibilidad::ESTADOS_PROGRAMACION_ACTIVOS)
            ->where('pa.fecha_inicio', '<', $fin)
            ->where('pa.fecha_fin', '>', $inicio)
            ->when($ignorar, fn ($q) => $q->where('pa.programacion_actividad_id', '!=', $ignorar))
            ->first(['a.nombre_actividad', 'pa.fecha_inicio', 'pa.fecha_fin']);

        if ($cruceInstructor) {
            throw ValidationException::withMessages([
                'personal_id' => "El instructor ya tiene \"{$cruceInstructor->nombre_actividad}\" de "
                    . Carbon::parse($cruceInstructor->fecha_inicio)->format('h:i a') . ' a ' . Carbon::parse($cruceInstructor->fecha_fin)->format('h:i a') . '.',
            ]);
        }
    }

    public static function idEstado(string $nombre): int
    {
        return (int) DB::table('estado_programacion_actividad')->where('nombre_estado', $nombre)->value('estado_programacion_actividad_id');
    }

    public static function instructoresActivos()
    {
        $tiposPersonal = DB::table('tipo_cuenta')->whereIn('nombre_cuenta', ['Administrador', 'Operador'])->pluck('tipo_cuenta_id');

        return DB::table('personal as p')
            ->join('estado_personal as ep', 'ep.estado_personal_id', '=', 'p.estado_personal_id')
            ->leftJoin('cuenta as c', 'c.personal_id', '=', 'p.personal_id')
            ->where('ep.nombre_estado', 'Activo')
            ->where(fn ($q) => $q->whereNull('c.cuenta_id')->orWhereNotIn('c.tipo_cuenta_id', $tiposPersonal))
            ->orderBy('p.apellido')
            ->get(['p.personal_id', 'p.nombre', 'p.apellido']);
    }

    private function reglasActividad(?int $id = null): array
    {
        return [
            'nombre_actividad' => ['required', 'string', 'max:50', function ($attribute, $value, $fail) use ($id) {
                $existe = DB::table('actividad')
                    ->whereRaw('LOWER(BTRIM(nombre_actividad)) = ?', [mb_strtolower(trim($value))])
                    ->when($id, fn ($q) => $q->where('actividad_id', '!=', $id))
                    ->exists();

                if ($existe) {
                    $fail('Ya existe una actividad con ese nombre.');
                }
            }],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'maximo_participantes' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'registro_obligatorio' => ['nullable', 'boolean'],
        ];
    }

    private function reglasProgramacion(): array
    {
        return ReservaController::reglasHorario() + [
            'actividad_id' => ['required', 'integer', Rule::exists('actividad', 'actividad_id')->where('estado', true)],
            'personal_id' => ['required', 'integer', Rule::in(self::instructoresActivos()->pluck('personal_id')->all())],
            'registro_obligatorio' => ['nullable', 'boolean'],
        ];
    }

    private function programacionModificable($id): object
    {
        DB::table('programacion_actividad')->where('programacion_actividad_id', $id)->lockForUpdate()->first() ?? abort(404);
        $programacion = self::consultaProgramaciones()->where('pa.programacion_actividad_id', $id)->first();

        if (!in_array($programacion->nombre_estado, ['Pendiente', 'Solicitada'], true) || Carbon::parse($programacion->fecha_inicio)->lte(now())) {
            throw ValidationException::withMessages(['actividad_id' => "Solo se pueden cambiar actividades futuras programadas o solicitadas (estado actual: {$programacion->nombre_estado})."]);
        }

        return $programacion;
    }

    /**
     * Un instructor solo puede ver y registrar asistencia de sus propias actividades.
     */
    private function autorizarInstructor(Request $request, object $programacion): void
    {
        $cuenta = $request->user();

        if ($cuenta->tieneTipo('Instructor') && (int) $cuenta->personal_id !== (int) $programacion->personal_id) {
            abort(403, 'Solo puedes gestionar tus propias actividades.');
        }
    }

    private function datosReporte(Request $request): array
    {
        $desde = ReservaController::fechaValida($request->input('desde')) ?? now()->startOfMonth();
        $hasta = (ReservaController::fechaValida($request->input('hasta')) ?? now())->copy()->endOfDay();

        $filas = DB::table('programacion_actividad as pa')
            ->join('actividad as a', 'a.actividad_id', '=', 'pa.actividad_id')
            ->join('estado_programacion_actividad as ep', 'ep.estado_programacion_actividad_id', '=', 'pa.estado_programacion_actividad_id')
            ->leftJoin('registro_actividad as ra', 'ra.programacion_actividad_id', '=', 'pa.programacion_actividad_id')
            ->leftJoin('asistencia_actividad as aa', 'aa.programacion_actividad_id', '=', 'pa.programacion_actividad_id')
            ->whereBetween('pa.fecha_inicio', [$desde, $hasta])
            ->whereNotIn('ep.nombre_estado', ['Solicitada', 'Rechazada'])
            ->when($request->filled('actividad'), fn ($q) => $q->where('pa.actividad_id', $request->input('actividad')))
            ->groupBy('a.actividad_id', 'a.nombre_actividad')
            ->orderBy('a.nombre_actividad')
            ->select(
                'a.actividad_id',
                'a.nombre_actividad',
                DB::raw("COUNT(DISTINCT pa.programacion_actividad_id) FILTER (WHERE ep.nombre_estado <> 'Cancelada') AS sesiones"),
                DB::raw("COUNT(DISTINCT pa.programacion_actividad_id) FILTER (WHERE ep.nombre_estado = 'Cancelada') AS canceladas"),
                DB::raw('COUNT(DISTINCT ra.registro_actividad_id) AS inscripciones'),
                DB::raw('COUNT(DISTINCT aa.asistencia_actividad_id) AS asistencias'),
                DB::raw('COUNT(DISTINCT aa.usuario_id) AS personas')
            )
            ->get();

        return [$desde, $hasta, $filas];
    }
}
