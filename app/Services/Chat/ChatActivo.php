<?php

namespace App\Services\Chat;

use App\Services\IA\ProveedorIA;

/**
 * ¿Se muestra el widget de chat en la landing? Solo si CHAT_ACTIVO no lo apaga y el proveedor
 * de IA elegido existe y tiene key: sin key el chat no podría responder, y así la landing
 * queda idéntica a la original (la regla de oro del fixture).
 */
class ChatActivo
{
    public function activo(): bool
    {
        if (!config('chat.activo')) {
            return false;
        }

        $nombre = strtolower(trim((string) config('ia.proveedor')));
        $proveedor = preg_match('/^[a-z0-9_]+$/', $nombre) ? config('ia.proveedores.' . $nombre) : null;

        return is_array($proveedor)
            && is_string($proveedor['clase'] ?? null)
            && is_subclass_of($proveedor['clase'], ProveedorIA::class)
            && trim((string) ($proveedor['api_key'] ?? '')) !== '';
    }
}
