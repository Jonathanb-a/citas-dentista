<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\User;
use App\Models\Expediente;
use App\Models\HistorialCita;
use App\Services\DisponibilidadService;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class CitaManualService
{
    protected $disponibilidad;

    public function __construct(DisponibilidadService $disponibilidad)
    {
        $this->disponibilidad = $disponibilidad;
    }

    /**
     * Crea una cita manual (desde el panel de doctora o asistente).
     * Puede usar un paciente existente o crear uno nuevo.
     */
    public function crear(array $datos, $creadoPor)
    {
        // 1. Determinar el paciente
        if ($datos['tipo_paciente'] === 'nuevo') {
            // Crear paciente nuevo
            $paciente = User::create([
                'name' => $datos['nuevo_nombre'],
                'apellidos' => $datos['nuevo_apellidos'],
                'telefono' => $datos['nuevo_telefono'] ?? null,
                'email' => $datos['nuevo_email'],
                'password' => Hash::make('paciente123'), // contraseña temporal
                'role' => 'paciente',
                'activo' => true,
            ]);

            // Crear su expediente vacío
            Expediente::create(['paciente_id' => $paciente->id]);

            $pacienteId = $paciente->id;
        } else {
            // Usar paciente existente
            $pacienteId = $datos['paciente_id'];
        }

        // 2. Verificar disponibilidad del horario
        $espacios = $this->disponibilidad->espaciosDisponibles(
            $datos['fecha'],
            $datos['servicio_id']
        );

        $horaDisponible = collect($espacios)->contains('hora', $datos['hora']);

        if (!$horaDisponible) {
            return ['error' => 'Ese horario ya no está disponible. Por favor elige otro.'];
        }

        // 3. Calcular límite de confirmación si se crea como confirmada
        $confirmacionLimite = null;
        if ($datos['status'] === 'confirmada') {
            $confirmacionLimite = Carbon::now()->addHours(24);
        }

        // 4. Crear la cita
        try {
            $cita = Cita::create([
                'paciente_id' => $pacienteId,
                'servicio_id' => $datos['servicio_id'],
                'horario_id' => $datos['horario_id'],
                'fecha' => $datos['fecha'],
                'hora' => $datos['hora'],
                'status' => $datos['status'],
                'confirmado_por_paciente' => false,
                'confirmacion_limite' => $confirmacionLimite,
                'motivo' => $datos['motivo'] ?? null,
            ]);

            HistorialCita::create([
                'cita_id' => $cita->id,
                'user_id' => $creadoPor,
                'accion' => 'creada',
                'descripcion' => 'Cita creada manualmente por ' . auth()->user()->name . '.',
            ]);

            return ['cita' => $cita];

        } catch (\Illuminate\Database\QueryException $e) {
            return ['error' => 'Ese horario acaba de ser ocupado. Por favor elige otro.'];
        }
    }
}