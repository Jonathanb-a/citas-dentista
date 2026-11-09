<?php

namespace App\Services\IA\Datos;

/** La IA pide ejecutar una herramienta con estos argumentos. El $id enlaza la llamada con su resultado. */
final class LlamadaHerramienta
{
    public function __construct(
        public readonly string $id,
        public readonly string $nombre,
        public readonly array $argumentos = [],
    ) {}
}
