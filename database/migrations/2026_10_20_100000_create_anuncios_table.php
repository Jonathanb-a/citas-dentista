<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anuncios', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 120);
            $table->string('descripcion', 300)->nullable();
            $table->string('tipo', 20); // aviso | promocion
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->boolean('activo')->default(true);
            $table->string('boton_texto', 40)->nullable();
            $table->string('boton_enlace', 255)->nullable();
            $table->string('emoji', 16)->nullable();
            $table->string('color', 20)->default('rosa');
            $table->timestamps();

            $table->index(['activo', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anuncios');
    }
};
