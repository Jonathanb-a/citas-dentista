<?php

namespace App\Services\IA;

/** Construye el proveedor que indica IA_PROVEEDOR (config/ia.php). Nunca lanza excepciones. */
class ProveedorIAFactory
{
    public function crear(?string $nombre = null): ProveedorIA
    {
        $nombre = strtolower(trim($nombre ?? (string) config('ia.proveedor')));

        if (!preg_match('/^[a-z0-9_]+$/', $nombre)) {
            return new ProveedorNoDisponible('IA_PROVEEDOR no es un nombre válido');
        }

        $config = config('ia.proveedores.' . $nombre);
        $clase = is_array($config) ? ($config['clase'] ?? null) : null;

        if (!is_string($clase) || !is_subclass_of($clase, ProveedorIA::class)) {
            return new ProveedorNoDisponible('proveedor "' . $nombre . '" no configurado en config/ia.php');
        }

        return new $clase($config);
    }
}
