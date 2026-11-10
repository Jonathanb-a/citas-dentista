<?php

namespace App\Services\Chat;

use App\Models\Horario;
use Carbon\Carbon;

/**
 * Horario general de atención para las instrucciones del chat, leído de la tabla `horarios`
 * (la del módulo Horarios del panel, la misma que usa DisponibilidadService para ofrecer citas:
 * así el asistente nunca contradice los espacios que puede agendar). Solo los bloques
 * `disponible`; los días consecutivos con el mismo horario se juntan. Los bloqueos (vacaciones,
 * días cerrados) no entran: para un día concreto está ver_horarios_libres.
 *
 * El texto sale solo de columnas de la base (enum de día y horas): no hay texto libre que limpiar.
 */
class HorarioConsultorio
{
    private const DIAS = [
        'lunes' => 'lunes', 'martes' => 'martes', 'miercoles' => 'miércoles', 'jueves' => 'jueves',
        'viernes' => 'viernes', 'sabado' => 'sábado', 'domingo' => 'domingo',
    ];

    public function resumen(): string
    {
        try {
            $filas = Horario::where('disponible', true)->get(['dia_semana', 'hora_inicio', 'hora_fin']);
        } catch (\Throwable $e) {
            return $this->sinDatos();
        }

        if ($filas->isEmpty()) {
            return $this->sinDatos();
        }

        // Bloques de cada día, ordenados y sin repetidos
        $porDia = [];
        foreach (array_keys(self::DIAS) as $dia) {
            $bloques = $filas->where('dia_semana', $dia)
                ->sortBy(fn ($h) => Carbon::parse($h->hora_inicio)->format('H:i'))
                ->map(fn ($h) => Carbon::parse($h->hora_inicio)->format('g:i A') . ' a ' . Carbon::parse($h->hora_fin)->format('g:i A'))
                ->unique()
                ->values()
                ->all();

            $porDia[$dia] = $bloques === [] ? 'cerrado' : implode(' y ', $bloques);
        }

        // Rachas de días consecutivos con el mismo horario
        $lineas = [];
        $dias = array_keys($porDia);

        for ($i = 0; $i < count($dias);) {
            $j = $i;
            while ($j + 1 < count($dias) && $porDia[$dias[$j + 1]] === $porDia[$dias[$i]]) {
                $j++;
            }

            $lineas[] = '- ' . $this->etiqueta($dias[$i], $dias[$j], $j - $i + 1) . ': ' . $porDia[$dias[$i]];
            $i = $j + 1;
        }

        return implode("\n", $lineas);
    }

    private function etiqueta(string $desde, string $hasta, int $cuantos): string
    {
        $nombre = ucfirst(self::DIAS[$desde]);

        return match (true) {
            $cuantos === 1 => $nombre,
            $cuantos === 2 => $nombre . ' y ' . self::DIAS[$hasta],
            default => $nombre . ' a ' . self::DIAS[$hasta],
        };
    }

    private function sinDatos(): string
    {
        return '(el horario de atención lo confirma el consultorio)';
    }
}
