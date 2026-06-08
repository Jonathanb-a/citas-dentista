<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expedientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('users')->onDelete('cascade');
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('sexo', ['masculino','femenino','otro'])->nullable();
            $table->text('alergias')->nullable();
            $table->text('enfermedades')->nullable();
            $table->text('medicamentos')->nullable();
            $table->text('notas_doctora')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expedientes');
    }
};