<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Support\Auditoria;
use App\Support\Imagenes;
use App\Support\PdfReporte;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InventarioController extends Controller
{
    public function index(Request $request)
    {
        [$tipo, $maquinasFiltradas, $implementosFiltrados] = $this->filtrar($request);

        // Totales generales (sin filtros) para el resumen, buscando los estados por nombre.
        $estadosImplementoIds = DB::table('estado_implemento')
            ->pluck('estado_implemento_id', 'nombre_estado');

        $conteoImplementos = DB::table('implemento')
            ->select('estado_implemento_id', DB::raw('COUNT(*) AS total'))
            ->groupBy('estado_implemento_id')
            ->pluck('total', 'estado_implemento_id');

        $contar = fn (string $nombre) => (int) ($conteoImplementos[$estadosImplementoIds[$nombre] ?? 0] ?? 0);

        $totalMaquinas = DB::table('maquina')->count();
        $totalImplementos = (int) $conteoImplementos->sum();
        $totalDisponibles = $contar('Disponible');
        $totalPrestados = $contar('Ocupado');
        $totalAveriados = $contar('Averiado');
        $totalPerdidos = $contar('Perdido');

        // Tarjetas por categoría (máquinas e implementos), con la cantidad y los estados de sus unidades.
        $hayFiltros = $request->hasAny(['buscar', 'categoria', 'espacio', 'estado']);
        $tarjetas = $this->tarjetasPorCategoria($tipo, $maquinasFiltradas, $implementosFiltrados, $hayFiltros);

        // Filtros sobre las tarjetas: cantidad de unidades (todas las pestañas) y stock mínimo (implementos).
        $cantidadMin = filter_var($request->input('cantidad_min'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $cantidadMax = filter_var($request->input('cantidad_max'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $stock = $tipo === 'implemento' ? $request->input('stock') : null;

        $tarjetas = $tarjetas
            ->when($cantidadMin !== false, fn ($c) => $c->filter(fn ($t) => $t->total >= $cantidadMin))
            ->when($cantidadMax !== false, fn ($c) => $c->filter(fn ($t) => $t->total <= $cantidadMax))
            ->when($stock === 'bajo', fn ($c) => $c->filter(fn ($t) => $t->bajo_stock))
            ->when($stock === 'suficiente', fn ($c) => $c->filter(fn ($t) => !$t->bajo_stock))
            ->values();

        $porPagina = 8;
        $paginaActual = LengthAwarePaginator::resolveCurrentPage();

        $paginacion = new LengthAwarePaginator(
            $tarjetas->slice(($paginaActual - 1) * $porPagina, $porPagina)->values(),
            $tarjetas->count(),
            $porPagina,
            $paginaActual,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
        // Datos para los selectores.
        $categoriasMaquina = DB::table('categoria_maquina')->orderBy('nombre_categoria')->get();
        $categoriasImplemento = DB::table('categoria_implemento')->orderBy('nombre_categoria')->get();
        $espacios = DB::table('espacio')->orderBy('nombre_espacio')->get();
        $estadosMaquina = DB::table('estado_maquina')->orderBy('estado_maquina_id')->get();
        $estadosImplemento = DB::table('estado_implemento')->orderBy('estado_implemento_id')->get();

        // "Ocupado" solo lo asigna un préstamo; no se ofrece al registrar.
        $estadosImplementoRegistro = $estadosImplemento
            ->where('nombre_estado', '!=', 'Ocupado')
            ->values();

        $nombresEstado = $estadosMaquina->pluck('nombre_estado')
            ->concat($estadosImplemento->pluck('nombre_estado'))
            ->unique()
            ->sort()
            ->values();

        $stockBajo = $this->categoriasBajoStock();

        return view('inventario', compact(
            'stockBajo',
            'paginacion',
            'tipo',
            'totalMaquinas',
            'totalImplementos',
            'totalPrestados',
            'totalDisponibles',
            'totalAveriados',
            'totalPerdidos',
            'categoriasMaquina',
            'categoriasImplemento',
            'espacios',
            'estadosMaquina',
            'estadosImplemento',
            'estadosImplementoRegistro',
            'nombresEstado'
        ));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'tipo' => 'required|in:maquina,implemento',
            'categoria_id' => 'required|integer',
            'estado_id' => 'required|integer',
            'espacio_id' => 'required_if:tipo,maquina|nullable|integer|exists:espacio,espacio_id',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        if ($datos['tipo'] === 'maquina') {
            $request->validate([
                'categoria_id' => 'exists:categoria_maquina,categoria_maquina_id',
                'estado_id' => 'exists:estado_maquina,estado_maquina_id',
            ]);

            DB::transaction(function () use ($datos) {
                // Bloquear la categoría para evitar nombres repetidos
                // cuando se registran dos máquinas simultáneamente.
                $categoria = DB::table('categoria_maquina')
                    ->where('categoria_maquina_id', $datos['categoria_id'])
                    ->lockForUpdate()
                    ->first();

                $nombres = DB::table('maquina')
                    ->where('categoria_maquina_id', $datos['categoria_id'])
                    ->pluck('nombre_maquina');

                $siguienteNumero = $this->obtenerSiguienteNumero($nombres);

                $nombre = $categoria->nombre_categoria . ' '
                    . str_pad($siguienteNumero, 2, '0', STR_PAD_LEFT);

                $fila = [
                    'nombre_maquina' => $nombre,
                    'descripcion' => $datos['observaciones'] ?? null,
                    'categoria_maquina_id' => $datos['categoria_id'],
                    'espacio_id' => $datos['espacio_id'],
                    'estado_maquina_id' => $datos['estado_id'],
                ];
                $id = DB::table('maquina')->insertGetId($fila, 'maquina_id');
                Auditoria::cambio('maquina', 'INSERT', $id, null, $fila);
            });

            return redirect()
                ->route('inventario.index')
                ->with('success', 'La máquina se registró correctamente.');
        }

        $request->validate([
            'categoria_id' => 'exists:categoria_implemento,categoria_implemento_id',
            'estado_id' => [
                'exists:estado_implemento,estado_implemento_id',
                function ($attribute, $value, $fail) {
                    $nombre = DB::table('estado_implemento')
                        ->where('estado_implemento_id', $value)
                        ->value('nombre_estado');

                    if ($nombre === 'Ocupado') {
                        $fail('El estado "Ocupado" solo se asigna al registrar un préstamo.');
                    }
                },
            ],
        ]);

        DB::transaction(function () use ($datos) {
            $categoria = DB::table('categoria_implemento')
                ->where('categoria_implemento_id', $datos['categoria_id'])
                ->lockForUpdate()
                ->first();

            $prefijo = $categoria->nombre_categoria . ' ';
            $nombres = DB::table('implemento')
                ->where('nombre_implemento', 'like', \App\Support\Busqueda::empiezaPor($prefijo))
                ->pluck('nombre_implemento');
            $siguienteNumero = $this->obtenerSiguienteNumero($nombres);
            $nombre = $prefijo . str_pad($siguienteNumero, 2, '0', STR_PAD_LEFT);

            $fila = [
                'nombre_implemento' => $nombre,
                'categoria_implemento_id' => $datos['categoria_id'],
                'estado_implemento_id' => $datos['estado_id'],
            ];
            $id = DB::table('implemento')->insertGetId($fila, 'implemento_id');
            Auditoria::cambio('implemento', 'INSERT', $id, null, $fila);
        });

        return redirect()
            ->route('inventario.index')
            ->with('success', 'El implemento se registró correctamente.');
    }

    // HU-158: modificar los datos y el estado de un elemento.
    public function update(Request $request, string $tipo, $id)
    {
        if ($tipo === 'maquina') {
            $maquina = DB::table('maquina')->where('maquina_id', $id)->first();
            abort_if(!$maquina, 404);

            $datos = $request->validate([
                'descripcion' => ['nullable', 'string', 'max:1000'],
                'espacio_id' => ['required', 'integer', 'exists:espacio,espacio_id'],
                'estado_maquina_id' => ['required', 'integer', 'exists:estado_maquina,estado_maquina_id'],
            ]);

            DB::table('maquina')->where('maquina_id', $id)->update($datos);
            Auditoria::cambio('maquina', 'UPDATE', $id, (array) $maquina, $datos);

            return back()->with('success', "{$maquina->nombre_maquina} se actualizó.");
        }

        abort_unless($tipo === 'implemento', 404);

        $implemento = DB::table('implemento as i')
            ->join('estado_implemento as ei', 'ei.estado_implemento_id', '=', 'i.estado_implemento_id')
            ->where('i.implemento_id', $id)
            ->select('i.*', 'ei.nombre_estado')
            ->first();
        abort_if(!$implemento, 404);

        $ocupado = DB::table('estado_implemento')->where('nombre_estado', 'Ocupado')->value('estado_implemento_id');

        $datos = $request->validate([
            'categoria_implemento_id' => ['required', 'integer', 'exists:categoria_implemento,categoria_implemento_id'],
            'estado_implemento_id' => ['required', 'integer', 'exists:estado_implemento,estado_implemento_id', Rule::notIn([$ocupado])],
        ], [
            'estado_implemento_id.not_in' => 'El estado "Ocupado" solo lo asigna un préstamo.',
        ]);

        if ($implemento->nombre_estado === 'Ocupado') {
            throw ValidationException::withMessages([
                'estado_implemento_id' => 'El implemento está prestado. Registra la devolución antes de cambiar su estado.',
            ]);
        }

        DB::table('implemento')->where('implemento_id', $id)->update($datos);
        Auditoria::cambio('implemento', 'UPDATE', $id, (array) $implemento, $datos);

        return back()->with('success', "{$implemento->nombre_implemento} se actualizó.");
    }

    // HU-160 / HU-162 / HU-165: detalle de un elemento con sus cambios, novedades y préstamos.
    public function show(string $tipo, $id)
    {
        if ($tipo === 'maquina') {
            $elemento = DB::table('maquina as m')
                ->join('categoria_maquina as c', 'c.categoria_maquina_id', '=', 'm.categoria_maquina_id')
                ->join('estado_maquina as em', 'em.estado_maquina_id', '=', 'm.estado_maquina_id')
                ->leftJoin('espacio as e', 'e.espacio_id', '=', 'm.espacio_id')
                ->where('m.maquina_id', $id)
                ->select('m.*', 'c.nombre_categoria', 'em.nombre_estado', 'e.nombre_espacio')
                ->first();
            abort_if(!$elemento, 404);

            $novedades = DB::table('mantenimiento_maquina as mm')
                ->join('estado_maquina_mantenimiento as e', 'e.estado_maquina_mantenimiento_id', '=', 'mm.estado_maquina_mantenimiento_id')
                ->where('mm.maquina_id', $id)
                ->orderByDesc('mm.fecha_mantenimiento')
                ->orderByDesc('mm.mantenimiento_maquina_id')
                ->select('mm.*', 'e.nombre_estado')
                ->get();

            return view('inventario-detalle', [
                'tipo' => 'maquina',
                'elemento' => $elemento,
                'nombre' => $elemento->nombre_maquina,
                'codigo' => 'MAQ-' . $elemento->maquina_id,
                'novedades' => $novedades,
                'prestamos' => collect(),
                'historial' => Auditoria::historial('maquina', $id),
                'espacios' => DB::table('espacio')->orderBy('nombre_espacio')->get(),
                'estados' => DB::table('estado_maquina')->orderBy('estado_maquina_id')->get(),
                'estadosMantenimiento' => DB::table('estado_maquina_mantenimiento')->orderBy('estado_maquina_mantenimiento_id')->get(),
            ]);
        }

        abort_unless($tipo === 'implemento', 404);

        $elemento = DB::table('implemento as i')
            ->join('categoria_implemento as c', 'c.categoria_implemento_id', '=', 'i.categoria_implemento_id')
            ->join('estado_implemento as ei', 'ei.estado_implemento_id', '=', 'i.estado_implemento_id')
            ->where('i.implemento_id', $id)
            ->select('i.*', 'c.nombre_categoria', 'ei.nombre_estado')
            ->first();
        abort_if(!$elemento, 404);

        // HU-165: historial de préstamos del implemento.
        $prestamos = DB::table('prestamo_implemento as pi')
            ->join('prestamo as p', 'p.prestamo_id', '=', 'pi.prestamo_id')
            ->join('estado_implemento_devuelto as ed', 'ed.estado_implemento_devuelto_id', '=', 'pi.estado_implemento_devuelto_id')
            ->leftJoin('usuario as u', 'u.usuario_id', '=', 'p.usuario_id')
            ->leftJoin('personal as per', 'per.personal_id', '=', 'p.personal_id')
            ->where('pi.implemento_id', $id)
            ->orderByDesc('p.fecha_prestado')
            ->select(
                'p.prestamo_id', 'p.fecha_prestado', 'p.fecha_devuelto', 'p.activo', 'ed.nombre_estado as estado_devolucion',
                DB::raw("COALESCE(NULLIF(CONCAT_WS(' ', u.nombre, u.apellido), ''), CONCAT_WS(' ', per.nombre, per.apellido)) AS destinatario"),
                DB::raw("CASE WHEN p.usuario_id IS NOT NULL THEN 'Usuario' ELSE 'Instructor' END AS tipo_destinatario")
            )
            ->get();

        return view('inventario-detalle', [
            'tipo' => 'implemento',
            'elemento' => $elemento,
            'nombre' => $elemento->nombre_implemento,
            'codigo' => 'IMP-' . $elemento->implemento_id,
            'novedades' => collect(),
            'prestamos' => $prestamos,
            'historial' => Auditoria::historial('implemento', $id),
            'categorias' => DB::table('categoria_implemento')->orderBy('nombre_categoria')->get(),
            'estados' => DB::table('estado_implemento')->where('nombre_estado', '!=', 'Ocupado')->orderBy('estado_implemento_id')->get(),
        ]);
    }

    // HU-161: registrar novedades, inspecciones o mantenimientos de una máquina.
    public function registrarNovedad(Request $request, $id)
    {
        $maquina = DB::table('maquina')->where('maquina_id', $id)->first();
        abort_if(!$maquina, 404);

        $datos = $request->validate([
            'fecha_mantenimiento' => ['required', 'date', 'before_or_equal:today'],
            'tipo_novedad' => ['required', 'in:Novedad,Inspección,Mantenimiento'],
            'detalles_mantenimiento' => ['required', 'string', 'max:2000'],
            'estado_maquina_mantenimiento_id' => ['required', 'integer', 'exists:estado_maquina_mantenimiento,estado_maquina_mantenimiento_id'],
            'bloquear' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($datos, $maquina) {
            DB::table('mantenimiento_maquina')->insert([
                'maquina_id' => $maquina->maquina_id,
                'fecha_mantenimiento' => $datos['fecha_mantenimiento'],
                'detalles_mantenimiento' => "[{$datos['tipo_novedad']}] {$datos['detalles_mantenimiento']}",
                'estado_maquina_mantenimiento_id' => $datos['estado_maquina_mantenimiento_id'],
            ]);

            // Si la novedad impide el uso, la máquina pasa a "Mantenimiento".
            if (!empty($datos['bloquear'])) {
                $enMantenimiento = DB::table('estado_maquina')->where('nombre_estado', 'Mantenimiento')->value('estado_maquina_id');

                if ($enMantenimiento && (int) $maquina->estado_maquina_id !== (int) $enMantenimiento) {
                    DB::table('maquina')->where('maquina_id', $maquina->maquina_id)->update(['estado_maquina_id' => $enMantenimiento]);
                    Auditoria::cambio('maquina', 'UPDATE', $maquina->maquina_id, ['estado_maquina_id' => $maquina->estado_maquina_id], ['estado_maquina_id' => $enMantenimiento]);
                }
            }
        });

        return back()->with('success', 'La novedad quedó registrada.');
    }

    // Actualizar el estado de una novedad (pendiente → en proceso → finalizado).
    public function actualizarNovedad(Request $request, $id, $novedadId)
    {
        $datos = $request->validate([
            'estado_maquina_mantenimiento_id' => ['required', 'integer', 'exists:estado_maquina_mantenimiento,estado_maquina_mantenimiento_id'],
        ]);

        $actualizadas = DB::table('mantenimiento_maquina')
            ->where('maquina_id', $id)
            ->where('mantenimiento_maquina_id', $novedadId)
            ->update($datos);

        abort_if($actualizadas === 0, 404);

        return back()->with('success', 'El estado de la novedad se actualizó.');
    }

    // HU-163: reporte PDF del inventario con los filtros aplicados.
    public function exportar(Request $request)
    {
        [$tipo, $maquinas, $implementos] = $this->filtrar($request);

        $filas = $maquinas->map(fn ($m) => [
            'MAQ-' . $m->maquina_id, $m->nombre_maquina, 'Máquina', $m->nombre_categoria, $m->nombre_espacio ?? '—', $m->nombre_estado,
            $m->ultima_fecha ? Carbon::parse($m->ultima_fecha)->format('d/m/Y') : 'Sin registro',
        ])->concat($implementos->map(fn ($i) => [
            'IMP-' . $i->implemento_id, $i->nombre_implemento, 'Implemento', $i->nombre_categoria, '—', $i->nombre_estado, 'No aplica',
        ]));

        $resumen = ['Elementos: ' . $filas->count() . ' (máquinas: ' . $maquinas->count() . ', implementos: ' . $implementos->count() . ')'];

        foreach ($this->categoriasBajoStock() as $categoria) {
            $resumen[] = "Stock bajo: {$categoria->nombre_categoria} tiene {$categoria->activos} activos (mínimo {$categoria->stock_minimo}).";
        }

        Auditoria::movimiento('Exportar', ['reporte' => 'inventario', 'filtros' => $request->query()]);

        $pdf = PdfReporte::tabla('Inventario del gimnasio', [
            ['titulo' => 'Código', 'ancho' => 70],
            ['titulo' => 'Nombre', 'ancho' => 170],
            ['titulo' => 'Tipo', 'ancho' => 80],
            ['titulo' => 'Categoría', 'ancho' => 130],
            ['titulo' => 'Ubicación', 'ancho' => 130],
            ['titulo' => 'Estado', 'ancho' => 95],
            ['titulo' => 'Últ. mantenimiento', 'ancho' => 107],
        ], $filas->values(), $resumen);

        return PdfReporte::descargar($pdf, 'inventario-' . now()->format('Ymd-His') . '.pdf');
    }

    /**
     * HU-159: categorías de implementos cuya cantidad activa (disponibles + prestados)
     * está por debajo del stock mínimo.
     */
    /** Clase de color de cada estado para la barra de la tarjeta. */
    private const COLORES_ESTADO = [
        'Disponible' => 'disponible',
        'Ocupado' => 'ocupado',
        'Averiado' => 'averiado',
        'Perdido' => 'perdido',
        'Mantenimiento' => 'mantenimiento',
        'Retirada' => 'retirada',
    ];

    /**
     * Agrupa las unidades filtradas por categoría. Cada tarjeta trae la cantidad, el
     * conteo por estado (para la barra) y la lista de unidades (para el pop-up "Ver").
     * Sin filtros también aparecen las categorías sin unidades.
     */
    private function tarjetasPorCategoria(string $tipo, Collection $maquinas, Collection $implementos, bool $hayFiltros): Collection
    {
        $tarjetas = collect();

        $grupos = [
            'maquina' => [
                'categorias' => DB::table('categoria_maquina')->orderBy('nombre_categoria')->get(),
                'estados' => DB::table('estado_maquina')->orderBy('estado_maquina_id')->pluck('nombre_estado'),
                'unidades' => $maquinas->groupBy('categoria_maquina_id'),
                'llave' => 'categoria_maquina_id',
                'descripcion' => 'descripcion_maquina',
            ],
            'implemento' => [
                'categorias' => DB::table('categoria_implemento')->orderBy('nombre_categoria')->get(),
                'estados' => DB::table('estado_implemento')->orderBy('estado_implemento_id')->pluck('nombre_estado'),
                'unidades' => $implementos->groupBy('categoria_implemento_id'),
                'llave' => 'categoria_implemento_id',
                'descripcion' => 'descripcion_implemento',
            ],
        ];

        foreach ($grupos as $tipoGrupo => $grupo) {
            if ($tipo !== 'todos' && $tipo !== $tipoGrupo) {
                continue;
            }

            foreach ($grupo['categorias'] as $categoria) {
                $id = $categoria->{$grupo['llave']};
                $unidades = $grupo['unidades']->get($id, collect());

                if ($hayFiltros && $unidades->isEmpty()) {
                    continue;
                }

                $porEstado = $unidades->countBy('nombre_estado');
                $activos = ($porEstado['Disponible'] ?? 0) + ($porEstado['Ocupado'] ?? 0);

                $tarjetas->push((object) [
                    'clave' => ($tipoGrupo === 'maquina' ? 'm:' : 'i:') . $id,
                    'tipo' => $tipoGrupo,
                    'id' => $id,
                    'nombre' => $categoria->nombre_categoria,
                    'descripcion' => $categoria->{$grupo['descripcion']},
                    'stock_minimo' => $categoria->stock_minimo ?? null,
                    'bajo_stock' => $tipoGrupo === 'implemento' && $activos < (int) ($categoria->stock_minimo ?? 0),
                    'imagen' => Imagenes::url($categoria->imagen ?? null, 'categorias'),
                    'total' => $unidades->count(),
                    'estados' => $grupo['estados']->map(fn ($nombre) => [
                        'nombre' => $nombre,
                        'total' => (int) ($porEstado[$nombre] ?? 0),
                        'clase' => self::COLORES_ESTADO[$nombre] ?? 'otro',
                    ])->values()->all(),
                    'unidades' => $unidades->sortBy($tipoGrupo === 'maquina' ? 'nombre_maquina' : 'nombre_implemento')->map(fn ($u) => [
                        'codigo' => $tipoGrupo === 'maquina' ? "MAQ-{$u->maquina_id}" : "IMP-{$u->implemento_id}",
                        'nombre' => $tipoGrupo === 'maquina' ? $u->nombre_maquina : $u->nombre_implemento,
                        'estado' => $u->nombre_estado,
                        'clase' => self::COLORES_ESTADO[$u->nombre_estado] ?? 'otro',
                        'ubicacion' => $tipoGrupo === 'maquina' ? ($u->nombre_espacio ?? 'Sin ubicación') : null,
                        'mantenimiento' => $tipoGrupo === 'maquina' && $u->ultima_fecha ? Carbon::parse($u->ultima_fecha)->format('d/m/Y') : null,
                        'url' => route('inventario.show', [$tipoGrupo, $tipoGrupo === 'maquina' ? $u->maquina_id : $u->implemento_id]),
                    ])->values()->all(),
                ]);
            }
        }

        return $tarjetas;
    }

    // Editar una categoría del inventario: nombre, descripción, stock mínimo y foto.
    public function actualizarCategoria(Request $request, string $tipo, $id)
    {
        $tabla = $tipo === 'maquina' ? 'categoria_maquina' : 'categoria_implemento';
        $llave = $tabla . '_id';
        $columnaDescripcion = $tipo === 'maquina' ? 'descripcion_maquina' : 'descripcion_implemento';

        $categoria = DB::table($tabla)->where($llave, $id)->first();
        abort_unless($categoria, 404);

        $request->merge(['nombre_categoria' => trim((string) $request->input('nombre_categoria'))]);

        $datos = $request->validateWithBag('categoria', [
            'nombre_categoria' => ['required', 'string', 'max:45', Rule::unique($tabla, 'nombre_categoria')->ignore($id, $llave)],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'stock_minimo' => $tipo === 'implemento' ? ['required', 'integer', 'min:0', 'max:10000'] : ['nullable'],
            'imagen' => Imagenes::REGLAS,
            'quitar_imagen' => ['nullable', 'boolean'],
        ], [
            'nombre_categoria.unique' => 'Ya existe una categoría con ese nombre.',
        ]);

        $cambios = [
            'nombre_categoria' => $datos['nombre_categoria'],
            $columnaDescripcion => $datos['descripcion'] ?? null,
        ];

        if ($tipo === 'implemento') {
            $cambios['stock_minimo'] = (int) $datos['stock_minimo'];
        }

        if ($request->hasFile('imagen') || !empty($datos['quitar_imagen'])) {
            Imagenes::borrar($categoria->imagen, 'categorias');
            $cambios['imagen'] = $request->hasFile('imagen') ? Imagenes::guardar($request->file('imagen'), 'categorias') : null;
        }

        DB::table($tabla)->where($llave, $id)->update($cambios);

        $antes = (array) $categoria;
        $despues = $cambios;
        if (array_key_exists('imagen', $cambios)) {
            $antes['foto'] = $categoria->imagen ? 'Sí' : 'No';
            $despues['foto'] = $cambios['imagen'] ? 'Actualizada' : 'Quitada';
            unset($despues['imagen']);
        }
        Auditoria::cambio($tabla, 'UPDATE', $id, $antes, $despues);

        return back()->with('success', "La categoría {$datos['nombre_categoria']} se actualizó.");
    }

    private function categoriasBajoStock()
    {
        return DB::table('categoria_implemento as c')
            ->leftJoin('implemento as i', 'i.categoria_implemento_id', '=', 'c.categoria_implemento_id')
            ->leftJoin('estado_implemento as ei', 'ei.estado_implemento_id', '=', 'i.estado_implemento_id')
            ->groupBy('c.categoria_implemento_id', 'c.nombre_categoria', 'c.stock_minimo')
            ->havingRaw("COUNT(i.implemento_id) FILTER (WHERE ei.nombre_estado IN ('Disponible', 'Ocupado')) < c.stock_minimo")
            ->orderBy('c.nombre_categoria')
            ->select(
                'c.categoria_implemento_id',
                'c.nombre_categoria',
                'c.stock_minimo',
                DB::raw("COUNT(i.implemento_id) FILTER (WHERE ei.nombre_estado IN ('Disponible', 'Ocupado')) AS activos"),
                DB::raw("COUNT(i.implemento_id) FILTER (WHERE ei.nombre_estado = 'Disponible') AS disponibles")
            )
            ->get();
    }

    /**
     * Aplica los filtros de la vista (HU-41). Devuelve [tipo, máquinas, implementos].
     */
    private function filtrar(Request $request): array
    {
        // Filtros recibidos desde la vista (HU-41).
        $tipo = in_array($request->input('tipo'), ['maquina', 'implemento'], true)
            ? $request->input('tipo')
            : 'todos';
        $buscar = trim((string) $request->input('buscar', ''));
        $categoria = (string) $request->input('categoria', '');
        $espacio = (string) $request->input('espacio', '');
        $estado = trim((string) $request->input('estado', ''));

        // Última fecha de mantenimiento registrada por máquina.
        $ultimosMantenimientos = DB::table('mantenimiento_maquina')
            ->select('maquina_id', DB::raw('MAX(fecha_mantenimiento) AS ultima_fecha'))
            ->groupBy('maquina_id');

        $consultaMaquinas = DB::table('maquina as m')
            ->join('categoria_maquina as c', 'm.categoria_maquina_id', '=', 'c.categoria_maquina_id')
            ->leftJoin('espacio as e', 'm.espacio_id', '=', 'e.espacio_id')
            ->join('estado_maquina as em', 'm.estado_maquina_id', '=', 'em.estado_maquina_id')
            ->leftJoinSub($ultimosMantenimientos, 'um', function ($join) {
                $join->on('m.maquina_id', '=', 'um.maquina_id');
            })
            ->select('m.*', 'c.nombre_categoria', 'e.nombre_espacio', 'em.nombre_estado', 'um.ultima_fecha');

        $consultaImplementos = DB::table('implemento as i')
            ->join('categoria_implemento as c', 'i.categoria_implemento_id', '=', 'c.categoria_implemento_id')
            ->join('estado_implemento as ei', 'i.estado_implemento_id', '=', 'ei.estado_implemento_id')
            ->select('i.*', 'c.nombre_categoria', 'ei.nombre_estado');

        if ($buscar !== '') {
            $consultaMaquinas->where(function ($q) use ($buscar) {
                $q->where('m.nombre_maquina', 'ILIKE', \App\Support\Busqueda::contiene($buscar))
                    ->orWhere('c.nombre_categoria', 'ILIKE', \App\Support\Busqueda::contiene($buscar))
                    ->orWhere('e.nombre_espacio', 'ILIKE', \App\Support\Busqueda::contiene($buscar))
                    ->orWhere('em.nombre_estado', 'ILIKE', \App\Support\Busqueda::contiene($buscar))
                    ->orWhereRaw("CONCAT('MAQ-', m.maquina_id) ILIKE ?", [\App\Support\Busqueda::contiene($buscar)]);
            });

            $consultaImplementos->where(function ($q) use ($buscar) {
                $q->where('i.nombre_implemento', 'ILIKE', \App\Support\Busqueda::contiene($buscar))
                    ->orWhere('c.nombre_categoria', 'ILIKE', \App\Support\Busqueda::contiene($buscar))
                    ->orWhere('ei.nombre_estado', 'ILIKE', \App\Support\Busqueda::contiene($buscar))
                    ->orWhereRaw("CONCAT('IMP-', i.implemento_id) ILIKE ?", [\App\Support\Busqueda::contiene($buscar)]);
            });
        }

        // La categoría llega como "m:ID" (máquina) o "i:ID" (implemento).
        if (preg_match('/^([mi]):(\d+)$/', $categoria, $coincidencia)) {
            if ($coincidencia[1] === 'm') {
                $consultaMaquinas->where('m.categoria_maquina_id', (int) $coincidencia[2]);
                $tipo = 'maquina';
            } else {
                $consultaImplementos->where('i.categoria_implemento_id', (int) $coincidencia[2]);
                $tipo = 'implemento';
            }
        }

        // Los implementos no tienen ubicación: filtrar por espacio solo muestra máquinas.
        if ($espacio !== '' && ctype_digit($espacio)) {
            $consultaMaquinas->where('m.espacio_id', (int) $espacio);
            $tipo = 'maquina';
        }

        if ($estado !== '') {
            $consultaMaquinas->where('em.nombre_estado', $estado);
            $consultaImplementos->where('ei.nombre_estado', $estado);
        }

        $maquinasFiltradas = $tipo === 'implemento'
            ? collect()
            : $consultaMaquinas->orderByDesc('m.maquina_id')->get();

        $implementosFiltrados = $tipo === 'maquina'
            ? collect()
            : $consultaImplementos->orderByDesc('i.implemento_id')->get();

        return [$tipo, $maquinasFiltradas, $implementosFiltrados];
    }

    /**
     * Calcula el siguiente consecutivo según los nombres existentes.
     * Ejemplo: Colchoneta 01, Colchoneta 02 -> siguiente: 03.
     */
    private function obtenerSiguienteNumero($nombres): int
    {
        $numeros = [];

        foreach ($nombres as $nombre) {
            if (preg_match('/(\d+)$/', $nombre, $coincidencia)) {
                $numeros[] = (int) $coincidencia[1];
            }
        }

        return empty($numeros) ? 1 : max($numeros) + 1;
    }
}