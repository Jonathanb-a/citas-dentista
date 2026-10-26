<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Una cita cancelada ya no ocupa su horario. La columna generada vale NULL
     * cuando la cita está cancelada, y los NULL no chocan en un índice único.
     *
     * Es idempotente: en MySQL los cambios de estructura no se revierten si
     * algo falla, así que la migración puede haber corrido a medias. Cada paso
     * revisa si ya se hizo. El índice viejo y el nuevo se llaman igual, por eso
     * se distinguen por sus columnas y no solo por el nombre.
     */
    public function up(): void
    {
        $columnasNuevas = ['fecha', 'hora', 'ocupa_horario'];

        $indiceNuevoExiste = Schema::hasIndex('citas', $columnasNuevas, 'unique');

        if (Schema::hasIndex('citas', 'citas_fecha_hora_unique') && ! $indiceNuevoExiste) {
            Schema::table('citas', function (Blueprint $table) {
                $table->dropUnique('citas_fecha_hora_unique');
            });
        }

        if (! Schema::hasColumn('citas', 'ocupa_horario')) {
            Schema::table('citas', function (Blueprint $table) {
                $table->tinyInteger('ocupa_horario')->nullable()
                    ->virtualAs("CASE WHEN status = 'cancelada' THEN NULL ELSE 1 END");
            });
        }

        if (! $indiceNuevoExiste) {
            Schema::table('citas', function (Blueprint $table) use ($columnasNuevas) {
                $table->unique($columnasNuevas, 'citas_fecha_hora_unique');
            });
        }
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
