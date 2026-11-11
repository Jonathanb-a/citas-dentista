<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotente: un despliegue repetido no debe fallar si la columna ya existe
        if (Schema::hasColumn('users', 'foto')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            // Ruta relativa de la foto de perfil (ImagenService, carpeta "perfiles"); nunca una URL
            $table->string('foto')->nullable();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'foto')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('foto'));
        }
    }
};
