<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Historia clínica odontológica (NOM-013-SSA2) y trazabilidad de cambios
    public function up(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            // Identificación
            $table->string('direccion')->nullable()->after('sexo');
            $table->string('contacto_emergencia_nombre')->nullable()->after('direccion');
            $table->string('contacto_emergencia_telefono', 20)->nullable()->after('contacto_emergencia_nombre');
            $table->string('tipo_sangre', 5)->nullable()->after('contacto_emergencia_telefono');

            // Antecedentes heredofamiliares y patológicos
            $table->text('antecedentes_familiares')->nullable()->after('tipo_sangre');
            $table->text('cirugias_previas')->nullable()->after('medicamentos');
            $table->boolean('embarazada')->nullable()->after('cirugias_previas');

            // Hábitos
            $table->boolean('fuma')->nullable()->after('embarazada');
            $table->boolean('alcohol')->nullable()->after('fuma');
            $table->boolean('bruxismo')->nullable()->after('alcohol');
            $table->string('frecuencia_cepillado', 50)->nullable()->after('bruxismo');
            $table->boolean('usa_hilo_dental')->nullable()->after('frecuencia_cepillado');

            // Clínico
            $table->text('padecimiento_actual')->nullable()->after('usa_hilo_dental');
            $table->text('diagnostico')->nullable()->after('padecimiento_actual');
            $table->text('plan_tratamiento')->nullable()->after('diagnostico');

            // Trazabilidad
            $table->foreignId('actualizado_por')->nullable()->after('observaciones')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('ultima_actualizacion')->nullable()->after('actualizado_por');
        });
    }

    public function down(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('actualizado_por');
            $table->dropColumn([
                'direccion',
                'contacto_emergencia_nombre',
                'contacto_emergencia_telefono',
                'tipo_sangre',
                'antecedentes_familiares',
                'cirugias_previas',
                'embarazada',
                'fuma',
                'alcohol',
                'bruxismo',
                'frecuencia_cepillado',
                'usa_hilo_dental',
                'padecimiento_actual',
                'diagnostico',
                'plan_tratamiento',
                'ultima_actualizacion',
            ]);
        });
    }
};
