<?php

namespace App\Services\IA\Datos;

/**
 * Un mensaje del historial, en formato neutro (cada proveedor lo traduce al suyo).
 *
 * - usuario: lo que escribió el visitante
 * - asistente: lo que respondió la IA (texto y/o llamadas a herramienta). Se arma con
 *   RespuestaIA::comoMensaje(), que conserva los datos internos del proveedor
 * - herramienta: el resultado de ejecutar una llamada
 */
final class MensajeIA
{
    public const USUARIO = 'usuario';
    public const ASISTENTE = 'asistente';
    public const HERRAMIENTA = 'herramienta';

    /**
     * @param LlamadaHerramienta[] $llamadas
     * @param array|null $crudo  datos opacos del proveedor que hay que devolverle tal cual
     *                           (Gemini: firmas de pensamiento); solo los lee ese proveedor
     */
    private function __construct(
        public readonly string $rol,
        public readonly ?string $texto = null,
        public readonly array $llamadas = [],
        public readonly ?string $llamadaId = null,
        public readonly ?string $herramienta = null,
        public readonly array|string|null $resultado = null,
        public readonly ?array $crudo = null,
        public readonly ?string $origen = null,
    ) {}

    public static function usuario(string $texto): self
    {
        return new self(self::USUARIO, texto: $texto);
    }

    /** @param LlamadaHerramienta[] $llamadas */
    public static function asistente(?string $texto, array $llamadas = [], ?array $crudo = null, ?string $origen = null): self
    {
        return new self(self::ASISTENTE, texto: $texto, llamadas: $llamadas, crudo: $crudo, origen: $origen);
    }

    public static function resultado(LlamadaHerramienta $llamada, array|string $resultado): self
    {
        return new self(self::HERRAMIENTA, llamadaId: $llamada->id, herramienta: $llamada->nombre, resultado: $resultado);
    }
}
