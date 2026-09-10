<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Admin — Doctora
        $admin = new User();
        $admin->name      = 'Melissa';
        $admin->apellidos = 'López N.';
        $admin->email     = 'admin@dentissa.com';
        $admin->telefono  = '9991231234';
        $admin->password  = Hash::make('admin123');
        $admin->role      = 'admin';
        $admin->activo    = true;
        $admin->save();

        // Asistente
        $asistente = new User();
        $asistente->name      = 'Asistente';
        $asistente->apellidos = 'Dentissa';
        $asistente->email     = 'asistente@dentissa.com';
        $asistente->telefono  = '9991231235';
        $asistente->password  = Hash::make('asistente123');
        $asistente->role      = 'asistente';
        $asistente->activo    = true;
        $asistente->save();
    }
}