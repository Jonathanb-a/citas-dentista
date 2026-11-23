<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Una cuenta con contraseña temporal (alta por la clínica o restablecida) debe cambiarla al entrar.
    // Las cuentas que ya existen quedan en false: no se les pide nada.
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'debe_cambiar_password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('debe_cambiar_password')->default(false)->after('activo');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'debe_cambiar_password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('debe_cambiar_password');
            });
        }
    }
};
