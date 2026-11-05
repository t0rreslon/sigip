<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PrestamoController extends Controller
{
    public function index()
    {
        $buscar = request('buscar');
        $tipo = request('tipo');
        $categoria = request('categoria');
        // Consultar los tipos de usuario registrados.
        $tiposUsuario = DB::table('rol_usuario')
            ->select('nombre')
            ->orderBy('nombre')
            ->get();
        $estado = request('estado');
        $fecha = request('fecha');
        $periodo = request('periodo');

        // 1. Consultar todos los préstamos, priorizando los activos.
        $prestamos = DB::table('prestamo as p')
            ->leftJoin('usuario as u', 'u.usuario_id', '=', 'p.usuario_id')
            ->leftJoin('rol_usuario as ru', 'ru.rol_usuario_id', '=', 'u.rol_usuario_id')
            ->leftJoin('personal as per', 'per.personal_id', '=', 'p.personal_id')
            ->select(
                'p.prestamo_id',
                'p.usuario_id',
                'p.personal_id',
                'p.fecha_prestado',
                'p.fecha_devuelto',
                'p.activo',

                DB::raw("
            CASE
                WHEN p.usuario_id IS NOT NULL THEN CONCAT('Usuario: ', ru.nombre)
                WHEN p.personal_id IS NOT NULL THEN 'Instructor'
            END AS tipo_destinatario
        "),

                DB::raw("
            CASE
                WHEN p.usuario_id IS NOT NULL THEN u.nombre
                WHEN p.personal_id IS NOT NULL THEN per.nombre
            END AS nombre
        "),

                DB::raw("
            CASE
                WHEN p.usuario_id IS NOT NULL THEN u.apellido
                WHEN p.personal_id IS NOT NULL THEN per.apellido
            END AS apellido
        "),

                DB::raw("
            CASE
                WHEN p.usuario_id IS NOT NULL THEN u.documento
                WHEN p.personal_id IS NOT NULL THEN per.documento
            END AS documento_destinatario
        ")
            )

            ->when($buscar, function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {

                    // Buscar en los datos de usuarios.
                    $q->where('u.nombre', 'ILIKE', "%{$buscar}%")
                        ->orWhere('u.apellido', 'ILIKE', "%{$buscar}%")
                        ->orWhereRaw(
                            "CONCAT_WS(' ', u.nombre, u.apellido) ILIKE ?",
                            ["%{$buscar}%"]
                        )
                        ->orWhere('u.documento', 'ILIKE', "%{$buscar}%")

                        // Buscar en los datos del personal.
                        ->orWhere('per.nombre', 'ILIKE', "%{$buscar}%")
                        ->orWhere('per.apellido', 'ILIKE', "%{$buscar}%")
                        ->orWhereRaw(
                            "CONCAT_WS(' ', per.nombre, per.apellido) ILIKE ?",
                            ["%{$buscar}%"]
                        )
                        ->orWhere('per.documento', 'ILIKE', "%{$buscar}%")

                        // Buscar por tipo de usuario o rol.
                        ->orWhere('ru.nombre', 'ILIKE', "%{$buscar}%")

                        // Buscar por nombre del implemento prestado.
                        ->orWhereExists(function ($subquery) use ($buscar) {
                            $subquery->select(DB::raw(1))
                                ->from('prestamo_implemento as pi')
                                ->join(
                                    'implemento as i',
                                    'i.implemento_id',
                                    '=',
                                    'pi.implemento_id'
                                )
                                ->whereColumn('pi.prestamo_id', 'p.prestamo_id')
                                ->where('i.nombre_implemento', 'ILIKE', "%{$buscar}%");
                        });
                });
            })

            ->when($tipo, function ($query) use ($tipo) {
                if ($tipo === 'Instructor') {
                    $query->whereNotNull('p.personal_id');
                } else {
                    $query->whereNotNull('p.usuario_id')
                        ->where('ru.nombre', $tipo);
                }
            })

            ->when($categoria, function ($query) use ($categoria) {
                $query->whereExists(function ($subquery) use ($categoria) {
                    $subquery->select(DB::raw(1))
                        ->from('prestamo_implemento as pi')
                        ->join(
                            'implemento as i',
                            'i.implemento_id',
                            '=',
                            'pi.implemento_id'
                        )
                        ->whereColumn('pi.prestamo_id', 'p.prestamo_id')
                        ->where('i.categoria_implemento_id', $categoria);
                });
            })

            ->when($estado !== null && $estado !== '', function ($query) use ($estado) {
                $query->where('p.activo', $estado === 'activo');
            })

            // Filtrar por una fecha exacta.
            ->when($fecha, function ($query) use ($fecha) {
                $query->whereDate('p.fecha_prestado', $fecha);
            })

            // Filtrar por periodos rápidos, si no se eligió una fecha exacta.
            ->when(!$fecha && $periodo, function ($query) use ($periodo) {

                if ($periodo === 'hoy') {
                    $query->whereDate('p.fecha_prestado', today());
                }

                if ($periodo === 'ultima_semana') {
                    $query->whereBetween('p.fecha_prestado', [
                        now()->subDays(6)->startOfDay(),
                        now()->endOfDay()
                    ]);
                }

                if ($periodo === 'ultimo_mes') {
                    $query->whereBetween('p.fecha_prestado', [
                        now()->subMonth()->startOfDay(),
                        now()->endOfDay()
                    ]);
                }
            })

            // Primero activos y después finalizados.
            ->orderByRaw("CASE WHEN p.activo = TRUE THEN 0 ELSE 1 END")

            // Dentro de cada grupo, los más recientes primero.
            ->orderByDesc('p.fecha_prestado')

            ->paginate(10);

        // 2. Consultar los implementos asociados a esos préstamos.
        $idsPrestamos = $prestamos->pluck('prestamo_id');

        $detalles = DB::table('prestamo_implemento as pi')
            ->join('implemento as i', 'i.implemento_id', '=', 'pi.implemento_id')
            ->leftJoin(
                'categoria_implemento as ci',
                'ci.categoria_implemento_id',
                '=',
                'i.categoria_implemento_id'
            )
            ->join(
                'estado_implemento as ei',
                'ei.estado_implemento_id',
                '=',
                'i.estado_implemento_id'
            )
            ->join(
                'estado_implemento_devuelto as eid',
                'eid.estado_implemento_devuelto_id',
                '=',
                'pi.estado_implemento_devuelto_id'
            )
            ->whereIn('pi.prestamo_id', $idsPrestamos)
            ->select(
                'pi.prestamo_id',
                'pi.prestamo_implemento_id',
                'i.implemento_id',
                'i.nombre_implemento',
                'ci.nombre_categoria',
                'ei.nombre_estado as estado_implemento',
                'eid.nombre_estado as estado_devolucion'
            )
            ->orderBy('i.nombre_implemento')
            ->get()
            ->groupBy('prestamo_id');

        // 3. Asociar los implementos a cada préstamo.
        $prestamos->getCollection()->transform(function ($prestamo) use ($detalles) {
            $prestamo->implementos = $detalles->get(
                $prestamo->prestamo_id,
                collect()
            );

            $prestamo->cantidad_implementos = $prestamo->implementos->count();

            return $prestamo;
        });

        // 4. Consultar las cantidades disponibles por categoría.
        $categorias = DB::table('categoria_implemento as ci')
            ->leftJoin('implemento as i', 'i.categoria_implemento_id', '=', 'ci.categoria_implemento_id')
            ->leftJoin('estado_implemento as ei', 'ei.estado_implemento_id', '=', 'i.estado_implemento_id')
            ->select(
                'ci.categoria_implemento_id',
                'ci.nombre_categoria',
                DB::raw("
            COUNT(i.implemento_id) FILTER (
                WHERE ei.nombre_estado = 'Ocupado'
            ) AS ocupados
        "),
                DB::raw("
            COUNT(i.implemento_id) FILTER (
                WHERE ei.nombre_estado = 'Disponible'
            ) AS disponibles
        "),
                DB::raw("
            COUNT(i.implemento_id) FILTER (
                WHERE ei.nombre_estado IN ('Disponible', 'Ocupado')
            ) AS total
        ")
            )
            ->groupBy('ci.categoria_implemento_id', 'ci.nombre_categoria')
            ->orderBy('ci.nombre_categoria')
            ->get();


        // 5. Consultar los implementos disponibles para registrar préstamos.
        $implementosDisponibles = DB::table('implemento as i')
            ->join(
                'estado_implemento as ei',
                'ei.estado_implemento_id',
                '=',
                'i.estado_implemento_id'
            )
            ->leftJoin(
                'categoria_implemento as ci',
                'ci.categoria_implemento_id',
                '=',
                'i.categoria_implemento_id'
            )
            ->where('ei.nombre_estado', 'Disponible')
            ->select(
                'i.implemento_id',
                'i.nombre_implemento',
                'i.categoria_implemento_id',
                'ci.nombre_categoria'
            )
            ->orderBy('ci.nombre_categoria')
            ->orderBy('i.nombre_implemento')
            ->get();

        // 6. Consultar el total de préstamos activos.
        $totalPrestamosActivos = DB::table('prestamo')
            ->where('activo', true)
            ->count();

        return view('prestamos', compact(
            'prestamos',
            'categorias',
            'totalPrestamosActivos',
            'implementosDisponibles',
            'tiposUsuario'
        ));
    }

    public function store(Request $request)
    {
        // 1. Validar los datos recibidos del formulario.
        $datos = $request->validate([
            'tipo_destinatario' => 'required|in:usuario,instructor',
            'documento' => 'required|string|max:12',
            'implementos' => 'required|array|min:1',
            'implementos.*' => 'required|integer|distinct|exists:implemento,implemento_id',
        ], [
            'tipo_destinatario.required' => 'Debes seleccionar el tipo de destinatario.',
            'tipo_destinatario.in' => 'El tipo de destinatario seleccionado no es válido.',
            'documento.required' => 'Debes ingresar el documento del destinatario.',
            'documento.max' => 'El documento no puede superar los 12 caracteres.',
            'implementos.required' => 'Debes seleccionar al menos un implemento.',
            'implementos.min' => 'Debes seleccionar al menos un implemento.',
            'implementos.*.exists' => 'Uno de los implementos seleccionados no existe.',
            'implementos.*.distinct' => 'No puedes seleccionar el mismo implemento varias veces.',
        ]);

        try {

            DB::transaction(function () use ($datos) {

                // 2. Identificar y validar al destinatario.
                if ($datos['tipo_destinatario'] === 'usuario') {

                    $destinatario = DB::table('usuario')
                        ->where('documento', $datos['documento'])
                        ->lockForUpdate()
                        ->first();

                    if (!$destinatario) {
                        throw ValidationException::withMessages([
                            'documento' => 'No existe un usuario registrado con ese documento.',
                        ]);
                    }

                    if (!$destinatario->estado_usuario) {
                        throw ValidationException::withMessages([
                            'documento' => 'El usuario se encuentra inactivo y no puede recibir préstamos.',
                        ]);
                    }

                    $usuarioId = $destinatario->usuario_id;
                    $personalId = null;

                    // 3. Comprobar si ya tiene un préstamo activo.
                    $tienePrestamoActivo = DB::table('prestamo')
                        ->where('usuario_id', $usuarioId)
                        ->where('activo', true)
                        ->exists();
                } else {

                    $destinatario = DB::table('personal as p')
                        ->join(
                            'estado_personal as ep',
                            'ep.estado_personal_id',
                            '=',
                            'p.estado_personal_id'
                        )
                        ->where('p.documento', $datos['documento'])
                        ->select(
                            'p.personal_id',
                            'ep.nombre_estado'
                        )
                        ->lockForUpdate()
                        ->first();

                    if (!$destinatario) {
                        throw ValidationException::withMessages([
                            'documento' => 'No existe un instructor registrado con ese documento.',
                        ]);
                    }

                    if ($destinatario->nombre_estado !== 'Activo') {
                        throw ValidationException::withMessages([
                            'documento' => 'El instructor no se encuentra activo y no puede recibir préstamos.',
                        ]);
                    }

                    $usuarioId = null;
                    $personalId = $destinatario->personal_id;

                    // 3. Comprobar si ya tiene un préstamo activo.
                    $tienePrestamoActivo = DB::table('prestamo')
                        ->where('personal_id', $personalId)
                        ->where('activo', true)
                        ->exists();
                }

                if ($tienePrestamoActivo) {
                    throw ValidationException::withMessages([
                        'documento' => 'Esta persona ya tiene un préstamo activo. Si necesita más implementos, debes agregarlos a su préstamo existente.',
                    ]);
                }

                // 4. Consultar los estados necesarios.
                $estadoDisponible = DB::table('estado_implemento')
                    ->where('nombre_estado', 'Disponible')
                    ->value('estado_implemento_id');

                $estadoOcupado = DB::table('estado_implemento')
                    ->where('nombre_estado', 'Ocupado')
                    ->value('estado_implemento_id');

                $estadoPendiente = DB::table('estado_implemento_devuelto')
                    ->where('nombre_estado', 'Pendiente')
                    ->value('estado_implemento_devuelto_id');

                if (
                    $estadoDisponible === null ||
                    $estadoOcupado === null ||
                    $estadoPendiente === null
                ) {
                    throw new \RuntimeException(
                        'No se encontraron todos los estados necesarios para registrar el préstamo.'
                    );
                }

                // 5. Verificar que todos los implementos sigan disponibles.
                $implementos = DB::table('implemento')
                    ->whereIn('implemento_id', $datos['implementos'])
                    ->where('estado_implemento_id', $estadoDisponible)
                    ->lockForUpdate()
                    ->get();

                if ($implementos->count() !== count($datos['implementos'])) {
                    throw ValidationException::withMessages([
                        'implementos' => 'Uno o varios implementos ya no están disponibles. Actualiza la lista e inténtalo nuevamente.',
                    ]);
                }

                // 6. Registrar el préstamo.
                $prestamoId = DB::table('prestamo')->insertGetId([
                    'usuario_id' => $usuarioId,
                    'personal_id' => $personalId,
                    'activo' => true,
                ], 'prestamo_id');

                // 7. Registrar cada implemento asociado al préstamo.
                foreach ($implementos as $implemento) {

                    DB::table('prestamo_implemento')->insert([
                        'implemento_id' => $implemento->implemento_id,
                        'prestamo_id' => $prestamoId,
                        'estado_implemento_devuelto_id' => $estadoPendiente,
                    ]);
                }

                // 8. Cambiar el estado de los implementos a Ocupado.
                DB::table('implemento')
                    ->whereIn('implemento_id', $datos['implementos'])
                    ->update([
                        'estado_implemento_id' => $estadoOcupado,
                    ]);
            });

            return redirect()
                ->route('prestamos')
                ->with('success', 'El préstamo se registró correctamente.');
        } catch (ValidationException $e) {

            throw $e;
        } catch (\Throwable $e) {

            report($e);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'No fue posible registrar el préstamo. Verifica la información e inténtalo nuevamente.');
        }
    }

    public function devolver(Request $request, $prestamoId)
    {
        // 1. Validar los estados de devolución recibidos.
        $datos = $request->validate([
            'devoluciones' => ['required', 'array', 'min:1'],
            'devoluciones.*' => [
                'required',
                'string',
                'in:Intacto,Averiado,Perdido'
            ],
        ], [
            'devoluciones.required' => 'Debes seleccionar al menos un implemento para devolver.',
            'devoluciones.min' => 'Debes seleccionar al menos un implemento para devolver.',
            'devoluciones.*.in' => 'El estado de devolución seleccionado no es válido.',
        ]);

        try {

            DB::transaction(function () use ($datos, $prestamoId) {

                // 2. Verificar y bloquear el préstamo.
                $prestamo = DB::table('prestamo')
                    ->where('prestamo_id', $prestamoId)
                    ->lockForUpdate()
                    ->first();

                if (!$prestamo || !$prestamo->activo) {
                    throw ValidationException::withMessages([
                        'devoluciones' => 'El préstamo no existe o ya se encuentra finalizado.',
                    ]);
                }

                // 3. Consultar y bloquear los detalles seleccionados.
                $idsDetalles = array_keys($datos['devoluciones']);

                $detalles = DB::table('prestamo_implemento as pi')
                    ->join(
                        'estado_implemento_devuelto as eid',
                        'eid.estado_implemento_devuelto_id',
                        '=',
                        'pi.estado_implemento_devuelto_id'
                    )
                    ->where('pi.prestamo_id', $prestamoId)
                    ->whereIn('pi.prestamo_implemento_id', $idsDetalles)
                    ->select(
                        'pi.prestamo_implemento_id',
                        'pi.implemento_id',
                        'eid.nombre_estado'
                    )
                    ->lockForUpdate()
                    ->get();

                if ($detalles->count() !== count($idsDetalles)) {
                    throw ValidationException::withMessages([
                        'devoluciones' => 'Uno o varios implementos no pertenecen a este préstamo.',
                    ]);
                }

                // 4. Comprobar que los implementos sigan pendientes.
                foreach ($detalles as $detalle) {
                    if ($detalle->nombre_estado !== 'Pendiente') {
                        throw ValidationException::withMessages([
                            'devoluciones' => 'Uno o varios implementos ya tienen registrada su devolución.',
                        ]);
                    }
                }

                // 5. Consultar los identificadores de los estados.
                $estadosDevolucion = DB::table('estado_implemento_devuelto')
                    ->whereIn('nombre_estado', ['Intacto', 'Averiado', 'Perdido'])
                    ->pluck('estado_implemento_devuelto_id', 'nombre_estado');

                $estadosImplemento = DB::table('estado_implemento')
                    ->whereIn('nombre_estado', ['Disponible', 'Averiado', 'Perdido'])
                    ->pluck('estado_implemento_id', 'nombre_estado');

                // 6. Actualizar cada devolución y el estado del implemento.
                foreach ($detalles as $detalle) {

                    $estadoDevolucion = $datos['devoluciones'][$detalle->prestamo_implemento_id];

                    $estadoImplemento = match ($estadoDevolucion) {
                        'Intacto' => 'Disponible',
                        'Averiado' => 'Averiado',
                        'Perdido' => 'Perdido',
                    };

                    if (
                        !isset($estadosDevolucion[$estadoDevolucion]) ||
                        !isset($estadosImplemento[$estadoImplemento])
                    ) {
                        throw new \RuntimeException(
                            'No se encontraron los estados necesarios para procesar la devolución.'
                        );
                    }

                    DB::table('prestamo_implemento')
                        ->where(
                            'prestamo_implemento_id',
                            $detalle->prestamo_implemento_id
                        )
                        ->update([
                            'estado_implemento_devuelto_id' =>
                            $estadosDevolucion[$estadoDevolucion],
                        ]);

                    DB::table('implemento')
                        ->where('implemento_id', $detalle->implemento_id)
                        ->update([
                            'estado_implemento_id' =>
                            $estadosImplemento[$estadoImplemento],
                        ]);
                }

                // 7. Comprobar si todavía quedan implementos pendientes.
                $quedanPendientes = DB::table('prestamo_implemento as pi')
                    ->join(
                        'estado_implemento_devuelto as eid',
                        'eid.estado_implemento_devuelto_id',
                        '=',
                        'pi.estado_implemento_devuelto_id'
                    )
                    ->where('pi.prestamo_id', $prestamoId)
                    ->where('eid.nombre_estado', 'Pendiente')
                    ->exists();

                // 8. Cerrar el préstamo solamente si ya no hay pendientes.
                if (!$quedanPendientes) {
                    DB::table('prestamo')
                        ->where('prestamo_id', $prestamoId)
                        ->update([
                            'activo' => false,
                            'fecha_devuelto' => now(),
                        ]);
                }
            });

            return redirect()
                ->route('prestamos')
                ->with('success', 'La devolución se registró correctamente.');
        } catch (ValidationException $e) {

            throw $e;
        } catch (\Throwable $e) {

            report($e);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'No fue posible registrar la devolución. Inténtalo nuevamente.');
        }
    }
}
