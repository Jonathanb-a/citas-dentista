<?php

namespace App\Services;

use App\Models\Bloqueo;
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

        // Bloqueos de agenda (vacaciones, juntas, días cerrados) que tocan esta fecha
        $bloqueos = Bloqueo::queCubre($fechaCarbon)->get();

        if ($bloqueos->contains(fn ($bloqueo) => $bloqueo->esDiaCompleto())) {
            return [];
        }

        // Buscar los bloques de horario de ese día que estén disponibles
        $horarios = Horario::where('dia_semana', $diaSemana)
            ->where('disponible', true)
            ->get();

        if ($horarios->isEmpty()) {
            return [];
        }

        // Obtener las citas ya agendadas ese día (que no estén canceladas)
        // con su duración para calcular el rango ocupado completo
        $citasOcupadas = Cita::with('servicio')
            ->whereDate('fecha', $fecha)
            ->whereNotIn('status', ['cancelada'])
            ->get()
            ->map(function ($cita) use ($fecha) {
                $inicio = Carbon::parse($fecha . ' ' . $cita->hora);
                $fin = $inicio->copy()->addMinutes($cita->servicio->duracion_minutos);
                return ['inicio' => $inicio, 'fin' => $fin];
            });

        $espacios = [];

        // Por cada bloque de horario, generar los espacios según la duración
        foreach ($horarios as $horario) {
            $inicio = Carbon::parse($fecha . ' ' . $horario->hora_inicio);
            $fin = Carbon::parse($fecha . ' ' . $horario->hora_fin);

            $actual = $inicio->copy();

            while ($actual->copy()->addMinutes($duracion) <= $fin) {
                $espacioInicio = $actual->copy();
                $espacioFin = $actual->copy()->addMinutes($duracion);

                // Verificar si este espacio choca con alguna cita ocupada
                $hayChoque = $citasOcupadas->contains(function ($ocupada) use ($espacioInicio, $espacioFin) {
                    // Dos rangos se solapan si: inicioA < finB Y finA > inicioB
                    return $espacioInicio->lt($ocupada['fin']) && $espacioFin->gt($ocupada['inicio']);
                });

                // Verificar si cae dentro de un bloqueo por horas
                $estaBloqueado = $bloqueos->contains(fn ($bloqueo) => $bloqueo->chocaCon($espacioInicio, $espacioFin));

                // Verificar si es una hora pasada
                $esPasado = $fechaCarbon->isToday() && $actual->lt(Carbon::now());

                if (!$hayChoque && !$estaBloqueado && !$esPasado) {
                    $espacios[] = [
                        'hora' => $actual->format('H:i'),
                        'hora_formato' => $actual->format('h:i A'),
                        'horario_id' => $horario->id,
                    ];
                }

                $actual->addMinutes($duracion + $horario->descanso_minutos);
            }
        }

        return $espacios;
    }
}