<?php

namespace App\Services\IA;

use App\Services\IA\Datos\RespuestaIA;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Base de los proveedores que hablan por HTTPS. Aquí vive todo lo que no cambia de
 * un proveedor a otro: timeout, errores controlados y log sin secretos. Cada
 * proveedor solo traduce el formato (cuerpo de la petición e interpretación de la
 * respuesta).
 *
 * Reglas:
 * - responder() nunca lanza excepciones: ante cualquier fallo devuelve un fallo con
 *   el mensaje amable de config('ia.mensaje_error')
 * - La key nunca llega al log; tampoco los mensajes del usuario (pueden traer
 *   nombre y teléfono): solo se registra proveedor, estado HTTP y el código de error
 */
abstract class ProveedorHttp implements ProveedorIA
{
    /** @param array $config  la entrada de este proveedor en config('ia.proveedores') */
    public function __construct(protected array $config) {}

    /** Nombre corto para el log */
    abstract protected function nombre(): string;

    abstract protected function url(): string;

    /** Encabezados propios del proveedor (aquí va la key) */
    abstract protected function encabezados(string $clave): array;

    abstract protected function cuerpo(array $mensajes, array $herramientas, ?string $instrucciones): array;

    /** Convierte el JSON de la respuesta; devuelve RespuestaIA::fallo() si no sirve */
    abstract protected function interpretar(array $datos): RespuestaIA;

    final public function responder(array $mensajes, array $herramientas = [], ?string $instrucciones = null): RespuestaIA
    {
        $clave = trim((string) ($this->config['api_key'] ?? ''));

        if ($clave === '') {
            return $this->fallo('sin API key configurada');
        }

        try {
            $respuesta = $this->cliente($clave)->post($this->url(), $this->cuerpo($mensajes, $herramientas, $instrucciones));

            if ($respuesta->failed()) {
                return $this->fallo('HTTP ' . $respuesta->status() . $this->codigoError($respuesta));
            }

            $datos = $respuesta->json();

            if (!is_array($datos)) {
                return $this->fallo('respuesta que no es JSON');
            }

            $resultado = $this->interpretar($datos);

            return $resultado->falla ? $this->fallo((string) $resultado->motivo) : $resultado;
        } catch (\Throwable $e) {
            return $this->fallo(class_basename($e) . ': ' . $this->sinClave(mb_substr($e->getMessage(), 0, 200), $clave));
        }
    }

    protected function cliente(string $clave): PendingRequest
    {
        return Http::timeout((int) config('ia.timeout', 15))
            ->connectTimeout((int) config('ia.conexion', 5))
            ->withHeaders($this->encabezados($clave))
            ->acceptJson()
            ->asJson();
    }

    /** Código del error de la API (p. ej. rate_limit_exceeded); nunca su mensaje, que puede repetir lo enviado */
    protected function codigoError(Response $respuesta): string
    {
        $codigo = $respuesta->json('error.code');

        return is_string($codigo) && preg_match('/^[A-Za-z0-9_]{1,60}$/', $codigo) ? ' (' . $codigo . ')' : '';
    }

    protected function fallo(string $motivo): RespuestaIA
    {
        Log::warning('IA (' . $this->nombre() . '): ' . $motivo);

        return RespuestaIA::fallo($motivo);
    }

    private function sinClave(string $texto, string $clave): string
    {
        return str_replace($clave, '***', $texto);
    }
}
