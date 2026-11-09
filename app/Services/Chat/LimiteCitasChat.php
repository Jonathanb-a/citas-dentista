<?php

namespace App\Services\Chat;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Tope de citas agendadas por chat al día: por IP y en total. Un chat público baja mucho la
 * barrera para llenar la agenda con citas falsas. El contador es atómico y se reserva ANTES
 * de crear la cita; si la creación falla, se libera. El día es el de la zona del consultorio.
 */
class LimiteCitasChat
{
    /** @return string|null null si se reservó; 'ip', 'global' o 'error' si no se puede agendar */
    public function reservar(string $ip): ?string
    {
        $claveIp = $this->claveIp($ip);
        $claveGlobal = $this->claveGlobal();

        try {
            if ($this->incrementar($claveIp) > (int) config('chat.agenda.limite_ip_dia')) {
                Cache::decrement($claveIp);

                return 'ip';
            }

            if ($this->incrementar($claveGlobal) > (int) config('chat.agenda.limite_dia')) {
                Cache::decrement($claveGlobal);
                Cache::decrement($claveIp);

                return 'global';
            }
        } catch (\Throwable $e) {
            Log::warning('Chat: no se pudo leer el contador de citas por chat (' . class_basename($e) . ')');

            return 'error';
        }

        return null;
    }

    public function liberar(string $ip): void
    {
        try {
            Cache::decrement($this->claveIp($ip));
            Cache::decrement($this->claveGlobal());
        } catch (\Throwable $e) {
            Log::warning('Chat: no se pudo liberar el contador de citas por chat (' . class_basename($e) . ')');
        }
    }

    public function usadasGlobal(): int
    {
        return (int) Cache::get($this->claveGlobal(), 0);
    }

    public function usadasIp(string $ip): int
    {
        return (int) Cache::get($this->claveIp($ip), 0);
    }

    private function incrementar(string $clave): int
    {
        Cache::add($clave, 0, now()->addDays(2));
        $valor = Cache::increment($clave);

        if ($valor === false) {
            Cache::add($clave, 0, now()->addDays(2));
            $valor = Cache::increment($clave);
        }

        if (!is_int($valor)) {
            throw new \RuntimeException('contador no disponible');
        }

        return $valor;
    }

    private function dia(): string
    {
        return Carbon::now((string) config('consultorio.zona_horaria'))->toDateString();
    }

    private function claveIp(string $ip): string
    {
        return 'chat:citas:ip:' . md5($ip) . ':' . $this->dia();
    }

    private function claveGlobal(): string
    {
        return 'chat:citas:global:' . $this->dia();
    }
}
