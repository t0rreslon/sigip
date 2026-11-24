<?php

namespace App\Http\Controllers;

use App\Models\Espacio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
                $q->where('e.nombre_espacio', 'ILIKE', "%{$buscar}%")
                    ->orWhere('e.descripcion', 'ILIKE', "%{$buscar}%");
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
            ->paginate(10)
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
        ], [
            'estado_espacio_id.in' => 'El estado "Reservado" se asigna automáticamente con las reservas.',
        ]);

        Espacio::create($datos);

        return redirect()
            ->route('espacios.index')
            ->with('success', 'El espacio se registró correctamente.');
    }

    // HU-47: habilitar o deshabilitar un espacio.
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
}
