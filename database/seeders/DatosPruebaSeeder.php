<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Especialidad;
use App\Models\Servicio;
use App\Models\Horario;
use App\Models\User;
use App\Models\Expediente;
use Illuminate\Support\Facades\Hash;

class DatosPruebaSeeder extends Seeder
{
    public function run(): void
    {
        // ESPECIALIDADES
        $general = Especialidad::create([
            'nombre' => 'Odontología General',
            'descripcion' => 'Tratamientos dentales generales y preventivos.',
            'activo' => true,
        ]);

        $ortodoncia = Especialidad::create([
            'nombre' => 'Ortodoncia',
            'descripcion' => 'Corrección de la posición de los dientes.',
            'activo' => true,
        ]);

        // SERVICIOS de Odontología General
        Servicio::create([
            'especialidad_id' => $general->id,
            'nombre' => 'Limpieza dental',
            'descripcion' => 'Limpieza profunda y eliminación de sarro.',
            'duracion_minutos' => 45,
            'activo' => true,
        ]);

        Servicio::create([
            'especialidad_id' => $general->id,
            'nombre' => 'Eliminación de caries',
            'descripcion' => 'Tratamiento de caries dental.',
            'duracion_minutos' => 30,
            'activo' => true,
        ]);

        Servicio::create([
            'especialidad_id' => $general->id,
            'nombre' => 'Extracción',
            'descripcion' => 'Extracción de pieza dental.',
            'duracion_minutos' => 45,
            'activo' => true,
        ]);

        Servicio::create([
            'especialidad_id' => $general->id,
            'nombre' => 'Colocación de resinas',
            'descripcion' => 'Reconstrucción dental con resina.',
            'duracion_minutos' => 40,
            'activo' => true,
        ]);

        // SERVICIOS de Ortodoncia
        Servicio::create([
            'especialidad_id' => $ortodoncia->id,
            'nombre' => 'Brackets',
            'descripcion' => 'Colocación y ajuste de brackets.',
            'duracion_minutos' => 60,
            'activo' => true,
        ]);

        Servicio::create([
            'especialidad_id' => $ortodoncia->id,
            'nombre' => 'Ortodoncia maxilar',
            'descripcion' => 'Tratamiento de ortodoncia para niños.',
            'duracion_minutos' => 60,
            'activo' => true,
        ]);

        // HORARIOS (Lunes a Viernes 9-17, Sábado 9-14)
        $diasSemana = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
        foreach ($diasSemana as $dia) {
            Horario::create([
                'dia_semana' => $dia,
                'hora_inicio' => '09:00',
                'hora_fin' => '17:00',
                'disponible' => true,
            ]);
        }

        Horario::create([
            'dia_semana' => 'sabado',
            'hora_inicio' => '09:00',
            'hora_fin' => '14:00',
            'disponible' => true,
        ]);

        // PACIENTE de prueba
        $paciente = User::create([
            'name' => 'María',
            'apellidos' => 'García López',
            'email' => 'paciente@test.com',
            'telefono' => '9991112233',
            'password' => Hash::make('paciente123'),
            'role' => 'paciente',
            'activo' => true,
        ]);

        // Expediente del paciente
        Expediente::create([
            'paciente_id' => $paciente->id,
            'fecha_nacimiento' => '1995-05-15',
            'sexo' => 'femenino',
            'alergias' => 'Penicilina',
            'enfermedades' => 'Ninguna',
            'medicamentos' => 'Ninguno',
        ]);
    }
}