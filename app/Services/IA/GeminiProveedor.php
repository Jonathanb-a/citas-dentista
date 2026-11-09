<?php

namespace App\Services\IA;

use App\Services\IA\Datos\HerramientaIA;
use App\Services\IA\Datos\LlamadaHerramienta;
use App\Services\IA\Datos\MensajeIA;
use App\Services\IA\Datos\RespuestaIA;

/**
 * Gemini por la Interactions API (POST /v1beta/interactions), la que Google
 * recomienda para integraciones nuevas (generateContent quedó como heredada).
 * Docs: https://ai.google.dev/gemini-api/docs/function-calling
 *
 * Va sin estado (store: false): en cada petición se manda el historial completo.
 * Los "pasos" que devuelve el modelo (pensamiento con firma, llamadas a función,
 * texto) se guardan tal cual en RespuestaIA::$crudo y se reenvían al armar el
 * historial: con los modelos Gemini 3 en adelante, omitir la firma de una llamada a
 * función es un error 400.
 */
class GeminiProveedor extends ProveedorHttp
{
    private const ORIGEN = 'gemini';

    protected function nombre(): string
    {
        return self::ORIGEN;
    }

    protected function url(): string
    {
        return (string) $this->config['url'];
    }

    protected function encabezados(string $clave): array
    {
        // La key va en un encabezado, no en la URL: así no sale en mensajes de error de conexión
        return array_filter([
            'x-goog-api-key' => $clave,
            'Api-Revision' => $this->config['revision'] ?? null,
        ]);
    }

    protected function cuerpo(array $mensajes, array $herramientas, ?string $instrucciones): array
    {
        $cuerpo = [
            'model' => (string) $this->config['modelo'],
            'store' => false,
            'input' => $this->pasos($mensajes),
        ];

        if ($instrucciones !== null && trim($instrucciones) !== '') {
            $cuerpo['system_instruction'] = $instrucciones;
        }

        if ($herramientas !== []) {
            $cuerpo['tools'] = array_map(fn (HerramientaIA $h) => [
                'type' => 'function',
                'name' => $h->nombre,
                'description' => $h->descripcion,
                'parameters' => $this->esquema($h->parametros),
            ], array_values($herramientas));
        }

        return $cuerpo;
    }

    protected function interpretar(array $datos): RespuestaIA
    {
        if (isset($datos['error'])) {
            return RespuestaIA::fallo('la API devolvió un error en una respuesta 200');
        }

        $estado = $datos['status'] ?? null;
        if (in_array($estado, ['failed', 'cancelled', 'incomplete'], true)) {
            return RespuestaIA::fallo('estado de la interacción: ' . $estado);
        }

        $pasos = $datos['steps'] ?? null;
        if (!is_array($pasos)) {
            return RespuestaIA::fallo('respuesta sin pasos');
        }

        $textos = [];
        $llamadas = [];

        foreach (array_values($pasos) as $i => $paso) {
            $tipo = $paso['type'] ?? null;

            if ($tipo === 'model_output') {
                foreach ($paso['content'] ?? [] as $parte) {
                    if (($parte['type'] ?? null) === 'text' && is_string($parte['text'] ?? null)) {
                        $textos[] = $parte['text'];
                    }
                }
            } elseif ($tipo === 'function_call' && is_string($paso['name'] ?? null)) {
                $argumentos = $paso['arguments'] ?? [];
                $llamadas[] = new LlamadaHerramienta(
                    (string) ($paso['id'] ?? 'llamada_' . $i),
                    $paso['name'],
                    is_array($argumentos) ? $argumentos : [],
                );
            }
        }

        $texto = trim(implode("\n", $textos));

        if ($llamadas !== []) {
            return RespuestaIA::conLlamadas($llamadas, $texto !== '' ? $texto : null, $pasos, self::ORIGEN);
        }

        if ($texto === '') {
            // Bloqueo de seguridad, respuesta cortada o solo "pensamiento"
            return RespuestaIA::fallo('respuesta sin texto ni llamadas');
        }

        return RespuestaIA::conTexto($texto, $pasos, self::ORIGEN);
    }

    /** Historial neutro -> pasos de entrada de la Interactions API */
    private function pasos(array $mensajes): array
    {
        $pasos = [];

        foreach ($mensajes as $mensaje) {
            if ($mensaje->rol === MensajeIA::USUARIO) {
                $pasos[] = ['type' => 'user_input', 'content' => (string) $mensaje->texto];
            } elseif ($mensaje->rol === MensajeIA::HERRAMIENTA) {
                $pasos[] = [
                    'type' => 'function_result',
                    'name' => (string) $mensaje->herramienta,
                    'call_id' => (string) $mensaje->llamadaId,
                    'result' => [['type' => 'text', 'text' => $this->comoTexto($mensaje->resultado)]],
                ];
            } elseif ($mensaje->crudo !== null && $mensaje->origen === self::ORIGEN) {
                // Los pasos originales del modelo, con sus firmas
                array_push($pasos, ...$mensaje->crudo);
            } else {
                if ($mensaje->texto !== null && $mensaje->texto !== '') {
                    $pasos[] = ['type' => 'model_output', 'content' => [['type' => 'text', 'text' => $mensaje->texto]]];
                }
                foreach ($mensaje->llamadas as $llamada) {
                    $pasos[] = [
                        'type' => 'function_call',
                        'id' => $llamada->id,
                        'name' => $llamada->nombre,
                        'arguments' => $llamada->argumentos === [] ? new \stdClass : $llamada->argumentos,
                    ];
                }
            }
        }

        return $pasos;
    }

    private function comoTexto(array|string|null $resultado): string
    {
        return is_string($resultado) ? $resultado : (string) json_encode($resultado ?? new \stdClass, JSON_UNESCAPED_UNICODE);
    }

    // Una herramienta sin parámetros se manda como objeto vacío (un array vacío se codificaría como [])
    private function esquema(array $parametros): array
    {
        $parametros += ['type' => 'object'];

        if ($parametros['type'] === 'object' && empty($parametros['properties'])) {
            $parametros['properties'] = new \stdClass;
        }

        return $parametros;
    }
}
