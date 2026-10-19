<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Solo guarda lo que la doctora personalizó: una fila por campo (clave y valor).
     * Los campos disponibles, sus valores originales y sus reglas viven en
     * config/landing.php; agregar uno nuevo no requiere migración.
     */
    public function up(): void
    {
        Schema::create('landing_contenidos', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 100)->unique(); // p. ej. hero.titulo
            $table->text('valor')->nullable();      // texto, o ruta de la imagen subida
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_contenidos');
    }
};
