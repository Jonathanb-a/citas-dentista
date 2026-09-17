<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un registro por diente marcado. Los dientes sanos no se guardan:
     * la ausencia de registro significa sano.
     *
     * numero_diente usa notación FDI. Hoy solo se capturan los 32 dientes
     * permanentes (11-18, 21-28, 31-38, 41-48), pero los temporales
     * (51-55, 61-65, 71-75, 81-85) caben en esta misma columna sin cambios:
     * la fase 3 solo tendrá que ampliar la lista de números válidos del modelo.
     */
    public function up(): void
    {
        Schema::create('odontograma', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->constrained('expedientes')->cascadeOnDelete();
            $table->smallInteger('numero_diente');
            $table->string('estado');
            $table->text('nota')->nullable();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Un diente no puede tener dos estados en el mismo expediente
            $table->unique(['expediente_id', 'numero_diente']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odontograma');
    }
};
