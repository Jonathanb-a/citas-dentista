<?php

namespace App\Services;

use App\Models\Bloqueo;
use App\Models\Horario;
use App\Models\Cita;
use App\Models\Servicio;
use Carbon\Carbon;
use App\Support\Consultorio;

class DisponibilidadService
{
    /**
     * Busca el espacio de una hora y, ADEMÁS, serializa a quienes agendan a la vez.
     *
     * Debe llamarse como PRIMERA consulta dentro de una DB::transaction: bloquea los bloques de horario
     * de ese día de la semana (FOR UPDATE, en orden fijo para no producir interbloqueos), así que dos
     * personas que reservan casi al mismo instante hacen fila. La segunda vuelve a calcular la
     * disponibilidad ya con la cita de la primera guardada y ve el choque. Sin esto, el índice único
     * solo detecta dos citas con el MISMO inicio, no servicios de distinta duración que se traslapan.
     *
     * Devuelve el espacio libre ['hora', 'hora_formato', 'horario_id'] o null si ya no existe.
     */
    public function espacioConBloqueo($fecha, $servicioId, string $hora, $ignorarCitaId = null): ?array
    {
        $dia = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'][Carbon::parse($fecha)->dayOfWeekIso - 1];

        Horario::where('dia_semana', $dia)->orderBy('id')->lockForUpdate()->get();

        return collect($this->espaciosDisponibles($fecha, $servicioId, $ignorarCitaId))->firstWhere('hora', $hora);
    }

    /**
     * Calcula los espacios disponibles para una fecha y servicio dados.
     */
    public function espaciosDisponibles($fecha, $servicioId, $ignorarCitaId = null)
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
            // Al reagendar, la cita que se mueve no debe estorbarse a sí misma
            ->when($ignorarCitaId, fn ($q) => $q->where('id', '!=', $ignorarCitaId))
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
                // (hoy y ahora, con el reloj del consultorio: la app corre en UTC)
                $esPasado = $fechaCarbon->toDateString() === Consultorio::hoy()->toDateString()
                    && $actual->lt(Consultorio::ahora());

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