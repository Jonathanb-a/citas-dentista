<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Fecha y hora "de pared" del consultorio. La app corre en UTC (config/app.php) y Railway también,
 * pero la clínica está en America/Mexico_City: "hoy" y "ahora" para las citas se calculan aquí.
 *
 * Los dos métodos devuelven un Carbon en la zona de la app con la fecha y hora LOCALES del consultorio,
 * para poder compararlos con las fechas y horas de las citas, que se guardan sin zona horaria.
 */
class Consultorio
{
    /** Hoy (a las 00:00) según el reloj del consultorio */
    public static function hoy(): Carbon
    {
        return Carbon::parse(Carbon::now(self::zona())->toDateString());
    }

    /** La hora actual según el reloj del consultorio, para comparar con la hora de un espacio */
    public static function ahora(): Carbon
    {
        return Carbon::parse(Carbon::now(self::zona())->format('Y-m-d H:i:s'));
    }

    private static function zona(): string
    {
        return (string) config('consultorio.zona_horaria');
    }
}
