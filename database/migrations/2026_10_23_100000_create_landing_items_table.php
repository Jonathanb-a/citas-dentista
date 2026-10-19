<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Elementos de las listas administrables de la landing (hoy: la galería).
     * Mientras una lista no se toque no tiene filas y se muestran sus originales
     * (config/landing.php, "colecciones"). Al primer cambio se guardan todos
     * como filas y de ahí en adelante manda la base de datos.
     */
    public function up(): void
    {
        Schema::create('landing_items', function (Blueprint $table) {
            $table->id();
            $table->string('coleccion', 40);
            $table->unsignedInteger('orden')->default(0);
            // Clave del elemento original del que viene (galeria-1…); null si lo agregó la doctora
            $table->string('original', 60)->nullable();
            // Ruta de la imagen subida; null = usar la imagen del elemento original
            $table->string('imagen')->nullable();
            $table->json('datos'); // campos de texto: titulo, subtitulo…
            $table->timestamps();

            $table->index(['coleccion', 'orden']);
            $table->unique(['coleccion', 'original']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_items');
    }
};
