<?php

namespace App\Support;

/**
 * Traduce el nombre de una ruta a una descripción legible para el historial de acciones (HU-207).
 */
class NombresRutas
{
    private const DESCRIPCIONES = [
        'usuarios.store' => 'Registró un usuario',
        'usuarios.update' => 'Modificó un usuario',
        'usuarios.estado' => 'Cambió el estado de un usuario',
        'control-acceso.registrar-ingreso' => 'Registró un ingreso',
        'control-acceso.salida' => 'Registró una salida',
        'prestamos.store' => 'Registró un préstamo',
        'prestamos.devolver' => 'Registró una devolución',
        'inventario.store' => 'Registró un elemento del inventario',
        'inventario.update' => 'Modificó un elemento del inventario',
        'inventario.novedades.store' => 'Registró una novedad o mantenimiento de un equipo',
        'planes.store' => 'Registró un plan',
        'planes.update' => 'Modificó un plan',
        'planes.destroy' => 'Desactivó un plan',
        'planes.activar' => 'Activó un plan',
        'suscripciones.store' => 'Asignó una suscripción',
        'suscripciones.update' => 'Modificó una suscripción',
        'pago.store' => 'Registró un pago',
        'pago.revisar' => 'Revisó un pago',
        'espacios.store' => 'Registró un espacio',
        'espacios.estado' => 'Cambió el estado de un espacio',
        'espacios.mantenimientos.store' => 'Registró el mantenimiento de un espacio',
        'reservas.store' => 'Registró una reserva',
        'reservas.cancelar' => 'Canceló una reserva',
        'reservas.responder' => 'Confirmó o rechazó una reserva',
        'actividades.store' => 'Registró una actividad',
        'actividades.update' => 'Modificó una actividad',
        'programaciones.store' => 'Programó una actividad',
        'programaciones.update' => 'Modificó una actividad programada',
        'programaciones.cancelar' => 'Canceló una actividad programada',
        'programaciones.validar' => 'Validó una actividad solicitada',
        'programaciones.inscribir' => 'Inscribió un usuario a una actividad',
        'programaciones.desinscribir' => 'Retiró un usuario de una actividad',
        'programaciones.asistencia' => 'Registró la asistencia de una actividad',
        'perfil.update' => 'Actualizó sus datos personales',
        'perfil.clave' => 'Cambió su contraseña',
    ];

    public static function describir(?string $ruta): string
    {
        if (!$ruta) {
            return 'Acción';
        }

        return self::DESCRIPCIONES[$ruta] ?? ucfirst(str_replace(['.', '-'], ' ', $ruta));
    }
}
