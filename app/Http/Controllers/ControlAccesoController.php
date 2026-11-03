<?php

namespace App\Http\Controllers;

use App\Models\ControlAcceso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // <- para utilizar consultas adicionales

class ControlAccesoController extends Controller
{
     // MOSTRAR VISTA E HISTORIAL DE ACCESOS
    public function index()
    {
        $historial = ControlAcceso::whereDate(
            'fecha_ingreso',
            today()
        )
        ->orderByDesc('fecha_ingreso')
        ->get();

        return view('controlacceso', compact('historial'));
    }


    // CONSULTAR USUARIO
    public function consultar(Request $request)
    {
        $request->validate([
            'identificacion' => 'required|string',
        ]);

        /*
         * Aquí debes consultar tu modelo Usuario
         * y relacionar sus datos con las tablas de:
         * suscripciones, planes y pagos.
         *
         * Los nombres de esas tablas y sus columnas
         * deben coincidir con tu base de datos.
         */

        return back()->with(
            'info',
            'Aquí se realizará la consulta del usuario.'
        );
    }


    // REGISTRAR INGRESO
    public function registrarIngreso(Request $request)
    {
        $request->validate([
            'usuario_id' => 'required|integer',
        ]);

        // Evitar que el usuario tenga dos ingresos abiertos.
        $accesoAbierto = ControlAcceso::where(
            'usuario_id',
            $request->usuario_id
        )
        ->whereNull('fecha_salida')
        ->exists();

        if ($accesoAbierto) {
            return back()->with(
                'error',
                'El usuario ya tiene un ingreso registrado sin salida.'
            );
        }

        /*
         * IMPORTANTE:
         * Antes de crear el acceso debes validar que el usuario
         * esté activo, tenga su suscripción vigente y su pago
         * esté verificado.
         *
         * Esa validación depende de las tablas de tu proyecto.
         */

        $acceso = ControlAcceso::create([
            'usuario_id' => $request->usuario_id,
            'fecha_ingreso' => now(),
            'fecha_salida' => null,
        ]);

        return back()->with(
            'success',
            'Ingreso registrado correctamente.'
        );
    }


    // REGISTRAR SALIDA
    public function registrarSalida($usuario_id)
    {
        // Buscar el ingreso abierto más reciente del usuario.
        $acceso = ControlAcceso::where(
            'usuario_id',
            $usuario_id
        )
        ->whereNull('fecha_salida')
        ->orderByDesc('fecha_ingreso')
        ->first();

        if (!$acceso) {
            return back()->with(
                'error',
                'No existe un ingreso pendiente de registrar salida.'
            );
        }

        $acceso->update([
            'fecha_salida' => now(),
        ]);

        return back()->with(
            'success',
            'Salida registrada correctamente.'
        );
    }
}
