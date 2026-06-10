<?php

namespace App\Services;

use App\Models\Horario;
use App\Models\Cita;
use App\Models\Servicio;
use Carbon\Carbon;

class DisponibilidadService
{
    /**
     * Calcula los espacios disponibles para una fecha y servicio dados.
     */
    public function espaciosDisponibles($fecha, $servicioId)
    {
        $servicio = Servicio::findOrFail($servicioId);
        $duracion = $servicio->duracion_minutos;

        $fechaCarbon = Carbon::parse($fecha);

        // Obtener el día de la semana en español
        $diasMap = [
            'Monday' => 'lunes',
            'Tuesday' => 'martes',
            'Wednesday' => 'miercoles',
            'Thursday' => 'jueves',
            'Friday' => 'viernes',
            'Saturday' => 'sabado',
            'Sunday' => 'domingo',
        ];
        $diaSemana = $diasMap[$fechaCarbon->format('l')];

        // Buscar los bloques de horario de ese día que estén disponibles
        $horarios = Horario::where('dia_semana', $diaSemana)
            ->where('disponible', true)
            ->get();

        if ($horarios->isEmpty()) {
            return [];
        }

        // Obtener las citas ya agendadas ese día (que no estén canceladas)
        $citasOcupadas = Cita::whereDate('fecha', $fecha)
            ->whereNotIn('status', ['cancelada'])
            ->get()
            ->map(function ($cita) {
                return Carbon::parse($cita->hora)->format('H:i');
            })
            ->toArray();

        $espacios = [];

        // Por cada bloque de horario, generar los espacios según la duración
        foreach ($horarios as $horario) {
            $inicio = Carbon::parse($fecha . ' ' . $horario->hora_inicio);
            $fin = Carbon::parse($fecha . ' ' . $horario->hora_fin);

            $actual = $inicio->copy();

            while ($actual->copy()->addMinutes($duracion) <= $fin) {
                $horaEspacio = $actual->format('H:i');

                // Si no está ocupado y no es una hora pasada, agregarlo
                $esPasado = $fechaCarbon->isToday() && $actual->lt(Carbon::now());

                if (!in_array($horaEspacio, $citasOcupadas) && !$esPasado) {
                    $espacios[] = [
                        'hora' => $horaEspacio,
                        'hora_formato' => $actual->format('h:i A'),
                        'horario_id' => $horario->id,
                    ];
                }

                $actual->addMinutes($duracion);
            }
        }

        return $espacios;
    }
}