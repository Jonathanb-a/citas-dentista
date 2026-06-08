<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('apellidos')->after('name');
            $table->string('telefono')->nullable()->after('apellidos');
            $table->date('fecha_nacimiento')->nullable()->after('telefono');
            $table->enum('sexo', ['masculino','femenino','otro'])->nullable()->after('fecha_nacimiento');
            $table->enum('role', ['admin','asistente','paciente'])->default('paciente')->after('sexo');
            $table->boolean('activo')->default(true)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['apellidos','telefono','fecha_nacimiento','sexo','role','activo']);
        });
    }
};