<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tipo de acción para auditar el uso del asistente con IA (quién, cuándo y qué tipo de
 * respuesta pidió; no se guarda el texto de la pregunta por si contiene datos personales).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!DB::table('tipo_accion')->where('nombre_accion', 'Consultar asistente')->exists()) {
            DB::table('tipo_accion')->insert(['nombre_accion' => 'Consultar asistente']);
        }
    }

    public function down(): void
    {
        // Las acciones auditadas no se borran (tablas de auditoría inmutables), así que el tipo se conserva.
    }
};
