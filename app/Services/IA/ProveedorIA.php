<?php

namespace App\Services\IA;

use App\Services\IA\Datos\HerramientaIA;
use App\Services\IA\Datos\MensajeIA;
use App\Services\IA\Datos\RespuestaIA;

/**
 * Contrato de un proveedor de IA (Gemini, Groq, OpenAI, Anthropic…). El resto del
 * sistema solo conoce esta interfaz y los objetos de App\Services\IA\Datos; el
 * proveedor activo lo decide IA_PROVEEDOR (config/ia.php).
 */
interface ProveedorIA
{
    /**
     * Manda el historial (y las herramientas disponibles) y devuelve texto, llamadas a
     * herramienta o un fallo. Nunca lanza excepciones.
     *
     * @param MensajeIA[] $mensajes
     * @param HerramientaIA[] $herramientas
     */
    public function responder(array $mensajes, array $herramientas = [], ?string $instrucciones = null): RespuestaIA;
}
