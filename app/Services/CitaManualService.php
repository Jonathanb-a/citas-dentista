<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\User;
use App\Models\Expediente;
use App\Models\HistorialCita;
use App\Services\DisponibilidadService;
use App\Services\PasswordTemporalService;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class CitaManualService
{
    protected $disponibilidad;
    protected $passwordTemporal;

    public function __construct(DisponibilidadService $disponibilidad, PasswordTemporalService $passwordTemporal)
    {
        $this->disponibilidad = $disponibilidad;
        $this->passwordTemporal = $passwordTemporal;
    }

    /**
     * Crea una cita manual (desde el panel de doctora o asistente).
     * Puede usar un paciente existente o crear uno nuevo.
     */
    public function crear(array $datos, $creadoPor)
    {
        // 0. Verificar disponibilidad (horarios, citas y bloqueos) antes de crear nada,
        // para no dejar un paciente nuevo huérfano si el horario no sirve
        $espacios = $this->disponibilidad->espaciosDisponibles(
            $datos['fecha'],
            $datos['servicio_id']
        );

        if (!collect($espacios)->contains('hora', $datos['hora'])) {
            return ['error' => 'Ese horario ya no está disponible. Por favor elige otro.'];
        }

        // 1. Determinar el paciente
        $panelPassword = null;

        if ($datos['tipo_paciente'] === 'nuevo') {
            // Contraseña temporal aleatoria (única por paciente)
            $passwordTemporal = $this->passwordTemporal->generar();

            $paciente = new User();
            $paciente->name = $datos['nuevo_nombre'];
            $paciente->apellidos = $datos['nuevo_apellidos'];
            $paciente->telefono = $datos['nuevo_telefono'] ?? null;
            $paciente->email = $datos['nuevo_email'];
            $paciente->sexo = $datos['nuevo_sexo'];
            $paciente->password = Hash::make($passwordTemporal);
            $paciente->role = 'paciente';   // asignado explícitamente, no por fillable
            $paciente->activo = true;
            $paciente->save();

            // Crear su expediente vacío
            Expediente::create(['paciente_id' => $paciente->id]);

            $panelPassword = $this->passwordTemporal->panel($paciente, $passwordTemporal);
            $pacienteId = $paciente->id;
        } else {
            // Usar paciente existente
            $pacienteId = $datos['paciente_id'];
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

            return ['cita' => $cita, 'password_temporal' => $panelPassword];

        } catch (\Illuminate\Database\QueryException $e) {
            return ['error' => 'Ese horario acaba de ser ocupado. Por favor elige otro.'];
        }
    }
/**
     * Reagenda una cita existente a una nueva fecha y hora.
     */
    public function reagendar($citaId, array $datos, $reagendadoPor)
    {
        $cita = Cita::findOrFail($citaId);

        // No se puede reagendar una cita atendida o cancelada
        if (in_array($cita->status, ['atendida', 'cancelada'])) {
            return ['error' => 'No se puede reagendar una cita que ya fue atendida o cancelada.'];
        }

        // Verificar disponibilidad del nuevo horario
        $espacios = $this->disponibilidad->espaciosDisponibles(
            $datos['fecha'],
            $cita->servicio_id
        );

        $horaDisponible = collect($espacios)->contains('hora', $datos['hora']);

        if (!$horaDisponible) {
            return ['error' => 'Ese horario no está disponible. Por favor elige otro.'];
        }

        // Fecha y hora anteriores, para el aviso a la clínica
        $antes = $cita->fecha->toDateString() . ' ' . Carbon::parse($cita->hora)->format('H:i');

        // Actualizar la cita con la nueva fecha y hora
        try {
            $cita->update([
                'fecha' => $datos['fecha'],
                'hora' => $datos['hora'],
                'horario_id' => $datos['horario_id'],
                'status' => 'confirmada',
                'confirmado_por_paciente' => false,
                'confirmacion_limite' => Carbon::now()->addHours(24),
            ]);

            HistorialCita::create([
                'cita_id' => $cita->id,
                'user_id' => $reagendadoPor,
                'accion' => 'reagendada',
                'descripcion' => 'Cita reagendada por ' . auth()->user()->name . ' a una nueva fecha y hora.',
            ]);

            // Aviso a la clínica, ya con la cita guardada y después de responder
            app(TelegramService::class)->enSegundoPlano(fn ($telegram) => $telegram->citaReagendada($cita, $antes));

            return ['cita' => $cita];

        } catch (\Illuminate\Database\QueryException $e) {
            return ['error' => 'Ese horario acaba de ser ocupado. Por favor elige otro.'];
        }
    }

}