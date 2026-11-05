<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class InventarioController extends Controller
{
    public function index()
{
    // Última fecha de mantenimiento registrada por máquina.
    $ultimosMantenimientos = DB::table('mantenimiento_maquina')
        ->select('maquina_id', DB::raw('MAX(fecha_mantenimiento) AS ultima_fecha'))
        ->groupBy('maquina_id');

    // Consultar todas las máquinas.
    $maquinasCompletas = DB::table('maquina as m')
        ->join('categoria_maquina as c', 'm.categoria_maquina_id', '=', 'c.categoria_maquina_id')
        ->leftJoin('espacio as e', 'm.espacio_id', '=', 'e.espacio_id')
        ->join('estado_maquina as em', 'm.estado_maquina_id', '=', 'em.estado_maquina_id')
        ->leftJoinSub($ultimosMantenimientos, 'um', function ($join) {
            $join->on('m.maquina_id', '=', 'um.maquina_id');
        })
        ->select(
            'm.*',
            'c.nombre_categoria',
            'e.nombre_espacio',
            'em.nombre_estado',
            'um.ultima_fecha'
        )
        ->orderByDesc('m.maquina_id')
        ->get();

    // Consultar todos los implementos.
    $implementosCompletos = DB::table('implemento as i')
        ->join('categoria_implemento as c', 'i.categoria_implemento_id', '=', 'c.categoria_implemento_id')
        ->join('estado_implemento as ei', 'i.estado_implemento_id', '=', 'ei.estado_implemento_id')
        ->select(
            'i.*',
            'c.nombre_categoria',
            'ei.nombre_estado'
        )
        ->orderByDesc('i.implemento_id')
        ->get();

    // Consultar implementos que todavía están prestados.
    $implementosPrestadosIds = DB::table('prestamo_implemento as pi')
        ->join('prestamo as p', 'pi.prestamo_id', '=', 'p.prestamo_id')
        ->where('p.activo', true)
        ->whereNull('p.fecha_devuelto')
        ->pluck('pi.implemento_id')
        ->unique();

    // Calcular totales generales antes de aplicar la paginación.
    $totalMaquinas = $maquinasCompletas->count();
    $totalImplementos = $implementosCompletos->count();

    $totalPrestados = $implementosPrestadosIds->count();

    $totalDisponibles = $implementosCompletos->filter(function ($implemento) use ($implementosPrestadosIds) {
        return (int) $implemento->estado_implemento_id === 1
            && !$implementosPrestadosIds->contains($implemento->implemento_id);
    })->count();

    $totalAveriados = $implementosCompletos
        ->where('estado_implemento_id', 3)
        ->count();

    $totalPerdidos = $implementosCompletos
        ->where('estado_implemento_id', 4)
        ->count();

    // Unificar máquinas e implementos para paginarlos en una sola tabla.
    $elementos = $maquinasCompletas->map(function ($maquina) {
        $maquina->tipo_elemento = 'maquina';
        return $maquina;
    })->concat(
        $implementosCompletos->map(function ($implemento) {
            $implemento->tipo_elemento = 'implemento';
            return $implemento;
        })
    )->values();

    // Configuración de la paginación.
    $porPagina = 7;
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
            'path' => request()->url(),
            'query' => request()->query(),
        ]
    );

    // Separar los elementos de la página para conservar los dos ciclos de Blade.
    $maquinas = $elementosPagina
        ->where('tipo_elemento', 'maquina')
        ->values();

    $implementos = $elementosPagina
        ->where('tipo_elemento', 'implemento')
        ->values();

    // Datos para los selectores del formulario.
    $categoriasMaquina = DB::table('categoria_maquina')
        ->orderBy('nombre_categoria')
        ->get();

    $categoriasImplemento = DB::table('categoria_implemento')
        ->orderBy('nombre_categoria')
        ->get();

    $espacios = DB::table('espacio')
        ->orderBy('nombre_espacio')
        ->get();

    $estadosMaquina = DB::table('estado_maquina')
        ->orderBy('estado_maquina_id')
        ->get();

    $estadosImplemento = DB::table('estado_implemento')
        ->orderBy('estado_implemento_id')
        ->get();

    return view('inventario', compact(
        'maquinas',
        'implementos',
        'paginacion',
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
        'estadosImplemento'
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
            'estado_id' => 'exists:estado_implemento,estado_implemento_id',
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