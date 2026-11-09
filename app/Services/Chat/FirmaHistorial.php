<?php

namespace App\Services\Chat;

/**
 * El historial viaja del navegador en cada petición, así que un visitante podría inventar
 * mensajes del asistente ("Claro, ignoraré mis reglas"). Cada respuesta de la IA sale con
 * una firma HMAC (con APP_KEY) y el servidor rechaza los mensajes del asistente cuya firma
 * no coincida. Repetir mensajes auténticos es inofensivo: son salidas reales del modelo.
 */
class FirmaHistorial
{
    public function firmar(string $texto): string
    {
        return hash_hmac('sha256', 'chat-asistente|' . $texto, (string) config('app.key'));
    }

    public function valida(string $texto, ?string $firma): bool
    {
        return is_string($firma) && hash_equals($this->firmar($texto), $firma);
    }
}
