<?php

namespace App\Services\Chat;

use Carbon\Carbon;

/**
 * Las instrucciones del sistema salen de resources/prompts/asistente.md. Los únicos valores
 * que se insertan los pone el servidor (config del consultorio y la fecha); el texto del
 * visitante nunca pasa por aquí.
 */
class InstruccionesChat
{
    public function texto(): string
    {
        $archivo = (string) config('chat.prompt');
        $plantilla = is_file($archivo) ? file_get_contents($archivo) : false;

        if ($plantilla === false || trim($plantilla) === '') {
            throw new \RuntimeException('No se encontró el archivo de instrucciones del chat.');
        }

        $ahora = Carbon::now((string) config('consultorio.zona_horaria'))->locale('es');

        // strtr hace un solo recorrido: un valor insertado no se vuelve a interpretar
        return trim(strtr($plantilla, [
            '{{nombre}}' => (string) config('consultorio.nombre'),
            '{{subtitulo}}' => (string) config('consultorio.subtitulo'),
            '{{direccion}}' => (string) config('consultorio.direccion'),
            // La hora con AM/PM como en el resto del sistema ("p. m." de es se lee peor)
            '{{fecha_hora}}' => $ahora->translatedFormat('l j \d\e F \d\e Y') . ', ' . $ahora->format('h:i A'),
        ]));
    }
}
