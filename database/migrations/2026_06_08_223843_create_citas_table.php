<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('servicio_id')->constrained('servicios')->onDelete('cascade');
            $table->foreignId('horario_id')->constrained('horarios')->onDelete('cascade');
            $table->date('fecha');
            $table->time('hora');
            $table->enum('status', ['pendiente','confirmada','atendida','cancelada','reagendada'])->default('pendiente');
            $table->boolean('confirmado_por_paciente')->default(false);
            $table->timestamp('confirmacion_limite')->nullable();
            $table->text('motivo')->nullable();
            $table->text('notas_asistente')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};