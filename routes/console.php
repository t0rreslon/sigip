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
    $todoBien = true;

    foreach (\App\Support\Auditoria::verificarIntegridad() as $tabla => $r) {
        if ($r['alterados'] === 0) {
            $this->info("{$tabla}: {$r['total']} registros, cadena íntegra.");
        } else {
            $todoBien = false;
            $this->error("{$tabla}: {$r['alterados']} registro(s) alterado(s) o faltante(s). Primer id: {$r['primer_id']}");
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

// Prueba la configuración del correo saliente (MAIL_* del .env) enviando un mensaje corto.
Artisan::command('correo:probar {destino}', function (string $destino) {
    $mailer = config('mail.default');

    if ($mailer === 'log') {
        $this->warn('MAIL_MAILER=log: el correo se guardará en storage/logs/laravel.log y NO se enviará.');
    }

    try {
        \Illuminate\Support\Facades\Mail::raw(
            "Este es un correo de prueba de SIGIP. Si lo recibes, la recuperación de contraseña ya puede enviar correos.",
            fn ($m) => $m->to($destino)->subject('SIGIP · Correo de prueba')
        );
    } catch (\Throwable $e) {
        $this->error('No se pudo enviar: ' . $e->getMessage());

        return 1;
    }

    $this->info("Correo enviado con \"{$mailer}\" a {$destino}. Revisa la bandeja de entrada y la carpeta de spam.");

    return 0;
})->purpose('Envía un correo de prueba con la configuración actual');
