<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Foto de cada categoría del inventario (implementos y máquinas). Se muestra en la
 * tarjeta de la categoría y se guarda en el disco privado como las demás fotos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categoria_implemento', fn (Blueprint $table) => $table->string('imagen', 120)->nullable());
        Schema::table('categoria_maquina', fn (Blueprint $table) => $table->string('imagen', 120)->nullable());
    }

    public function down(): void
    {
        Schema::table('categoria_implemento', fn (Blueprint $table) => $table->dropColumn('imagen'));
        Schema::table('categoria_maquina', fn (Blueprint $table) => $table->dropColumn('imagen'));
    }
};
