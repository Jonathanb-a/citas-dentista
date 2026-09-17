<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Rastro de quién restableció la contraseña de una cuenta y cuándo.
    // Va en users para cubrir por igual a pacientes y a personal.
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('password_restablecida_por')->nullable()->after('activo')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('password_restablecida_en')->nullable()->after('password_restablecida_por');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('password_restablecida_por');
            $table->dropColumn('password_restablecida_en');
        });
    }
};
