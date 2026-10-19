<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los movimientos automáticos (citas liberadas por no confirmar) no tienen
     * usuario. El comando ya escribía user_id = null y la vista ya muestra
     * "Sistema", pero la columna era NOT NULL y el comando fallaba.
     */
    public function up(): void
    {
        Schema::table('historial_citas', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('historial_citas', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
