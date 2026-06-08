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
        User::create([
            'name'      => 'Melissa',
            'apellidos' => 'López N.',
            'email'     => 'admin@dentissa.com',
            'telefono'  => '9991231234',
            'password'  => Hash::make('admin123'),
            'role'      => 'admin',
            'activo'    => true,
        ]);

        // Asistente
        User::create([
            'name'      => 'Asistente',
            'apellidos' => 'Dentissa',
            'email'     => 'asistente@dentissa.com',
            'telefono'  => '9991231235',
            'password'  => Hash::make('asistente123'),
            'role'      => 'asistente',
            'activo'    => true,
        ]);
    }
}