<?php

namespace App\Services\IA;

use App\Services\IA\Datos\RespuestaIA;
use Illuminate\Support\Facades\Log;

/**
 * Lo que entrega la fábrica cuando IA_PROVEEDOR está mal escrito o apunta a una clase
 * inválida: así un error de configuración no rompe la página, solo deja avisos en el log
 * y el visitante recibe el mensaje amable.
 */
final class ProveedorNoDisponible implements ProveedorIA
{
    public function __construct(private string $motivo) {}

    public function responder(array $mensajes, array $herramientas = [], ?string $instrucciones = null): RespuestaIA
    {
        Log::warning('IA: ' . $this->motivo);

        return RespuestaIA::fallo($this->motivo);
    }
}
