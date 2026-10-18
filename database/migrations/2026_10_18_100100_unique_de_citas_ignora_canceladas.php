<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Una cita cancelada ya no ocupa su horario. La columna generada vale NULL
     * cuando la cita está cancelada, y los NULL no chocan en un índice único.
     */
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropUnique('citas_fecha_hora_unique');
        });

        Schema::table('citas', function (Blueprint $table) {
            $table->tinyInteger('ocupa_horario')->nullable()
                ->virtualAs("CASE WHEN status = 'cancelada' THEN NULL ELSE 1 END");
            $table->unique(['fecha', 'hora', 'ocupa_horario'], 'citas_fecha_hora_unique');
        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropUnique('citas_fecha_hora_unique');
        });

        Schema::table('citas', function (Blueprint $table) {
            $table->dropColumn('ocupa_horario');
            $table->unique(['fecha', 'hora'], 'citas_fecha_hora_unique');
        });
    }
};
