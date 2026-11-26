<?php

namespace App\Http\Controllers;

use App\Support\Auditoria;
use App\Support\PdfReporte;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Instructores: personal del gimnasio que no tiene cuenta de Administrador u Operador.
 */
class InstructorController extends Controller
{
    // HU-32 / HU-33: visualizar y buscar instructores.
    public function index(Request $request)
    {
        $instructores = $this->consulta($request)->paginate(12)->withQueryString();

        return view('instructores.index', [
            'instructores' => $instructores,
            'estados' => DB::table('estado_personal')->orderBy('estado_personal_id')->get(),
            'tiposDocumento' => DB::table('tipo_documento')->orderBy('nombre_tipo_documento')->get(),
            'generos' => DB::table('genero')->orderBy('nombre_genero')->get(),
        ]);
    }

    // HU-31: registrar instructores.
    public function store(Request $request)
    {
        $datos = $request->validate(self::reglasPersonal());
        $datos['estado_personal_id'] = $this->idEstado('Activo');
        $datos['fecha_inicio'] = $datos['fecha_inicio'] ?? today()->format('Y-m-d');

        $id = DB::table('personal')->insertGetId($datos, 'personal_id');
        Auditoria::cambio('personal', 'INSERT', $id, null, $datos);

        return redirect()->route('instructores.index')->with('success', 'El instructor se registró correctamente.');
    }

    // HU-145: actualizar los datos de un instructor.
    public function update(Request $request, $id)
    {
        $instructor = $this->instructor($id);
        $datos = $request->validate(self::reglasPersonal($instructor->personal_id));

        DB::table('personal')->where('personal_id', $instructor->personal_id)->update($datos);
        Auditoria::cambio('personal', 'UPDATE', $instructor->personal_id, (array) $instructor, $datos);

        return redirect()->route('instructores.index', $request->only(['buscar', 'estado', 'page']))
            ->with('success', 'Los datos del instructor se actualizaron.');
    }

    // HU-146: cambiar el estado (Activo, Vacaciones, Retirado).
    public function cambiarEstado(Request $request, $id)
    {
        $instructor = $this->instructor($id);
        $datos = $request->validate([
            'estado_personal_id' => ['required', 'integer', 'exists:estado_personal,estado_personal_id'],
        ]);

        DB::transaction(function () use ($instructor, $datos) {
            DB::table('personal')->where('personal_id', $instructor->personal_id)->update($datos);

            $nombreEstado = DB::table('estado_personal')->where('estado_personal_id', $datos['estado_personal_id'])->value('nombre_estado');

            // Un instructor que no está activo no puede ingresar al sistema.
            DB::table('cuenta')->where('personal_id', $instructor->personal_id)->update(['estado_cuenta' => $nombreEstado === 'Activo']);

            Auditoria::cambio('personal', 'ESTADO', $instructor->personal_id,
                ['estado' => $instructor->nombre_estado], ['estado' => $nombreEstado]);
        });

        return back()->with('success', 'El estado del instructor se actualizó.');
    }

    // HU-147: crear la cuenta con contraseña para que el instructor ingrese.
    public function crearCuenta(Request $request, $id)
    {
        $instructor = $this->instructor($id);

        if ($instructor->cuenta_id) {
            throw ValidationException::withMessages(['login' => 'Este instructor ya tiene una cuenta.']);
        }

        $datos = $request->validate([
            'login' => ['required', 'string', 'min:4', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:cuenta,login'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'login.regex' => 'El usuario solo puede tener letras, números, punto, guion y guion bajo.',
        ]);

        $cuentaId = DB::table('cuenta')->insertGetId([
            'personal_id' => $instructor->personal_id,
            'login' => $datos['login'],
            'clave' => Hash::make($datos['password']),
            'tipo_cuenta_id' => DB::table('tipo_cuenta')->where('nombre_cuenta', 'Instructor')->value('tipo_cuenta_id'),
            'estado_cuenta' => $instructor->nombre_estado === 'Activo',
        ], 'cuenta_id');

        Auditoria::cambio('cuenta', 'INSERT', $cuentaId, null, ['login' => $datos['login'], 'tipo' => 'Instructor']);

        return back()->with('success', "Se creó la cuenta \"{$datos['login']}\". El instructor ya puede iniciar sesión.");
    }

    // HU-194: historial de cambios de un instructor.
    public function historial($id)
    {
        $instructor = $this->instructor($id);

        return view('historial', [
            'titulo' => 'Historial del instructor',
            'subtitulo' => "{$instructor->nombre} {$instructor->apellido} · {$instructor->documento}",
            'volver' => route('instructores.index'),
            'registros' => Auditoria::historial('personal', $instructor->personal_id),
        ]);
    }

    // HU-196: exportar los instructores a PDF.
    public function exportar(Request $request)
    {
        $instructores = $this->consulta($request)->get();

        Auditoria::movimiento('Exportar', ['reporte' => 'instructores', 'filtros' => $request->query()]);

        $pdf = PdfReporte::tabla('Instructores', [
            ['titulo' => 'Documento', 'ancho' => 85],
            ['titulo' => 'Nombre', 'ancho' => 180],
            ['titulo' => 'Correo', 'ancho' => 180],
            ['titulo' => 'Teléfono', 'ancho' => 80],
            ['titulo' => 'Desde', 'ancho' => 75],
            ['titulo' => 'Estado', 'ancho' => 80],
            ['titulo' => 'Cuenta', 'ancho' => 102],
        ], $instructores->map(fn ($i) => [
            $i->documento,
            "{$i->nombre} {$i->apellido}",
            $i->correo ?? '',
            $i->telefono ?? '',
            Carbon::parse($i->fecha_inicio)->format('d/m/Y'),
            $i->nombre_estado,
            $i->login ?? 'Sin cuenta',
        ]), ['Total de instructores: ' . $instructores->count()]);

        return PdfReporte::descargar($pdf, 'instructores-' . now()->format('Ymd-His') . '.pdf');
    }

    // HU-150 / HU-151: actividades, horarios y turnos asignados a un instructor.
    public function horario(Request $request, $id)
    {
        $instructor = $this->instructor($id);

        return view('instructores.horario', $this->datosHorario($instructor, $request) + [
            'instructor' => $instructor,
            'turnos' => DB::table('turno')->where('estado_turno', true)->orderBy('hora_inicio')->get(),
            'puedeAsignar' => true,
        ]);
    }

    // HU-151: asignar un turno (horario) al instructor.
    public function asignarTurno(Request $request, $id)
    {
        $instructor = $this->instructor($id);

        $datos = $request->validate([
            'turno_id' => ['required', 'integer', Rule::exists('turno', 'turno_id')->where('estado_turno', true)],
            'fecha_desde' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'fecha_hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:fecha_desde'],
            'dias' => ['required', 'array', 'min:1'],
            'dias.*' => ['integer', 'between:1,7'],
        ], [
            'dias.required' => 'Selecciona al menos un día de la semana.',
        ]);

        if ($instructor->nombre_estado !== 'Activo') {
            throw ValidationException::withMessages(['turno_id' => 'Solo se pueden asignar turnos a instructores activos.']);
        }

        $desde = Carbon::parse($datos['fecha_desde']);
        $hasta = Carbon::parse($datos['fecha_hasta'] ?? $datos['fecha_desde']);

        if ($desde->diffInDays($hasta) > 120) {
            throw ValidationException::withMessages(['fecha_hasta' => 'El rango de fechas no puede superar 120 días.']);
        }

        $vigente = DB::table('estado_turno_asignado')->where('nombre_estado', 'Vigente')->value('estado_turno_asignado_id');
        $creados = 0;

        DB::transaction(function () use ($instructor, $datos, $desde, $hasta, $vigente, &$creados) {
            for ($fecha = $desde->copy(); $fecha->lte($hasta); $fecha->addDay()) {
                if (!in_array($fecha->dayOfWeekIso, array_map('intval', $datos['dias']), true)) {
                    continue;
                }

                $existe = DB::table('turno_asignado')
                    ->where('personal_id', $instructor->personal_id)
                    ->where('turno_id', $datos['turno_id'])
                    ->whereDate('fecha_turno', $fecha->format('Y-m-d'))
                    ->where('estado_turno_asignado_id', $vigente)
                    ->exists();

                if (!$existe) {
                    DB::table('turno_asignado')->insert([
                        'personal_id' => $instructor->personal_id,
                        'turno_id' => $datos['turno_id'],
                        'fecha_turno' => $fecha->copy()->startOfDay(),
                        'estado_turno_asignado_id' => $vigente,
                    ]);
                    $creados++;
                }
            }
        });

        return back()->with('success', $creados > 0
            ? "Se asignaron {$creados} turno(s) al instructor."
            : 'El instructor ya tenía asignados esos turnos.');
    }

    // Cancelar un turno asignado.
    public function cancelarTurno($id, $turnoAsignadoId)
    {
        $instructor = $this->instructor($id);

        DB::table('turno_asignado')
            ->where('turno_asignado_id', $turnoAsignadoId)
            ->where('personal_id', $instructor->personal_id)
            ->update([
                'estado_turno_asignado_id' => DB::table('estado_turno_asignado')->where('nombre_estado', 'Cancelado')->value('estado_turno_asignado_id'),
            ]);

        return back()->with('success', 'El turno se canceló.');
    }

    /**
     * Turnos y actividades programadas de un instructor en un rango de fechas.
     * También lo usa el portal del instructor (HU-149).
     */
    public static function datosHorario(object $instructor, Request $request): array
    {
        try {
            $desde = $request->filled('desde') ? Carbon::createFromFormat('Y-m-d', $request->input('desde'))->startOfDay() : today()->startOfWeek();
        } catch (\Throwable) {
            $desde = today()->startOfWeek();
        }

        $hasta = $desde->copy()->addDays(13)->endOfDay();

        $turnosAsignados = DB::table('turno_asignado as ta')
            ->join('turno as t', 't.turno_id', '=', 'ta.turno_id')
            ->join('estado_turno_asignado as e', 'e.estado_turno_asignado_id', '=', 'ta.estado_turno_asignado_id')
            ->where('ta.personal_id', $instructor->personal_id)
            ->whereBetween('ta.fecha_turno', [$desde, $hasta])
            ->orderBy('ta.fecha_turno')
            ->orderBy('t.hora_inicio')
            ->select('ta.turno_asignado_id', 'ta.fecha_turno', 't.tipo_jornada', 't.hora_inicio', 't.hora_fin', 'e.nombre_estado')
            ->get();

        $actividades = DB::table('programacion_actividad as pa')
            ->join('actividad as a', 'a.actividad_id', '=', 'pa.actividad_id')
            ->join('espacio as e', 'e.espacio_id', '=', 'pa.espacio_id')
            ->join('estado_programacion_actividad as ep', 'ep.estado_programacion_actividad_id', '=', 'pa.estado_programacion_actividad_id')
            ->where('pa.personal_id', $instructor->personal_id)
            ->whereBetween('pa.fecha_inicio', [$desde, $hasta])
            ->whereNotIn('ep.nombre_estado', ['Cancelada', 'Rechazada'])
            ->orderBy('pa.fecha_inicio')
            ->select('pa.programacion_actividad_id', 'pa.fecha_inicio', 'pa.fecha_fin', 'a.nombre_actividad', 'e.nombre_espacio', 'ep.nombre_estado')
            ->get();

        return compact('desde', 'hasta', 'turnosAsignados', 'actividades');
    }

    public static function reglasPersonal(?int $personalId = null): array
    {
        return [
            'tipo_documento_id' => ['required', 'integer', 'exists:tipo_documento,tipo_documento_id'],
            'documento' => [
                'required', 'string', 'regex:/^[0-9]{6,12}$/',
                Rule::unique('personal', 'documento')->ignore($personalId, 'personal_id'),
                function ($attribute, $value, $fail) {
                    if (DB::table('usuario')->whereRaw('BTRIM(documento) = ?', [trim($value)])->exists()) {
                        $fail('Este documento ya está registrado como usuario del gimnasio.');
                    }
                },
            ],
            'nombre' => ['required', 'string', 'max:50', 'regex:/^[\pL\s\'.-]+$/u'],
            'apellido' => ['required', 'string', 'max:50', 'regex:/^[\pL\s\'.-]+$/u'],
            'genero_id' => ['required', 'integer', 'exists:genero,genero_id'],
            'fecha_inicio' => ['nullable', 'date', 'before_or_equal:today'],
            'telefono' => ['nullable', 'regex:/^[0-9]{7,11}$/'],
            'correo' => ['nullable', 'email', 'max:80'],
        ];
    }

    private function consulta(Request $request)
    {
        $tiposPersonal = DB::table('tipo_cuenta')->whereIn('nombre_cuenta', ['Administrador', 'Operador'])->pluck('tipo_cuenta_id');

        return DB::table('personal as p')
            ->join('estado_personal as ep', 'ep.estado_personal_id', '=', 'p.estado_personal_id')
            ->leftJoin('cuenta as c', 'c.personal_id', '=', 'p.personal_id')
            ->where(fn ($q) => $q->whereNull('c.cuenta_id')->orWhereNotIn('c.tipo_cuenta_id', $tiposPersonal))
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $buscar = trim($request->input('buscar'));
                $q->where(fn ($w) => $w->where('p.documento', 'ILIKE', \App\Support\Busqueda::contiene($buscar))
                    ->orWhere('p.correo', 'ILIKE', \App\Support\Busqueda::contiene($buscar))
                    ->orWhereRaw("CONCAT_WS(' ', p.nombre, p.apellido) ILIKE ?", [\App\Support\Busqueda::contiene($buscar)]));
            })
            ->when($request->filled('estado'), fn ($q) => $q->where('p.estado_personal_id', $request->input('estado')))
            ->when($request->input('cuenta') === 'con', fn ($q) => $q->whereNotNull('c.cuenta_id'))
            ->when($request->input('cuenta') === 'sin', fn ($q) => $q->whereNull('c.cuenta_id'))
            ->select('p.*', 'ep.nombre_estado', 'c.cuenta_id', 'c.login', 'c.estado_cuenta')
            ->orderBy('p.apellido')
            ->orderBy('p.nombre');
    }

    private function instructor($id): object
    {
        $instructor = $this->consulta(new Request())->where('p.personal_id', $id)->first();

        abort_if(!$instructor, 404);

        return $instructor;
    }

    private function idEstado(string $nombre): int
    {
        return (int) DB::table('estado_personal')->where('nombre_estado', $nombre)->value('estado_personal_id');
    }
}
