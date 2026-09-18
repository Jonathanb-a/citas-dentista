<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nota de evolución: qué se hizo en una consulta.
     * Una nota por cita, por eso cita_id es único: así "guardar",
     * "agregar después" y "editar" son la misma operación.
     */
    public function up(): void
    {
        Schema::create('notas_evolucion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->unique()->constrained('citas')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('users')->cascadeOnDelete();
            $table->text('procedimiento')->nullable();
            $table->text('observaciones')->nullable();
            $table->text('indicaciones')->nullable();
            $table->string('proxima_cita_sugerida')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_evolucion');
    }
};
