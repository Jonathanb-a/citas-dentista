<?php

namespace App\Services\Chat\Herramientas;

use App\Services\Chat\ContextoChat;
use App\Services\IA\Datos\HerramientaIA;

/**
 * Una herramienta que la IA puede pedir. TODO lo que llega en $argumentos lo escribió el modelo
 * (a partir de lo que dijo el visitante): se valida en el servidor y nunca se confía en ello.
 *
 * Las herramientas nunca leen ni devuelven citas ni datos de ningún paciente. Los errores se
 * devuelven como ['error' => código, 'mensaje' => texto para el modelo] sin repetir lo recibido.
 */
interface HerramientaChat
{
    public function definicion(): HerramientaIA;

    public function ejecutar(array $argumentos, ContextoChat $contexto): array;
}
