<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Foto de perfil de cada cuenta del personal (administradores, operadores e instructores).
 * Se guarda en el disco privado igual que las fotos de usuarios y espacios.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal', function (Blueprint $table) {
            $table->string('imagen', 120)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('personal', fn (Blueprint $table) => $table->dropColumn('imagen'));
    }
};
