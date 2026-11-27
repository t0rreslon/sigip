<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Suscripcion;
use App\Support\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SuscripcionController extends Controller
{
    // Asignar una suscripción a un usuario.
    public function store(Request $request)
    {
        $datos = $request->validateWithBag('asignar', [
            'documento' => ['required', 'string', 'regex:/^[0-9]{8,11}$/'],
            'plan_id' => [
                'required',
                Rule::exists('plan', 'plan_id')->where('estado_plan', true),
            ],
        ], [
            'documento.regex' => 'El documento debe contener entre 8 y 11 números.',
            'plan_id.exists' => 'El plan seleccionado no existe o está desactivado.',
        ]);

        DB::transaction(function () use ($datos) {
            $usuario = DB::table('usuario')
                ->where('documento', $datos['documento'])
                ->lockForUpdate()
                ->first();

            if (!$usuario) {
                throw ValidationException::withMessages([
                    'documento' => 'No existe un usuario registrado con ese documento.',
                ])->errorBag('asignar');
            }

            if (!$usuario->estado_usuario) {
                throw ValidationException::withMessages([
                    'documento' => 'El usuario se encuentra inactivo.',
                ])->errorBag('asignar');
            }

            // Un usuario no puede tener dos suscripciones vigentes al mismo tiempo.
            $tieneVigente = DB::table('suscripcion as s')
                ->join('plan as pl', 'pl.plan_id', '=', 's.plan_id')
                ->where('s.usuario_id', $usuario->usuario_id)
                ->whereRaw('(' . Suscripcion::sqlEstadoCalculado() . ") <> 'vencida'")
                ->exists();

            if ($tieneVigente) {
                throw ValidationException::withMessages([
                    'documento' => 'El usuario ya tiene una suscripción vigente o pendiente de activación. Modifícala en lugar de crear otra.',
                ])->errorBag('asignar');
            }

            $plan = Plan::findOrFail($datos['plan_id']);

            // Las fechas se calculan solas: queda "por activar" (fecha de registro, sin vencimiento)
            // y al completarse el pago empieza la vigencia (ver Suscripcion::iniciarVigencia).
            $suscripcion = Suscripcion::create([
                'usuario_id' => $usuario->usuario_id,
                'plan_id' => $plan->plan_id,
                'fecha_inicio' => now(),
                'fecha_fin' => null,
                // Tiquetera: entradas disponibles. Suscripción: días de vigencia.
                'cantidad_dias_restantes' => $plan->cantidad_entradas,
                'estado' => false,
            ]);

            // Un plan gratuito se activa de inmediato (y empieza su vigencia hoy).
            $suscripcion->sincronizarEstado();

            Auditoria::cambio('suscripcion', 'INSERT', $suscripcion->suscripcion_id, null, [
                'usuario' => "{$usuario->nombre} {$usuario->apellido} ({$usuario->documento})",
            ] + self::paraAuditoria($suscripcion, $plan));
        });

        return redirect()
            ->route('planes.index')
            ->with('mensaje', 'Plan asignado. Se activará y empezará su vigencia cuando el pago esté completo y verificado.');
    }

    // HU-37 / HU-39: actualizar el plan, la fecha de vencimiento y las entradas.
    public function update(Request $request, $id)
    {
        $suscripcion = Suscripcion::with('plan')->findOrFail($id);

        $datos = $request->validateWithBag('editarSuscripcion', [
            'plan_id' => [
                'required',
                Rule::exists('plan', 'plan_id')->where(function ($q) use ($suscripcion) {
                    // Se permite conservar el plan actual aunque esté desactivado.
                    $q->where('estado_plan', true)->orWhere('plan_id', $suscripcion->plan_id);
                }),
            ],
            'fecha_fin' => ['nullable', 'date'],
            'cantidad_dias_restantes' => ['required', 'integer', 'min:0', 'max:3650'],
        ], [
            'plan_id.exists' => 'El plan seleccionado no existe o está desactivado.',
        ]);

        $plan = Plan::findOrFail($datos['plan_id']);
        $fechaInicio = $suscripcion->fecha_inicio->copy()->startOfDay();

        $fechaFin = $datos['fecha_fin'] ?? null;

        if ($plan->tipo_plan === 'suscripcion' && !$fechaFin && $suscripcion->estado) {
            throw ValidationException::withMessages([
                'fecha_fin' => 'Los planes de suscripción requieren una fecha de vencimiento.',
            ])->errorBag('editarSuscripcion');
        }

        if ($fechaFin && Carbon::parse($fechaFin)->lt($fechaInicio)) {
            throw ValidationException::withMessages([
                'fecha_fin' => 'La fecha de vencimiento no puede ser anterior a la fecha de inicio (' . $fechaInicio->format('d/m/Y') . ').',
            ])->errorBag('editarSuscripcion');
        }

        DB::transaction(function () use ($suscripcion, $plan, $datos, $fechaFin) {
            $antes = self::paraAuditoria($suscripcion, $suscripcion->plan);

            $suscripcion->plan_id = $plan->plan_id;
            $suscripcion->fecha_fin = $fechaFin ? Carbon::parse($fechaFin)->endOfDay() : null;
            $suscripcion->cantidad_dias_restantes = $datos['cantidad_dias_restantes'];
            $suscripcion->save();

            // Con el nuevo plan puede cambiar el valor a pagar o la vigencia.
            $suscripcion->unsetRelation('plan');
            $suscripcion->sincronizarEstado();

            Auditoria::cambio('suscripcion', 'UPDATE', $suscripcion->suscripcion_id, $antes, self::paraAuditoria($suscripcion, $plan));
        });

        return redirect()
            ->route('planes.index', $request->only(['buscar', 'estado', 'plan', 'page']))
            ->with('mensaje', 'Suscripción actualizada exitosamente.');
    }

    /**
     * Datos legibles de la suscripción para el historial de cambios.
     */
    private static function paraAuditoria(Suscripcion $suscripcion, Plan $plan): array
    {
        return [
            'plan' => $plan->nombre_plan,
            'fecha_inicio' => $suscripcion->fecha_inicio?->format('d/m/Y'),
            'fecha_vencimiento' => $suscripcion->fecha_fin?->format('d/m/Y') ?? 'Sin vencimiento',
            'entradas_o_dias_restantes' => (int) $suscripcion->cantidad_dias_restantes,
            'estado' => $suscripcion->estado ? 'Activa' : 'Por activar',
        ];
    }
}
