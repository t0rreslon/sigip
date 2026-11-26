<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mejoras de seguridad según ISO/IEC 25010 (característica Seguridad).
 *
 *  - No repudio: las tablas de auditoría no se pueden modificar ni borrar
 *    (triggers) y cada fila guarda un hash encadenado con la anterior, así
 *    cualquier alteración directa en la base se detecta con
 *    `php artisan auditoria:verificar`.
 *  - Responsabilidad: nuevos tipos de acción para los intentos de inicio de
 *    sesión fallidos y los bloqueos por exceso de intentos.
 *  - Autenticidad: columnas para el doble factor (TOTP) de las cuentas.
 *  - Confidencialidad (Ley 1581 de 2012): fecha en que el usuario autorizó el
 *    tratamiento de sus datos personales.
 *
 * Es idempotente.
 *   php artisan migrate --path=database/migrations/2026_11_26_000001_seguridad_iso25010.php
 */
return new class extends Migration
{
    private const TABLAS_AUDITORIA = [
        // tabla => [columna id, expresión con el contenido de la fila]
        'auditoria_tablas' => [
            'id',
            "concat_ws('|', r.id, r.nombre_tabla, r.operacion, r.registro_id, r.datos_anteriores::text, r.datos_nuevos::text, r.usuario, r.creado_en::text)",
        ],
        'auditoria_movimientos' => [
            'auditoria_id',
            "concat_ws('|', r.auditoria_id, host(r.ip), r.cuenta_id, r.tipo_accion_id, r.datos::text, extract(epoch from r.fecha_hora)::text)",
        ],
    ];

    public function up(): void
    {
        foreach (['Login fallido', 'Bloqueo de cuenta', 'Activar 2FA', 'Desactivar 2FA'] as $accion) {
            if (!DB::table('tipo_accion')->where('nombre_accion', $accion)->exists()) {
                DB::table('tipo_accion')->insert(['nombre_accion' => $accion]);
            }
        }

        if (!Schema::hasColumn('cuenta', 'doble_factor_secreto')) {
            DB::statement('ALTER TABLE cuenta ADD COLUMN doble_factor_secreto TEXT');
            DB::statement('ALTER TABLE cuenta ADD COLUMN doble_factor_activo BOOLEAN NOT NULL DEFAULT FALSE');
        }

        if (!Schema::hasColumn('usuario', 'autoriza_datos_en')) {
            DB::statement('ALTER TABLE usuario ADD COLUMN autoriza_datos_en TIMESTAMPTZ');
            DB::statement("COMMENT ON COLUMN usuario.autoriza_datos_en IS 'Fecha en que el usuario autorizó el tratamiento de datos personales (Ley 1581 de 2012).'");
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pgcrypto');

        foreach (self::TABLAS_AUDITORIA as $tabla => [$id, $contenido]) {
            $this->protegerTabla($tabla, $id, $contenido);
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::TABLAS_AUDITORIA) as $tabla) {
            DB::statement("DROP TRIGGER IF EXISTS trg_{$tabla}_inmutable ON {$tabla}");
            DB::statement("DROP TRIGGER IF EXISTS trg_{$tabla}_truncate ON {$tabla}");
            DB::statement("DROP TRIGGER IF EXISTS trg_{$tabla}_hash ON {$tabla}");
            DB::statement("DROP FUNCTION IF EXISTS fn_{$tabla}_hash()");
            DB::statement("ALTER TABLE {$tabla} DROP COLUMN IF EXISTS hash, DROP COLUMN IF EXISTS hash_anterior");
        }

        DB::statement('DROP FUNCTION IF EXISTS fn_auditoria_inmutable()');
    }

    private function protegerTabla(string $tabla, string $id, string $contenido): void
    {
        if (!Schema::hasColumn($tabla, 'hash')) {
            DB::statement("ALTER TABLE {$tabla} ADD COLUMN hash_anterior CHAR(64)");
            DB::statement("ALTER TABLE {$tabla} ADD COLUMN hash CHAR(64)");
        }

        // Las filas que ya existían se encadenan en orden antes de bloquear la tabla.
        DB::statement("DROP TRIGGER IF EXISTS trg_{$tabla}_inmutable ON {$tabla}");

        DB::unprepared(<<<SQL
            DO \$\$
            DECLARE
                r {$tabla}%ROWTYPE;
                previo CHAR(64) := NULL;
            BEGIN
                FOR r IN SELECT * FROM {$tabla} ORDER BY {$id} LOOP
                    IF r.hash IS NULL THEN
                        UPDATE {$tabla}
                           SET hash_anterior = previo,
                               hash = encode(digest(coalesce(previo, '') || {$contenido}, 'sha256'), 'hex')
                         WHERE {$id} = r.{$id}
                     RETURNING hash INTO previo;
                    ELSE
                        previo := r.hash;
                    END IF;
                END LOOP;
            END
            \$\$;
        SQL);

        // Cada inserción toma el hash de la última fila (con un bloqueo para que no se crucen).
        DB::unprepared(<<<SQL
            CREATE OR REPLACE FUNCTION fn_{$tabla}_hash() RETURNS trigger
            LANGUAGE plpgsql AS \$\$
            DECLARE
                r {$tabla}%ROWTYPE;
            BEGIN
                PERFORM pg_advisory_xact_lock(hashtext('{$tabla}'));
                SELECT hash INTO NEW.hash_anterior FROM {$tabla} ORDER BY {$id} DESC LIMIT 1;
                r := NEW;
                NEW.hash := encode(digest(coalesce(NEW.hash_anterior, '') || {$contenido}, 'sha256'), 'hex');
                RETURN NEW;
            END
            \$\$;
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_auditoria_inmutable() RETURNS trigger
            LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'La tabla % es de auditoría: sus registros no se pueden modificar ni eliminar.', TG_TABLE_NAME
                    USING ERRCODE = 'insufficient_privilege';
            END
            $$;
        SQL);

        DB::statement("DROP TRIGGER IF EXISTS trg_{$tabla}_hash ON {$tabla}");
        DB::statement("CREATE TRIGGER trg_{$tabla}_hash BEFORE INSERT ON {$tabla} FOR EACH ROW EXECUTE FUNCTION fn_{$tabla}_hash()");
        DB::statement("CREATE TRIGGER trg_{$tabla}_inmutable BEFORE UPDATE OR DELETE ON {$tabla} FOR EACH ROW EXECUTE FUNCTION fn_auditoria_inmutable()");
        DB::statement("DROP TRIGGER IF EXISTS trg_{$tabla}_truncate ON {$tabla}");
        DB::statement("CREATE TRIGGER trg_{$tabla}_truncate BEFORE TRUNCATE ON {$tabla} FOR EACH STATEMENT EXECUTE FUNCTION fn_auditoria_inmutable()");
    }
};
