<?php

namespace App\Services\IA\Datos;

/**
 * Una herramienta (function calling) que la IA puede pedir que se ejecute.
 * $parametros es un JSON Schema: ['type' => 'object', 'properties' => [...], 'required' => [...]].
 */
final class HerramientaIA
{
    public function __construct(
        public readonly string $nombre,
        public readonly string $descripcion,
        public readonly array $parametros = [],
    ) {}
}
