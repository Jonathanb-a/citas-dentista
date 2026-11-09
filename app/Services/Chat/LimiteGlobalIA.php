<?php

namespace App\Services\Chat;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Tope diario de llamadas a la IA entre todos los visitantes. Cuenta llamadas a la IA, no
 * peticiones HTTP: quien use la IA (el chat hoy; el bucle de herramientas después) debe
 * llamar a reservar() antes de CADA llamada y, si devuelve false, no llamar.
 *
 * El contador es atómico (increment en el caché) y se reserva antes de llamar, así dos
 * visitantes a la vez no pueden pasarse del tope. El día es el natural de la zona horaria
 * del consultorio, no el de UTC.
 */
class LimiteGlobalIA
{
    public function reservar(): bool
    {
        $limite = (int) config('chat.limite_global_dia');
        $clave = $this->clave();

        try {
            Cache::add($clave, 0, now()->addDays(2));
            $usadas = Cache::increment($clave);

            if ($usadas === false) {
                // Raro: la clave expiró entre el add y el increment
                Cache::add($clave, 0, now()->addDays(2));
                $usadas = Cache::increment($clave);
            }

            if (!is_int($usadas)) {
                return false;
            }
        } catch (\Throwable $e) {
            // Sin contador fiable no se gasta cuota: el visitante recibe el mensaje amable
            Log::warning('Chat: no se pudo leer el contador global de la IA (' . class_basename($e) . ')');

            return false;
        }

        if ($usadas === $limite + 1) {
            Log::warning('Chat: se alcanzó el límite global diario de llamadas a la IA (' . $limite . ')');
        }

        return $usadas <= $limite;
    }

    public function usadas(): int
    {
        return (int) Cache::get($this->clave(), 0);
    }

    private function clave(): string
    {
        return 'chat:ia:global:' . Carbon::now((string) config('consultorio.zona_horaria'))->toDateString();
    }
}
