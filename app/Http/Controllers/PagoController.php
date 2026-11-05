<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pago;
use App\Models\MetodoPago;
use App\Models\Suscripcion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;


class PagoController extends Controller
{
    // ==========================================
    // 1. CONSULTAR PAGOS Y APLICAR FILTROS
    // ==========================================

    public function index(Request $request)
    {
        $consulta = Pago::with([
            'suscripcion.usuario',
            'suscripcion.plan',
            'metodoPago'
        ]);

        // Búsqueda por nombre, apellido, documento o plan
        if ($request->filled('buscar')) {

            $buscar = trim($request->buscar);

            $consulta->whereHas('suscripcion', function ($query) use ($buscar) {

                $query->whereHas('usuario', function ($usuario) use ($buscar) {

                    $usuario->where('nombre', 'ILIKE', "%{$buscar}%")
                        ->orWhere('apellido', 'ILIKE', "%{$buscar}%")
                        ->orWhere('documento', 'ILIKE', "%{$buscar}%")
                        ->orWhereRaw(
                            "CONCAT(nombre, ' ', apellido) ILIKE ?",
                            ["%{$buscar}%"]
                        );

                })->orWhereHas('plan', function ($plan) use ($buscar) {

                    $plan->where('nombre_plan', 'ILIKE', "%{$buscar}%");

                });

            });
        }

        // Filtro por fecha inicial
        if ($request->filled('fecha_desde')) {
            $consulta->whereDate(
                'fecha_pago',
                '>=',
                $request->fecha_desde
            );
        }

        // Filtro por fecha final
        if ($request->filled('fecha_hasta')) {
            $consulta->whereDate(
                'fecha_pago',
                '<=',
                $request->fecha_hasta
            );
        }

        // Filtro por monto mínimo
        if ($request->filled('monto_min')) {
            $consulta->where(
                'monto',
                '>=',
                $request->monto_min
            );
        }

        // Filtro por monto máximo
        if ($request->filled('monto_max')) {
            $consulta->where(
                'monto',
                '<=',
                $request->monto_max
            );
        }

        // Filtro por estado
        if ($request->filled('estado')) {
            $consulta->where(
                'estado_pago',
                $request->estado
            );
        }

        // Obtener pagos paginados
        $pagos = $consulta
            ->orderByDesc('fecha_pago')
            ->orderByDesc('pago_id')
            ->paginate(10)
            ->withQueryString();

        // Contadores reales
        $pendientes = Pago::where(
            'estado_pago',
            'Pendiente'
        )->count();

        $verificados = Pago::where(
            'estado_pago',
            'Verificado'
        )->count();

        $rechazados = Pago::where(
            'estado_pago',
            'Rechazado'
        )->count();

        // ==========================================
        // SUSCRIPCIONES CON SALDO PENDIENTE
        // ==========================================

        $pagosAcumulados = Pago::select(
                'suscripcion_id',
                DB::raw('SUM(monto) AS total_pagado')
            )
            ->where('estado_pago', '<>', 'Rechazado')
            ->groupBy('suscripcion_id')
            ->pluck('total_pagado', 'suscripcion_id');

        $suscripciones = Suscripcion::with([
            'usuario',
            'plan'
        ])->get();

        // Calcular el saldo de cada suscripción
        $suscripciones = $suscripciones->map(function ($suscripcion) use ($pagosAcumulados) {

            $totalPagado = (float) (
                $pagosAcumulados[$suscripcion->suscripcion_id] ?? 0
            );

            $suscripcion->saldo_pendiente = max(
                0,
                (float) $suscripcion->plan->costo - $totalPagado
            );

            return $suscripcion;

        })->filter(function ($suscripcion) {

            return $suscripcion->saldo_pendiente > 0;

        })->values();

        // Métodos de pago disponibles
        $metodosPago = MetodoPago::where(
            'estado_metodo',
            true
        )->orderBy('nombre_metodo')->get();

        return view('pago', compact(
            'pagos',
            'suscripciones',
            'metodosPago',
            'pendientes',
            'verificados',
            'rechazados'
        ));
    }


    // ==========================================
    // 2. REGISTRAR UN NUEVO PAGO
    // ==========================================

    public function store(Request $request)
    {
        $datosValidos = $request->validate([

            'suscripcion_id' => [
                'required',
                'exists:suscripcion,suscripcion_id'
            ],

            'metodo_pago_id' => [
                'required',
                Rule::exists('metodo_pago', 'metodo_pago_id')
                    ->where('estado_metodo', true)
            ],

            'monto' => [
                'required',
                'numeric',
                'min:0.01'
            ],

        ]);

        DB::transaction(function () use ($datosValidos) {

            // Bloquear la suscripción mientras se valida el saldo
            $suscripcion = Suscripcion::with('plan')
                ->where('suscripcion_id', $datosValidos['suscripcion_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Sumar pagos pendientes y verificados
            // Los rechazados no cuentan para el saldo
            $totalPagado = Pago::where(
                    'suscripcion_id',
                    $suscripcion->suscripcion_id
                )
                ->where('estado_pago', '<>', 'Rechazado')
                ->sum('monto');

            $saldoPendiente = max(
                0,
                (float) $suscripcion->plan->costo - (float) $totalPagado
            );

            // Validar que no se supere el saldo
            if ((float) $datosValidos['monto'] > $saldoPendiente) {

                throw ValidationException::withMessages([
                    'monto' => 'El monto supera el saldo pendiente de la suscripción. Saldo disponible: $' .
                        number_format($saldoPendiente, 0, ',', '.')
                ]);
            }

            // Registrar pago
            // La fecha y el estado se asignan automáticamente
            Pago::create([
                'suscripcion_id' => $datosValidos['suscripcion_id'],
                'metodo_pago_id' => $datosValidos['metodo_pago_id'],
                'monto' => $datosValidos['monto'],
                'estado_pago' => 'Pendiente',
            ]);

        });

        return redirect()
            ->route('pago.index')
            ->with('mensaje', 'Pago registrado exitosamente.');
    }


    // ==========================================
    // 3. REGISTRAR REVISIÓN DE UN PAGO
    // ==========================================

    public function revisar(Request $request, $id)
    {
        $datosValidos = $request->validate([

            'estado_pago' => [
                'required',
                Rule::in(['Verificado', 'Rechazado'])
            ],

        ]);

        DB::transaction(function () use ($id, $datosValidos) {

            $pago = Pago::where('pago_id', $id)
                ->lockForUpdate()
                ->firstOrFail();

            // Un pago verificado no puede modificarse nuevamente
            if ($pago->estado_pago === 'Verificado') {

                throw ValidationException::withMessages([
                    'estado_pago' => 'Este pago ya fue verificado y no puede modificarse.'
                ]);
            }

            $pago->estado_pago = $datosValidos['estado_pago'];
            $pago->fecha_revision = now();

            $pago->save();

        });

        return redirect()
            ->route('pago.index')
            ->with('mensaje', 'Revisión del pago registrada exitosamente.');
    }
}
