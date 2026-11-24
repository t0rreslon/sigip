<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class InventarioController extends Controller
{
    public function index(Request $request)
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
                $q->where('m.nombre_maquina', 'ILIKE', "%{$buscar}%")
                    ->orWhere('c.nombre_categoria', 'ILIKE', "%{$buscar}%")
                    ->orWhere('e.nombre_espacio', 'ILIKE', "%{$buscar}%")
                    ->orWhere('em.nombre_estado', 'ILIKE', "%{$buscar}%")
                    ->orWhereRaw("CONCAT('MAQ-', m.maquina_id) ILIKE ?", ["%{$buscar}%"]);
            });

            $consultaImplementos->where(function ($q) use ($buscar) {
                $q->where('i.nombre_implemento', 'ILIKE', "%{$buscar}%")
                    ->orWhere('c.nombre_categoria', 'ILIKE', "%{$buscar}%")
                    ->orWhere('ei.nombre_estado', 'ILIKE', "%{$buscar}%")
                    ->orWhereRaw("CONCAT('IMP-', i.implemento_id) ILIKE ?", ["%{$buscar}%"]);
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

        // Unificar máquinas e implementos para paginarlos en una sola tabla.
        $elementos = $maquinasFiltradas->map(function ($maquina) {
            $maquina->tipo_elemento = 'maquina';
            return $maquina;
        })->concat(
            $implementosFiltrados->map(function ($implemento) {
                $implemento->tipo_elemento = 'implemento';
                return $implemento;
            })
        )->values();

        $porPagina = 10;
        $paginaActual = LengthAwarePaginator::resolveCurrentPage();

        $elementosPagina = $elementos
            ->slice(($paginaActual - 1) * $porPagina, $porPagina)
            ->values();

        $paginacion = new LengthAwarePaginator(
            $elementosPagina,
            $elementos->count(),
            $porPagina,
            $paginaActual,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $maquinas = $elementosPagina->where('tipo_elemento', 'maquina')->values();
        $implementos = $elementosPagina->where('tipo_elemento', 'implemento')->values();

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

        return view('inventario', compact(
            'maquinas',
            'implementos',
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

                DB::table('maquina')->insert([
                    'nombre_maquina' => $nombre,
                    'descripcion' => $datos['observaciones'] ?? null,
                    'categoria_maquina_id' => $datos['categoria_id'],
                    'espacio_id' => $datos['espacio_id'],
                    'estado_maquina_id' => $datos['estado_id'],
                ]);
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
                ->where('nombre_implemento', 'like', $prefijo . '%')
                ->pluck('nombre_implemento');
            $siguienteNumero = $this->obtenerSiguienteNumero($nombres);
            $nombre = $prefijo . str_pad($siguienteNumero, 2, '0', STR_PAD_LEFT);

            DB::table('implemento')->insert([
                'nombre_implemento' => $nombre,
                'categoria_implemento_id' => $datos['categoria_id'],
                'estado_implemento_id' => $datos['estado_id'],
            ]);
        });

        return redirect()
            ->route('inventario.index')
            ->with('success', 'El implemento se registró correctamente.');
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