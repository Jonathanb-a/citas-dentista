<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotente: un despliegue repetido no debe fallar si la tabla ya existe
        if (Schema::hasTable('testimonios')) {
            return;
        }

        Schema::create('testimonios', function (Blueprint $table) {
            $table->id();
            // Un testimonio por paciente (índice único)
            $table->foreignId('paciente_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('calificacion');
            $table->text('texto');
            // Evidencia de la casilla "Autorizo que se muestre mi nombre y mi opinión"
            $table->boolean('autorizado')->default(false);
            // pendiente | aprobado | oculto
            $table->string('estado', 12)->default('pendiente');
            $table->boolean('destacado')->default(false);
            $table->timestamp('aprobado_en')->nullable();
            $table->timestamps();

            $table->index('estado');
            $table->index(['estado', 'destacado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonios');
    }
};
