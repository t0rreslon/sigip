<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\ControlAcceso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ControlAccesoController extends Controller
{
    // MOSTRAR VISTA E HISTORIAL DE ACCESOS
    public function index()
    {
        $historial = DB::table('acceso as a')
            ->join('usuario as u', 'a.usuario_id', '=', 'u.usuario_id')
            ->whereDate('a.fecha_ingreso', today())
            ->orderByDesc('a.fecha_ingreso')
            ->select(
                'a.acceso_id',
                'a.usuario_id',
                'a.fecha_ingreso',
                'a.fecha_salida',
                'u.nombre',
                'u.apellido',
                'u.documento'
            )
            ->get();

        return view('controlacceso', compact('historial'));
    }

    // REGISTRAR SALIDA
    public function registrarSalida($usuario_id)
    {
        $acceso = DB::table('acceso')
            ->where('usuario_id', $usuario_id)
            ->whereNull('fecha_salida')
            ->orderByDesc('fecha_ingreso')
            ->first();

        if (!$acceso) {
            return response()->json([
                'success' => false,
                'message' => 'No existe un ingreso pendiente de registrar salida.'
            ], 404);
        }

        DB::table('acceso')
            ->where('acceso_id', $acceso->acceso_id)
            ->update([
                'fecha_salida' => now()
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Salida registrada correctamente.'
        ]);
    }

    
    // REGISTRAR INGRESO
    public function registrarIngreso(Request $request)
    {
        $request->validate([
            'usuario_id' => 'required|integer'
        ]);

        // Buscar usuario
        $usuario = Usuario::find($request->usuario_id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario no existe.'
            ], 404);
        }

        // Verificar estado del usuario
        if (!$usuario->estado_usuario) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario se encuentra inactivo.'
            ], 403);
        }

        // Buscar la suscripción activa
        $suscripcion = DB::table('suscripcion as s')
            ->join('plan as pl', 's.plan_id', '=', 'pl.plan_id')
            ->where('s.usuario_id', $usuario->usuario_id)
            ->where('s.estado', true)
            ->orderByDesc('s.fecha_inicio')
            ->select(
                's.suscripcion_id',
                's.fecha_inicio',
                's.fecha_fin',
                's.cantidad_dias_restantes',
                's.estado',
                'pl.nombre_plan',
                'pl.tipo_plan',
                'pl.cantidad_entradas'
            )
            ->first();

        if (!$suscripcion) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario no tiene una suscripción activa.'
            ], 403);
        }

        // Verificar pago confirmado
        $pagoConfirmado = DB::table('pago')
            ->where('suscripcion_id', $suscripcion->suscripcion_id)
            ->where('esta_confirmado', true)
            ->exists();

        if (!$pagoConfirmado) {
            return response()->json([
                'success' => false,
                'message' => 'El pago de la suscripción no está confirmado.'
            ], 403);
        }

        // Validar condiciones según el tipo de plan
        if ($suscripcion->tipo_plan === 'suscripcion') {

            $ahora = now();

            if (
                $ahora->lt(
                    \Illuminate\Support\Carbon::parse($suscripcion->fecha_inicio)
                ) ||
                (
                    $suscripcion->fecha_fin !== null &&
                    $ahora->gt(
                        \Illuminate\Support\Carbon::parse($suscripcion->fecha_fin)
                    )
                )
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'La suscripción se encuentra fuera de su periodo de vigencia.'
                ], 403);
            }

        } elseif ($suscripcion->tipo_plan === 'tiquetera') {

            if ($suscripcion->cantidad_dias_restantes <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario no tiene entradas disponibles.'
                ], 403);
            }

        } else {
            return response()->json([
                'success' => false,
                'message' => 'El tipo de plan no está configurado.'
            ], 403);
        }

        // Verificar si ya existe un ingreso sin salida
        $accesoPendiente = DB::table('acceso')
            ->where('usuario_id', $usuario->usuario_id)
            ->whereNull('fecha_salida')
            ->exists();

        if ($accesoPendiente) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario ya tiene un ingreso registrado sin salida.'
            ], 409);
        }

        // Registrar ingreso y descontar entrada en una transacción
        try {
            $resultado = DB::transaction(function () use ($usuario, $suscripcion) {

                // Volver a comprobar el acceso pendiente para evitar duplicados
                $accesoPendiente = DB::table('acceso')
                    ->where('usuario_id', $usuario->usuario_id)
                    ->whereNull('fecha_salida')
                    ->exists();

                if ($accesoPendiente) {
                    throw new \RuntimeException(
                        'El usuario ya tiene un ingreso registrado sin salida.'
                    );
                }

                // Descontar una entrada solamente para tiqueteras
                if ($suscripcion->tipo_plan === 'tiquetera') {

                    $actualizada = DB::table('suscripcion')
                        ->where('suscripcion_id', $suscripcion->suscripcion_id)
                        ->where('cantidad_dias_restantes', '>', 0)
                        ->decrement('cantidad_dias_restantes');

                    if (!$actualizada) {
                        throw new \RuntimeException(
                            'No hay entradas disponibles para descontar.'
                        );
                    }
                }

                // Crear registro de acceso
                $accesoId = DB::table('acceso')->insertGetId([
                    'usuario_id' => $usuario->usuario_id,
                    'fecha_ingreso' => now(),
                    'fecha_salida' => null
                ], 'acceso_id');

                return $accesoId;
            });

            return response()->json([
                'success' => true,
                'message' => 'Ingreso registrado correctamente.',
                'acceso_id' => $resultado,
                'suscripcion_id' => $suscripcion->suscripcion_id,
                'plan' => $suscripcion->nombre_plan,
                'entradas_restantes' =>
                    $suscripcion->tipo_plan === 'tiquetera'
                        ? $suscripcion->cantidad_dias_restantes - 1
                        : null
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 409);
        }
    }


    // CONSULTAR USUARIOS, SUSCRIPCIONES Y PAGOS
    public function consultar(Request $request)
    {
        $termino = trim($request->input('buscar', ''));

        if ($termino === '') {
            return response()->json([
                'success' => false,
                'message' => 'Debes ingresar un dato para buscar.'
            ], 422);
        }

        // Buscar usuario por nombre, apellido, documento o correo.
        $usuarios = Usuario::query()
            ->where(function ($query) use ($termino) {
                $query->where('nombre', 'ILIKE', "%{$termino}%")
                    ->orWhere('apellido', 'ILIKE', "%{$termino}%")
                    ->orWhere('documento', 'ILIKE', "%{$termino}%")
                    ->orWhere('correo', 'ILIKE', "%{$termino}%");
            })
            ->limit(10)
            ->get([
                'usuario_id',
                'nombre',
                'apellido',
                'documento',
                'correo',
                'telefono',
                'estado_usuario'
            ]);

        if ($usuarios->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron usuarios.'
            ], 404);
        }

        // Consultar suscripciones, planes y pagos.
        $resultado = $usuarios->map(function ($usuario) {

            $suscripciones = DB::table('suscripcion as s')
                ->join('plan as pl', 's.plan_id', '=', 'pl.plan_id')
                ->where('s.usuario_id', $usuario->usuario_id)
                ->select(
                    's.suscripcion_id',
                    's.fecha_inicio',
                    's.fecha_fin',
                    's.cantidad_dias_restantes',
                    's.estado',
                    'pl.nombre_plan',
                    'pl.tipo_plan',
                    'pl.cantidad_entradas',
                    'pl.costo'
                )
                ->selectRaw("
                    EXISTS (
                        SELECT 1
                        FROM pago p
                        WHERE p.suscripcion_id = s.suscripcion_id
                        AND p.esta_confirmado = TRUE
                    ) AS pago_confirmado
                ")
                ->orderByDesc('s.fecha_inicio')
                ->get();

            return [
                'usuario' => $usuario,
                'suscripciones' => $suscripciones
            ];
        });

        return response()->json([
            'success' => true,
            'usuarios' => $resultado
        ]);
    }
}
