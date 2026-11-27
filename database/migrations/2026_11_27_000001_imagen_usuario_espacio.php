<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Foto de los usuarios y de los espacios. Se guarda el nombre del archivo; la imagen
 * vive en storage/app/private/imagenes y se sirve solo al personal autenticado
 * (la foto de una persona es un dato personal, Ley 1581 de 2012).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuario', function (Blueprint $table) {
            $table->string('imagen', 120)->nullable();
        });

        Schema::table('espacio', function (Blueprint $table) {
            $table->string('imagen', 120)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('usuario', fn (Blueprint $table) => $table->dropColumn('imagen'));
        Schema::table('espacio', fn (Blueprint $table) => $table->dropColumn('imagen'));
    }
};
