<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pago;
use App\Models\MetodoPago;
use App\Models\Suscripcion;
use App\Support\Auditoria;
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

                    $usuario->where('nombre', 'ILIKE', \App\Support\Busqueda::contiene($buscar))
                        ->orWhere('apellido', 'ILIKE', \App\Support\Busqueda::contiene($buscar))
                        ->orWhere('documento', 'ILIKE', \App\Support\Busqueda::contiene($buscar))
                        ->orWhereRaw(
                            "CONCAT(nombre, ' ', apellido) ILIKE ?",
                            [\App\Support\Busqueda::contiene($buscar)]
                        );

                })->orWhereHas('plan', function ($plan) use ($buscar) {

                    $plan->where('nombre_plan', 'ILIKE', \App\Support\Busqueda::contiene($buscar));

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
            $pago = Pago::create([
                'suscripcion_id' => $datosValidos['suscripcion_id'],
                'metodo_pago_id' => $datosValidos['metodo_pago_id'],
                'monto' => $datosValidos['monto'],
                'estado_pago' => 'Pendiente',
            ]);

            $suscripcion->loadMissing('usuario');

            Auditoria::cambio('pago', 'INSERT', $pago->pago_id, null, [
                'usuario' => trim($suscripcion->usuario?->nombre . ' ' . $suscripcion->usuario?->apellido),
                'plan' => $suscripcion->plan->nombre_plan,
                'suscripcion' => '#' . $suscripcion->suscripcion_id,
                'metodo' => DB::table('metodo_pago')->where('metodo_pago_id', $datosValidos['metodo_pago_id'])->value('nombre_metodo'),
                'monto' => '$' . number_format((float) $datosValidos['monto'], 0, ',', '.'),
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

            // Solo se revisan pagos pendientes. Un pago rechazado no puede
            // volver a verificarse porque ya no cuenta en el saldo y podría
            // hacer que el total pagado supere el costo del plan.
            if ($pago->estado_pago !== 'Pendiente') {

                throw ValidationException::withMessages([
                    'estado_pago' => 'Este pago ya fue ' . mb_strtolower($pago->estado_pago) . ' y no puede modificarse. Registra un pago nuevo si es necesario.'
                ]);
            }

            $pago->estado_pago = $datosValidos['estado_pago'];
            $pago->fecha_revision = now();

            $pago->save();

            Auditoria::cambio('pago', 'ESTADO', $pago->pago_id, ['estado_pago' => 'Pendiente'], ['estado_pago' => $pago->estado_pago]);

            // Si con este pago se completa el valor del plan, la suscripción se activa.
            Suscripcion::with('plan')
                ->where('suscripcion_id', $pago->suscripcion_id)
                ->lockForUpdate()
                ->first()
                ?->sincronizarEstado();

        });

        return redirect()
            ->route('pago.index')
            ->with('mensaje', 'Revisión del pago registrada exitosamente.');
    }
}
