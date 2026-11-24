<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Completa los catálogos y la tabla que necesitan las HU del backlog.
 * Es idempotente: se puede ejecutar sobre una base que ya tenga los datos.
 *
 *   php artisan migrate --path=database/migrations/2026_11_24_000001_completar_catalogos_sigip.php
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->insertarSiFalta('tipo_accion', 'nombre_accion', [
            'Crear', 'Actualizar', 'Eliminar', 'Iniciar sesión', 'Cerrar sesión', 'Exportar',
        ]);

        // Reservas solicitadas por instructores que el personal rechaza (HU-170).
        $this->insertarSiFalta('estado_reserva', 'nombre_estado', ['Rechazada']);

        // Actividades propuestas por instructores (HU-200, HU-201).
        $this->insertarSiFalta('estado_programacion_actividad', 'nombre_estado', ['Solicitada', 'Rechazada']);

        // Jornadas para asignar horarios a los instructores (HU-151).
        foreach ([
            ['Mañana', '06:00', '12:00'],
            ['Tarde', '12:00', '18:00'],
            ['Noche', '18:00', '21:00'],
        ] as [$jornada, $inicio, $fin]) {
            if (!DB::table('turno')->where('tipo_jornada', $jornada)->exists()) {
                DB::table('turno')->insert([
                    'tipo_jornada' => $jornada,
                    'hora_inicio' => $inicio,
                    'hora_fin' => $fin,
                    'estado_turno' => true,
                ]);
            }
        }

        // Mantenimientos de espacios (HU-168).
        if (!Schema::hasTable('mantenimiento_espacio')) {
            DB::statement(<<<'SQL'
                CREATE TABLE mantenimiento_espacio (
                    mantenimiento_espacio_id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
                    espacio_id INTEGER NOT NULL REFERENCES espacio (espacio_id),
                    fecha_inicio DATE NOT NULL,
                    fecha_fin DATE,
                    detalles TEXT NOT NULL,
                    estado_maquina_mantenimiento_id SMALLINT NOT NULL REFERENCES estado_maquina_mantenimiento (estado_maquina_mantenimiento_id),
                    registrado_por VARCHAR(50),
                    fecha_registro TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                    CONSTRAINT check_fechas_mantenimiento_espacio CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio)
                )
            SQL);
        }

        // Índices para los historiales.
        DB::statement('CREATE INDEX IF NOT EXISTS idx_auditoria_tablas_registro ON auditoria_tablas (nombre_tabla, registro_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_auditoria_movimientos_cuenta ON auditoria_movimientos (cuenta_id, fecha_hora DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_programacion_espacio_fechas ON programacion_actividad (espacio_id, fecha_inicio, fecha_fin)');
    }

    public function down(): void
    {
        // Los catálogos se conservan: otras tablas pueden referenciarlos.
        Schema::dropIfExists('mantenimiento_espacio');
    }

    private function insertarSiFalta(string $tabla, string $columna, array $valores): void
    {
        foreach ($valores as $valor) {
            if (!DB::table($tabla)->where($columna, $valor)->exists()) {
                DB::table($tabla)->insert([$columna => $valor]);
            }
        }
    }
};
