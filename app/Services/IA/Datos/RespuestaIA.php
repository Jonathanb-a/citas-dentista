<?php

namespace App\Services\IA\Datos;

/**
 * Lo que devuelve un proveedor: texto, llamadas a herramienta o un fallo.
 *
 * En un fallo, $texto trae el mensaje amable listo para mostrar al usuario y
 * $motivo la causa técnica (solo para el log y las pruebas, nunca para el usuario).
 * Un fallo no se agrega al historial.
 */
final class RespuestaIA
{
    /** @param LlamadaHerramienta[] $llamadas */
    private function __construct(
        public readonly ?string $texto,
        public readonly array $llamadas = [],
        public readonly ?array $crudo = null,
        public readonly ?string $origen = null,
        public readonly bool $falla = false,
        public readonly ?string $motivo = null,
    ) {}

    public static function conTexto(string $texto, ?array $crudo = null, ?string $origen = null): self
    {
        return new self($texto, crudo: $crudo, origen: $origen);
    }

    /** @param LlamadaHerramienta[] $llamadas */
    public static function conLlamadas(array $llamadas, ?string $texto = null, ?array $crudo = null, ?string $origen = null): self
    {
        return new self($texto, $llamadas, $crudo, $origen);
    }

    public static function fallo(string $motivo): self
    {
        return new self((string) config('ia.mensaje_error'), falla: true, motivo: $motivo);
    }

    public function pideHerramientas(): bool
    {
        return !$this->falla && $this->llamadas !== [];
    }

    /** El mensaje que se agrega al historial antes de mandar los resultados de las herramientas */
    public function comoMensaje(): MensajeIA
    {
        return MensajeIA::asistente($this->texto, $this->llamadas, $this->crudo, $this->origen);
    }
}
