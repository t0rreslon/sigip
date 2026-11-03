<?php

namespace App\Services;

use App\Models\Acceso;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistrarAccesoService
{
    public function registrar(int $usuarioId): Acceso
    {
        return DB::transaction(function () use ($usuarioId) {
            $usuario = Usuario::query()
                ->where('usuario_id', $usuarioId)
                ->lockForUpdate()
                ->first();

            if (!$usuario) {
                throw ValidationException::withMessages([
                    'usuario_id' => 'El usuario no existe.',
                ]);
            }

            if (!$usuario->estado_usuario) {
                throw ValidationException::withMessages([
                    'usuario_id' => 'El usuario está inactivo.',
                ]);
            }

            // Aquí verificaremos la suscripción o tiquetera.
            // También comprobaremos que no exista un
            // acceso anterior sin fecha de salida.
            // Solo después registraremos el ingreso.

            throw new \LogicException(
                'La validación del servicio debe implementarse antes de habilitar el acceso.'
            );
        });
    }
}