<?php

namespace App\Http\Controllers;

use App\Models\Espacio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Support\Auditoria;
use App\Support\Imagenes;
use Illuminate\Support\Carbon;

class EspacioController extends Controller
{
    // HU-46: visualizar los espacios por filtros.
    public function index(Request $request)
    {
        $proximasReservas = DB::table('reserva as r')
            ->join('estado_reserva as er', 'er.estado_reserva_id', '=', 'r.estado_reserva_id')
            ->whereIn('er.nombre_estado', Espacio::ESTADOS_RESERVA_ACTIVOS)
            ->where('r.fecha_fin', '>', now())
            ->select('r.espacio_id', DB::raw('COUNT(*) AS total'), DB::raw('MIN(r.fecha_inicio) AS proxima'))
            ->groupBy('r.espacio_id');

        $consulta = DB::table('espacio as e')
            ->join('estado_espacio as ee', 'ee.estado_espacio_id', '=', 'e.estado_espacio_id')
            ->leftJoinSub($proximasReservas, 'pr', 'pr.espacio_id', '=', 'e.espacio_id')
            ->select(
                'e.*',
                'ee.nombre_estado',
                DB::raw('COALESCE(pr.total, 0) AS reservas_activas'),
                'pr.proxima'
            );

        if ($request->filled('buscar')) {
            $buscar = trim($request->input('buscar'));

            $consulta->where(function ($q) use ($buscar) {
                $q->where('e.nombre_espacio', 'ILIKE', \App\Support\Busqueda::contiene($buscar))
                    ->orWhere('e.descripcion', 'ILIKE', \App\Support\Busqueda::contiene($buscar));
            });
        }

        if ($request->filled('estado')) {
            $consulta->where('e.estado_espacio_id', $request->input('estado'));
        }

        if ($request->filled('capacidad_min') && ctype_digit((string) $request->input('capacidad_min'))) {
            $consulta->where('e.capacidad', '>=', (int) $request->input('capacidad_min'));
        }

        $espacios = $consulta
            ->orderBy('e.nombre_espacio')
            ->paginate(8)
            ->withQueryString();

        $estados = DB::table('estado_espacio')->orderBy('estado_espacio_id')->get();

        $conteos = DB::table('espacio')
            ->select('estado_espacio_id', DB::raw('COUNT(*) AS total'))
            ->groupBy('estado_espacio_id')
            ->pluck('total', 'estado_espacio_id');

        return view('espacios', compact('espacios', 'estados', 'conteos'));
    }

    // HU-45: registrar espacios.
    public function store(Request $request)
    {
        $request->merge(['nombre_espacio' => trim((string) $request->input('nombre_espacio'))]);

        $estadosPermitidos = DB::table('estado_espacio')
            ->where('nombre_estado', '!=', 'Reservado')
            ->pluck('estado_espacio_id')
            ->all();

        $datos = $request->validateWithBag('registrar', [
            'nombre_espacio' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) {
                    $existe = DB::table('espacio')
                        ->whereRaw('LOWER(BTRIM(nombre_espacio)) = ?', [mb_strtolower($value)])
                        ->exists();

                    if ($existe) {
                        $fail('Ya existe un espacio con este nombre.');
                    }
                },
            ],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'capacidad' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'estado_espacio_id' => ['required', 'integer', Rule::in($estadosPermitidos)],
            'imagen' => Imagenes::REGLAS,
        ], [
            'estado_espacio_id.in' => 'El estado "Reservado" se asigna automáticamente con las reservas.',
        ]);

        unset($datos['imagen']);

        if ($request->hasFile('imagen')) {
            $datos['imagen'] = Imagenes::guardar($request->file('imagen'), 'espacios');
        }

        $espacio = Espacio::create($datos);
        Auditoria::cambio('espacio', 'INSERT', $espacio->espacio_id, null, $datos);

        return redirect()
            ->route('espacios.index')
            ->with('success', 'El espacio se registró correctamente.');
    }

    // HU-47: habilitar o deshabilitar un espacio.
    // Foto del espacio (se muestra en su tarjeta).
    public function cambiarImagen(Request $request, $id)
    {
        $espacio = Espacio::findOrFail($id);

        $request->validateWithBag('imagen', [
            'imagen' => array_merge(['required'], array_slice(Imagenes::REGLAS, 1)),
        ]);

        Imagenes::borrar($espacio->imagen, 'espacios');
        $antes = ['foto' => $espacio->imagen ? 'Sí' : 'No'];
        $espacio->imagen = Imagenes::guardar($request->file('imagen'), 'espacios');
        $espacio->save();

        Auditoria::cambio('espacio', 'UPDATE', $espacio->espacio_id, $antes, ['foto' => 'Actualizada']);

        return back()->with('success', "La foto de {$espacio->nombre_espacio} se actualizó.");
    }

    public function cambiarEstado(Request $request, $id)
    {
        $datos = $request->validate([
            'accion' => ['required', 'in:habilitar,deshabilitar'],
            'motivo' => ['required_if:accion,deshabilitar', 'nullable', Rule::in(Espacio::ESTADOS_DESHABILITADOS)],
        ], [
            'motivo.required_if' => 'Selecciona si el espacio queda en mantenimiento o fuera de servicio.',
        ]);

        DB::transaction(function () use ($datos, $id) {
            $espacio = Espacio::where('espacio_id', $id)->lockForUpdate()->firstOrFail();

            if ($datos['accion'] === 'deshabilitar') {
                $reservasPendientes = DB::table('reserva as r')
                    ->join('estado_reserva as er', 'er.estado_reserva_id', '=', 'r.estado_reserva_id')
                    ->where('r.espacio_id', $espacio->espacio_id)
                    ->whereIn('er.nombre_estado', Espacio::ESTADOS_RESERVA_ACTIVOS)
                    ->where('r.fecha_fin', '>', now())
                    ->count();

                if ($reservasPendientes > 0) {
                    throw ValidationException::withMessages([
                        'accion' => "El espacio tiene {$reservasPendientes} reserva(s) activa(s). Cancélalas antes de deshabilitarlo.",
                    ]);
                }

                $espacio->estado_espacio_id = Espacio::idEstado($datos['motivo']);
                $espacio->save();

                return;
            }

            // Al habilitarlo queda Disponible o Reservado según sus reservas.
            $espacio->estado_espacio_id = Espacio::idEstado('Disponible');
            $espacio->save();

            Espacio::actualizarEstadoSegunReservas($espacio->espacio_id);
        });

        return redirect()
            ->route('espacios.index', $request->only(['buscar', 'estado', 'capacidad_min', 'page']))
            ->with('success', $datos['accion'] === 'habilitar'
                ? 'El espacio quedó habilitado.'
                : 'El espacio quedó deshabilitado (' . mb_strtolower($datos['motivo']) . ').');
    }
    // HU-167: información completa de un espacio.
    public function show($id)
    {
        $espacio = DB::table('espacio as e')
            ->join('estado_espacio as ee', 'ee.estado_espacio_id', '=', 'e.estado_espacio_id')
            ->where('e.espacio_id', $id)
            ->select('e.*', 'ee.nombre_estado')
            ->first();
        abort_if(!$espacio, 404);

        $reservas = DB::table('reserva as r')
            ->join('estado_reserva as er', 'er.estado_reserva_id', '=', 'r.estado_reserva_id')
            ->leftJoin('usuario as u', 'u.usuario_id', '=', 'r.usuario_id')
            ->leftJoin('personal as p', 'p.personal_id', '=', 'r.personal_id')
            ->where('r.espacio_id', $id)
            ->where('r.fecha_fin', '>', now())
            ->whereIn('er.nombre_estado', Espacio::ESTADOS_RESERVA_ACTIVOS)
            ->orderBy('r.fecha_inicio')
            ->limit(20)
            ->select('r.*', 'er.nombre_estado', DB::raw("COALESCE(NULLIF(CONCAT_WS(' ', u.nombre, u.apellido), ''), CONCAT_WS(' ', p.nombre, p.apellido)) AS destinatario"))
            ->get();

        $actividades = DB::table('programacion_actividad as pa')
            ->join('actividad as a', 'a.actividad_id', '=', 'pa.actividad_id')
            ->join('personal as p', 'p.personal_id', '=', 'pa.personal_id')
            ->join('estado_programacion_actividad as ep', 'ep.estado_programacion_actividad_id', '=', 'pa.estado_programacion_actividad_id')
            ->where('pa.espacio_id', $id)
            ->where('pa.fecha_fin', '>', now())
            ->whereNotIn('ep.nombre_estado', ['Cancelada', 'Rechazada', 'Finalizada'])
            ->orderBy('pa.fecha_inicio')
            ->limit(20)
            ->select('pa.*', 'a.nombre_actividad', 'ep.nombre_estado', DB::raw("CONCAT_WS(' ', p.nombre, p.apellido) AS instructor"))
            ->get();

        $maquinas = DB::table('maquina as m')
            ->join('estado_maquina as em', 'em.estado_maquina_id', '=', 'm.estado_maquina_id')
            ->where('m.espacio_id', $id)
            ->orderBy('m.nombre_maquina')
            ->select('m.maquina_id', 'm.nombre_maquina', 'em.nombre_estado')
            ->get();

        $mantenimientos = DB::table('mantenimiento_espacio as me')
            ->join('estado_maquina_mantenimiento as e', 'e.estado_maquina_mantenimiento_id', '=', 'me.estado_maquina_mantenimiento_id')
            ->where('me.espacio_id', $id)
            ->orderByDesc('me.fecha_inicio')
            ->select('me.*', 'e.nombre_estado')
            ->get();

        return view('espacio-detalle', [
            'espacio' => $espacio,
            'reservas' => $reservas,
            'actividades' => $actividades,
            'maquinas' => $maquinas,
            'mantenimientos' => $mantenimientos,
            'estadosMantenimiento' => DB::table('estado_maquina_mantenimiento')->orderBy('estado_maquina_mantenimiento_id')->get(),
            'historial' => Auditoria::historial('espacio', $id),
        ]);
    }

    // HU-168: registrar el mantenimiento de un espacio.
    public function registrarMantenimiento(Request $request, $id)
    {
        $datos = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'detalles' => ['required', 'string', 'max:2000'],
            'estado_maquina_mantenimiento_id' => ['required', 'integer', 'exists:estado_maquina_mantenimiento,estado_maquina_mantenimiento_id'],
            'bloquear' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($datos, $id) {
            $espacio = Espacio::where('espacio_id', $id)->lockForUpdate()->firstOrFail();

            if (!empty($datos['bloquear'])) {
                $inicio = Carbon::parse($datos['fecha_inicio'])->startOfDay();
                $fin = Carbon::parse($datos['fecha_fin'] ?? $datos['fecha_inicio'])->endOfDay();

                $reservasEnPeriodo = DB::table('reserva as r')
                    ->join('estado_reserva as er', 'er.estado_reserva_id', '=', 'r.estado_reserva_id')
                    ->where('r.espacio_id', $id)
                    ->whereIn('er.nombre_estado', Espacio::ESTADOS_RESERVA_ACTIVOS)
                    ->where('r.fecha_inicio', '<', $fin)
                    ->where('r.fecha_fin', '>', $inicio)
                    ->count();

                if ($reservasEnPeriodo > 0) {
                    throw ValidationException::withMessages([
                        'fecha_inicio' => "Hay {$reservasEnPeriodo} reserva(s) activa(s) en ese periodo. Cancélalas o elige otras fechas.",
                    ]);
                }

                $estadoAnterior = $espacio->estado_espacio_id;
                $espacio->estado_espacio_id = Espacio::idEstado('Mantenimiento');
                $espacio->save();

                Auditoria::cambio('espacio', 'ESTADO', $id, ['estado_espacio_id' => $estadoAnterior], ['estado_espacio_id' => $espacio->estado_espacio_id]);
            }

            DB::table('mantenimiento_espacio')->insert([
                'espacio_id' => $id,
                'fecha_inicio' => $datos['fecha_inicio'],
                'fecha_fin' => $datos['fecha_fin'] ?? null,
                'detalles' => $datos['detalles'],
                'estado_maquina_mantenimiento_id' => $datos['estado_maquina_mantenimiento_id'],
                'registrado_por' => auth()->user()?->login,
            ]);
        });

        return back()->with('success', 'El mantenimiento del espacio quedó registrado.');
    }

    // Finalizar un mantenimiento: el espacio vuelve a estar disponible (o reservado).
    public function finalizarMantenimiento($id, $mantenimientoId)
    {
        DB::transaction(function () use ($id, $mantenimientoId) {
            $actualizados = DB::table('mantenimiento_espacio')
                ->where('espacio_id', $id)
                ->where('mantenimiento_espacio_id', $mantenimientoId)
                ->update([
                    'estado_maquina_mantenimiento_id' => DB::table('estado_maquina_mantenimiento')->where('nombre_estado', 'Finalizado')->value('estado_maquina_mantenimiento_id'),
                    'fecha_fin' => DB::raw('GREATEST(fecha_inicio, CURRENT_DATE)'),
                ]);

            abort_if($actualizados === 0, 404);

            $pendientes = DB::table('mantenimiento_espacio as me')
                ->join('estado_maquina_mantenimiento as e', 'e.estado_maquina_mantenimiento_id', '=', 'me.estado_maquina_mantenimiento_id')
                ->where('me.espacio_id', $id)
                ->where('e.nombre_estado', '!=', 'Finalizado')
                ->exists();

            $espacio = Espacio::where('espacio_id', $id)->lockForUpdate()->first();

            if (!$pendientes && $espacio && (int) $espacio->estado_espacio_id === Espacio::idEstado('Mantenimiento')) {
                $espacio->estado_espacio_id = Espacio::idEstado('Disponible');
                $espacio->save();
                Espacio::actualizarEstadoSegunReservas((int) $id);
            }
        });

        return back()->with('success', 'El mantenimiento se marcó como finalizado.');
    }
}
