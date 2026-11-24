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

Schedule::command('suscripciones:vencer')->hourly();
Schedule::command('espacios:actualizar-estados')->everyFifteenMinutes();
