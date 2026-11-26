<?php

use App\Models\Espacio;
use App\Models\Suscripcion;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Desactiva las suscripciones vencidas (por fecha o sin entradas).
// Reemplaza la actualización que antes se hacía al abrir la vista.
Artisan::command('suscripciones:vencer', function () {
    $ids = DB::table('suscripcion as s')
        ->join('plan as pl', 'pl.plan_id', '=', 's.plan_id')
        ->where('s.estado', true)
        ->whereRaw('(' . Suscripcion::sqlEstadoCalculado() . ") = 'vencida'")
        ->pluck('s.suscripcion_id');

    $total = DB::table('suscripcion')->whereIn('suscripcion_id', $ids)->update(['estado' => false]);

    $this->info("Suscripciones desactivadas: {$total}");
})->purpose('Desactiva las suscripciones vencidas');

// Recalcula el estado (Disponible / Reservado) de los espacios habilitados.
Artisan::command('espacios:actualizar-estados', function () {
    DB::table('espacio')->pluck('espacio_id')->each(
        fn ($id) => Espacio::actualizarEstadoSegunReservas((int) $id)
    );

    $this->info('Estados de los espacios actualizados.');
})->purpose('Actualiza el estado de los espacios según sus reservas');

// ISO 25010 – No repudio: comprueba que nadie alteró las tablas de auditoría.
// Cada fila guarda el hash de la anterior; si alguien cambia o borra una fila
// directamente en la base, la cadena se rompe en ese punto.
Artisan::command('auditoria:verificar', function () {
    $tablas = [
        'auditoria_tablas' => ['id', "concat_ws('|', r.id, r.nombre_tabla, r.operacion, r.registro_id, r.datos_anteriores::text, r.datos_nuevos::text, r.usuario, r.creado_en::text)"],
        'auditoria_movimientos' => ['auditoria_id', "concat_ws('|', r.auditoria_id, host(r.ip), r.cuenta_id, r.tipo_accion_id, r.datos::text, extract(epoch from r.fecha_hora)::text)"],
    ];

    $todoBien = true;

    foreach ($tablas as $tabla => [$id, $contenido]) {
        $errores = DB::select(<<<SQL
            SELECT {$id} AS id,
                   (r.hash_anterior IS DISTINCT FROM lag(r.hash) OVER (ORDER BY {$id})) AS enlace_roto,
                   (r.hash IS DISTINCT FROM encode(digest(coalesce(r.hash_anterior, '') || {$contenido}, 'sha256'), 'hex')) AS contenido_alterado
              FROM {$tabla} r
             ORDER BY {$id}
        SQL);

        $malos = array_filter($errores, fn ($fila) => $fila->enlace_roto || $fila->contenido_alterado);

        if ($malos === []) {
            $this->info("{$tabla}: " . count($errores) . ' registros, cadena íntegra.');
        } else {
            $todoBien = false;
            $this->error("{$tabla}: " . count($malos) . ' registro(s) alterado(s) o faltante(s). Primer id: ' . reset($malos)->id);
        }
    }

    return $todoBien ? 0 : 1;
})->purpose('Verifica la integridad de las tablas de auditoría');

// Recuperación: quita el doble factor de una cuenta que perdió su celular.
Artisan::command('cuenta:quitar-doble-factor {login}', function (string $login) {
    $cuenta = \App\Models\Cuenta::where('login', $login)->first();

    if (!$cuenta) {
        $this->error('No existe una cuenta con ese usuario.');

        return 1;
    }

    $cuenta->forceFill(['doble_factor_secreto' => null, 'doble_factor_activo' => false])->save();
    \App\Support\Auditoria::movimiento('Desactivar 2FA', ['origen' => 'consola'], $cuenta->cuenta_id);
    $this->info("Doble factor desactivado para {$login}.");

    return 0;
})->purpose('Desactiva el doble factor de una cuenta');

Schedule::command('auditoria:verificar')->dailyAt('02:00');
Schedule::command('suscripciones:vencer')->hourly();
Schedule::command('espacios:actualizar-estados')->everyFifteenMinutes();
