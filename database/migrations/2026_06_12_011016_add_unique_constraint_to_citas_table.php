<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            // Evita dos citas en la misma fecha y hora exacta
            $table->unique(['fecha', 'hora'], 'citas_fecha_hora_unique');
        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropUnique('citas_fecha_hora_unique');
        });
    }
};