<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anuncios', function (Blueprint $table) {
            // Ruta relativa dentro del disco de imágenes (ver ImagenService)
            $table->string('imagen')->nullable()->after('color');
            // Posición en el carrusel (1 = primero)
            $table->unsignedInteger('orden')->default(0)->after('imagen');
        });

        // Los anuncios que ya existían conservan el orden en que se crearon
        DB::table('anuncios')->update(['orden' => DB::raw('id')]);
    }

    public function down(): void
    {
        Schema::table('anuncios', function (Blueprint $table) {
            $table->dropColumn(['imagen', 'orden']);
        });
    }
};
