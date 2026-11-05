<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Suscripcion;
use App\Models\Usuario;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SuscripcionController extends Controller
{
 public function index(Request $request){
    // Consultar todas las suscripciones
    $activas = Suscripcion::where('estado', true)->count();

$vencidas = Suscripcion::where('estado', false)->count();
    

// Desactivar suscripciones vencidas
Suscripcion::where('estado', true)
    ->whereNotNull('fecha_fin')
    ->whereDate('fecha_fin', '<', now()->toDateString())
    ->update(['estado' => false]);

// Calcular contadores
$activas = Suscripcion::where('estado', true)->count();
$vencidas = Suscripcion::where('estado', false)->count();

    // Contar usuarios activos sin suscripciones
    $usuariosSinSuscripcion = DB::table('usuario')
        ->where('estado_usuario', true)
        ->whereNotIn('usuario_id', function ($consulta) {
            $consulta->select('usuario_id')
                ->from('suscripcion');
        })
        ->count();

    // Preparar la consulta para la búsqueda
    $consulta = Suscripcion::with(['usuario', 'plan']);

    if ($request->filled('buscar')) {
        $buscar = $request->buscar;

        $consulta->whereHas('usuario', function ($query) use ($buscar) {
            $query->where('nombre', 'ILIKE', "%{$buscar}%")
                ->orWhere('apellido', 'ILIKE', "%{$buscar}%")
                ->orWhere('documento', 'ILIKE', "%{$buscar}%");
        });
    }

    if ($request->filled('estado')) {
    $consulta->where('estado', $request->estado === 'true');
     }

    // Obtener los resultados de la búsqueda
    $suscripciones = $consulta->get();

    // Calcular los datos de las suscripciones mostradas
    foreach ($suscripciones as $suscripcion) {

        $suscripcion->estado_pago = $this->obtenerEstadoPago($suscripcion);

        $fechaReferencia = now()->startOfDay();

        if (\Carbon\Carbon::parse($suscripcion->fecha_inicio)->isFuture()) {
            $fechaReferencia = \Carbon\Carbon::parse($suscripcion->fecha_inicio)->startOfDay();
        }

        $suscripcion->dias_calculados = $suscripcion->fecha_fin
            ? max(0, (int) $fechaReferencia->diffInDays(
                \Carbon\Carbon::parse($suscripcion->fecha_fin)->startOfDay(),
                false
            ))
            : 0;
    }

    $planes = Plan::all();

    return view('planes', compact(
        'planes',
        'suscripciones',
        'activas',
        'vencidas',
        'usuariosSinSuscripcion'
    ));
}

public function create()
{
    $usuarios = Usuario::where('estado_usuario', true)->get();

    $planes = Plan::where('estado_plan', true)->get();

    return view('crear_suscripcion', compact('usuarios', 'planes'));
}

public function store(Request $request){
    $datosValidos = $request->validate([
        'usuario_id' => [
        'required',
         Rule::exists('usuario', 'usuario_id')->where('estado_usuario', true),
     ],
        'plan_id' => [
        'required',
         Rule::exists('plan', 'plan_id')->where('estado_plan', true),
        ],
        'fecha_inicio' => 'required|date',
        'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
    ]);

    $fechaInicio = \Carbon\Carbon::parse($datosValidos['fecha_inicio']);
    $fechaFin = \Carbon\Carbon::parse($datosValidos['fecha_fin']);

    $fechaReferencia = $fechaInicio->isFuture()
        ? $fechaInicio
        : \Carbon\Carbon::today();

    $diasRestantes = max(
        0,
        $fechaReferencia->diffInDays($fechaFin, false)
    );

    Suscripcion::create([
        'usuario_id' => $datosValidos['usuario_id'],
        'plan_id' => $datosValidos['plan_id'],
        'fecha_inicio' => $datosValidos['fecha_inicio'],
        'fecha_fin' => $datosValidos['fecha_fin'],
        'cantidad_dias_restantes' => $diasRestantes,
       'estado' => false,
    ]);

    return redirect('/planes');
}



private function obtenerEstadoPago(Suscripcion $suscripcion){
    $pagos = DB::table('pago')
        ->where('suscripcion_id', $suscripcion->suscripcion_id)
        ->get();

    if ($pagos->isEmpty()) {
        return 'Pendiente';
    }

    $todosConfirmados = $pagos->every(function ($pago) {
        return $pago->esta_confirmado;
    });

    $totalPagado = $pagos
        ->where('esta_confirmado', true)
        ->sum('monto');

    if (
        $todosConfirmados &&
        $totalPagado >= $suscripcion->plan->costo
    ) {
        return 'Verificado';
    }

    return 'Pendiente';
}
}