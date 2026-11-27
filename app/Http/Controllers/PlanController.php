<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Suscripcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\Auditoria;

class PlanController extends Controller
{
    // HU-17 / HU-36: visualizar los planes con sus detalles y las suscripciones.
    public function index(Request $request)
    {
        // Planes con el número de suscripciones asociadas (detalle del plan).
        // HU-152: filtros de planes (prefijo "p_" para no chocar con los de suscripciones).
        $planes = Plan::query()
            ->when($request->filled('p_buscar'), function ($q) use ($request) {
                $buscar = trim($request->input('p_buscar'));
                $q->where(fn ($w) => $w->where('nombre_plan', 'ILIKE', \App\Support\Busqueda::contiene($buscar))->orWhere('descripcion', 'ILIKE', \App\Support\Busqueda::contiene($buscar)));
            })
            ->when(in_array($request->input('p_tipo'), ['tiquetera', 'suscripcion'], true), fn ($q) => $q->where('tipo_plan', $request->input('p_tipo')))
            ->when(in_array($request->input('p_estado'), ['1', '0'], true), fn ($q) => $q->where('estado_plan', $request->input('p_estado') === '1'))
            ->withCount([
                'suscripciones',
                'suscripciones as suscripciones_activas_count' => function ($consulta) {
                    $consulta->where('estado', true);
                },
            ])
            ->orderByDesc('estado_plan')
            ->orderBy('nombre_plan')
            ->get();

        $estadoCalculado = Suscripcion::sqlEstadoCalculado();

        // Asignaciones separadas por tipo de plan, cada una con sus filtros y su paginación
        // (prefijo "t_" para tiqueteras y "s_" para suscripciones).
        $tiqueteras = $this->asignaciones($request, 'tiquetera', 't_');
        $suscripciones = $this->asignaciones($request, 'suscripcion', 's_');

        // Contadores generales.
        $conteos = DB::table('suscripcion as s')
            ->join('plan as pl', 'pl.plan_id', '=', 's.plan_id')
            ->select('pl.tipo_plan', DB::raw("({$estadoCalculado}) AS estado_calculado"), DB::raw('COUNT(*) AS total'))
            ->groupBy('pl.tipo_plan', 'estado_calculado')
            ->get();

        $contar = fn (?string $tipo, string $estado) => (int) $conteos
            ->where('estado_calculado', $estado)
            ->when($tipo, fn ($c) => $c->where('tipo_plan', $tipo))
            ->sum('total');

        $resumenTipo = fn (string $tipo) => [
            'activa' => $contar($tipo, 'activa'),
            'por_activar' => $contar($tipo, 'por_activar'),
            'vencida' => $contar($tipo, 'vencida'),
        ];

        $usuariosSinSuscripcion = DB::table('usuario')
            ->where('estado_usuario', true)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('suscripcion')
                    ->whereColumn('suscripcion.usuario_id', 'usuario.usuario_id');
            })
            ->count();

        $todosLosPlanes = Plan::orderBy('nombre_plan')->get();
        $planesActivos = $todosLosPlanes->where('estado_plan', true)->values();

        return view('planes', [
            'planes' => $planes,
            'planesActivos' => $planesActivos,
            'todosLosPlanes' => $todosLosPlanes,
            'suscripciones' => $suscripciones,
            'tiqueteras' => $tiqueteras,
            'resumenTiqueteras' => $resumenTipo('tiquetera'),
            'resumenSuscripciones' => $resumenTipo('suscripcion'),
            'activas' => $contar(null, 'activa'),
            'porActivar' => $contar(null, 'por_activar'),
            'vencidas' => $contar(null, 'vencida'),
            'usuariosSinSuscripcion' => $usuariosSinSuscripcion,
        ]);
    }

    /**
     * Asignaciones de un tipo de plan (tiquetera o suscripción) con búsqueda, filtro por
     * estado y por plan, y paginación propia. Los parámetros llevan el prefijo indicado.
     */
    private function asignaciones(Request $request, string $tipo, string $prefijo)
    {
        $estadoCalculado = Suscripcion::sqlEstadoCalculado();

        // Total pagado (verificado) por asignación.
        $pagosVerificados = DB::table('pago')
            ->select('suscripcion_id', DB::raw('SUM(monto) AS total_pagado'))
            ->where('estado_pago', 'Verificado')
            ->groupBy('suscripcion_id');

        $consulta = DB::table('suscripcion as s')
            ->join('usuario as u', 'u.usuario_id', '=', 's.usuario_id')
            ->join('plan as pl', 'pl.plan_id', '=', 's.plan_id')
            ->leftJoinSub($pagosVerificados, 'pv', 'pv.suscripcion_id', '=', 's.suscripcion_id')
            ->where('pl.tipo_plan', $tipo)
            ->select(
                's.*',
                'u.nombre',
                'u.apellido',
                'u.documento',
                'pl.nombre_plan',
                'pl.tipo_plan',
                'pl.costo',
                'pl.cantidad_entradas',
                DB::raw('COALESCE(pv.total_pagado, 0) AS total_pagado'),
                DB::raw("({$estadoCalculado}) AS estado_calculado")
            );

        if ($request->filled($prefijo . 'buscar')) {
            $buscar = \App\Support\Busqueda::contiene(trim($request->input($prefijo . 'buscar')));

            $consulta->where(function ($q) use ($buscar) {
                $q->where('u.nombre', 'ILIKE', $buscar)
                    ->orWhere('u.apellido', 'ILIKE', $buscar)
                    ->orWhere('u.documento', 'ILIKE', $buscar)
                    ->orWhere('pl.nombre_plan', 'ILIKE', $buscar)
                    ->orWhereRaw("CONCAT_WS(' ', u.nombre, u.apellido) ILIKE ?", [$buscar]);
            });
        }

        if (in_array($request->input($prefijo . 'estado'), ['activa', 'por_activar', 'vencida'], true)) {
            $consulta->whereRaw("({$estadoCalculado}) = ?", [$request->input($prefijo . 'estado')]);
        }

        if ($request->filled($prefijo . 'plan')) {
            $consulta->where('s.plan_id', (int) $request->input($prefijo . 'plan'));
        }

        return $consulta
            ->orderByRaw("CASE ({$estadoCalculado}) WHEN 'activa' THEN 0 WHEN 'por_activar' THEN 1 ELSE 2 END")
            ->orderByDesc('s.fecha_inicio')
            ->orderByDesc('s.suscripcion_id')
            ->paginate(10, ['*'], $prefijo . 'pagina')
            ->withQueryString();
    }

    // HU-16: registrar un nuevo plan.
    public function store(Request $request)
    {
        $request->merge([
            'nombre_plan' => trim($request->input('nombre_plan', ''))
        ]);

        $datosValidos = $request->validate($this->reglas());

        $datosValidos['estado_plan'] = true;

        $plan = Plan::create($datosValidos);

        Auditoria::cambio('plan', 'INSERT', $plan->plan_id, null, $plan->getAttributes());

        return redirect()
            ->route('planes.index')
            ->with('mensaje', 'Plan registrado exitosamente.');
    }

    // HU-34: modificar un plan existente.
    public function update(Request $request, $id)
    {
        $plan = Plan::findOrFail($id);

        $request->merge([
            'nombre_plan' => trim($request->input('nombre_plan', ''))
        ]);

        $datosValidos = $request->validateWithBag('editarPlan', $this->reglas($plan->plan_id));

        $antes = $plan->getAttributes();
        $plan->fill($datosValidos);
        $plan->ultima_actualizacion = now();
        $plan->save();

        Auditoria::cambio('plan', 'UPDATE', $plan->plan_id, $antes, $plan->getAttributes());

        return redirect()
            ->route('planes.index')
            ->with('mensaje', 'Plan actualizado exitosamente.');
    }

    // HU-35: deshabilitar un plan sin eliminarlo de la base de datos.
    public function destroy($id)
    {
        $plan = Plan::findOrFail($id);

        $plan->estado_plan = false;
        $plan->ultima_actualizacion = now();
        $plan->save();

        Auditoria::cambio('plan', 'ESTADO', $plan->plan_id, ['estado_plan' => !false], ['estado_plan' => false]);

        return redirect()
            ->route('planes.index')
            ->with('mensaje', 'Plan desactivado exitosamente. Las suscripciones existentes conservan su vigencia.');
    }

    // HU-35: habilitar nuevamente un plan.
    public function activar($id)
    {
        $plan = Plan::findOrFail($id);

        $plan->estado_plan = true;
        $plan->ultima_actualizacion = now();
        $plan->save();

        Auditoria::cambio('plan', 'ESTADO', $plan->plan_id, ['estado_plan' => !true], ['estado_plan' => true]);

        return redirect()
            ->route('planes.index')
            ->with('mensaje', 'Plan reactivado exitosamente.');
    }

    // HU-197: historial de cambios de un plan.
    public function historial($id)
    {
        $plan = Plan::findOrFail($id);

        return view('historial', [
            'titulo' => 'Historial del plan',
            'subtitulo' => $plan->nombre_plan,
            'volver' => route('planes.index'),
            'registros' => Auditoria::historial('plan', $plan->plan_id),
        ]);
    }

    private function reglas(?int $planId = null): array
    {
        return [
            'nombre_plan' => [
                'required',
                'string',
                'max:30',

                function ($attribute, $value, $fail) use ($planId) {
                    $existe = Plan::whereRaw(
                        'LOWER(BTRIM(nombre_plan)) = ?',
                        [mb_strtolower(trim($value))]
                    )
                        ->when($planId, fn ($q) => $q->where('plan_id', '!=', $planId))
                        ->exists();

                    if ($existe) {
                        $fail('Ya existe un plan con este nombre. Ingresa uno diferente.');
                    }
                }
            ],

            'descripcion' => 'nullable|string|max:500',
            'tipo_plan' => 'required|in:tiquetera,suscripcion',
            'cantidad_entradas' => 'required|integer|min:1|max:3650',
            'costo' => 'required|numeric|min:0|max:99999999'
        ];
    }
}
