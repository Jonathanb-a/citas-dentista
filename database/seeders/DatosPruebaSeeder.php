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
        $paciente = new User();
        $paciente->name      = 'María';
        $paciente->apellidos = 'García López';
        $paciente->email     = 'paciente@test.com';
        $paciente->telefono  = '9991112233';
        $paciente->password  = Hash::make('paciente123');
        $paciente->role      = 'paciente';
        $paciente->activo    = true;
        $paciente->save();

        // Expediente del paciente
        $expediente = Expediente::create([
            'paciente_id' => $paciente->id,
            'fecha_nacimiento' => '1995-05-15',
            'sexo' => 'femenino',
            'direccion' => 'Av. Lázaro Cárdenas 245, Col. Centro, Lázaro Cárdenas, Michoacán, C.P. 60950',
            'contacto_emergencia_nombre' => 'Jorge García López',
            'contacto_emergencia_telefono' => '7531234567',
            'tipo_sangre' => 'O+',
            'antecedentes_familiares' => 'Padre con diabetes tipo 2. Madre con hipertensión arterial.',
            'alergias' => 'Penicilina',
            'enfermedades' => 'Ninguna',
            'medicamentos' => 'Ninguno',
            'cirugias_previas' => 'Apendicectomía (2015).',
            'embarazada' => false,
            'fuma' => false,
            'alcohol' => false,
            'bruxismo' => true,
            'frecuencia_cepillado' => '2 veces al día',
            'usa_hilo_dental' => false,
            'padecimiento_actual' => 'Sensibilidad al frío en molares inferiores del lado izquierdo desde hace dos semanas.',
            'diagnostico' => 'Caries en órgano dental 36. Desgaste oclusal por bruxismo.',
            'plan_tratamiento' => "1. Resina en 36.\n2. Guarda oclusal nocturna.\n3. Técnica de cepillado e indicación de uso de hilo dental.",
            'notas_doctora' => 'Paciente refiere apretar los dientes al dormir. Revalorar en 3 meses.',
            'observaciones' => 'Prefiere citas por la tarde.',
        ]);

        // Odontograma de ejemplo, coherente con su diagnóstico
        $doctora = User::where('role', 'admin')->orderBy('id')->first();

        foreach ([
            36 => ['estado' => 'caries', 'nota' => 'Cara oclusal. Pendiente resina.'],
            16 => ['estado' => 'obturado', 'nota' => 'Resina colocada en 2023.'],
            26 => ['estado' => 'corona', 'nota' => null],
            48 => ['estado' => 'ausente', 'nota' => 'Extraído en 2022.'],
            11 => ['estado' => 'fracturado', 'nota' => 'Desgaste por bruxismo en borde incisal.'],
        ] as $numeroDiente => $diente) {
            $expediente->odontograma()->create([
                'numero_diente' => $numeroDiente,
                'estado' => $diente['estado'],
                'nota' => $diente['nota'],
                'actualizado_por' => $doctora?->id,
            ]);
        }

                Servicio::create([
            'especialidad_id' => $general->id,
            'nombre' => 'Consulta de valoración',
            'descripcion' => 'Evaluación general para diagnosticar tu molestia y definir el tratamiento adecuado. Ideal si no sabes qué necesitas.',
            'duracion_minutos' => 30,
            'activo' => true,
        ]);
    }
}