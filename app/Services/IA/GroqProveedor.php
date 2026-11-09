<?php

namespace App\Services\IA;

use App\Services\IA\Datos\HerramientaIA;
use App\Services\IA\Datos\LlamadaHerramienta;
use App\Services\IA\Datos\MensajeIA;
use App\Services\IA\Datos\RespuestaIA;
use Illuminate\Http\Client\Response;

/**
 * Groq por su API compatible con OpenAI (POST /openai/v1/chat/completions).
 * Docs: https://console.groq.com/docs/tool-use
 *
 * Es el formato "chat completions": mensajes system / user / assistant / tool, las
 * herramientas como {type: function, function: {...}} y los argumentos de cada
 * llamada como cadena JSON. No hay datos opacos que reenviar (a diferencia de las
 * firmas de Gemini), así que el historial se reconstruye siempre desde el formato neutro.
 *
 * Nunca se manda `name` en los mensajes: Groq lo rechaza con 400 (messages[].name) y
 * `tool_call_id` basta para enlazar cada resultado con su llamada.
 */
class GroqProveedor extends ProveedorHttp
{
    protected function nombre(): string
    {
        return 'groq';
    }

    protected function url(): string
    {
        return (string) $this->config['url'];
    }

    protected function encabezados(string $clave): array
    {
        // La key va en un encabezado, no en la URL
        return ['Authorization' => 'Bearer ' . $clave];
    }

    protected function cuerpo(array $mensajes, array $herramientas, ?string $instrucciones): array
    {
        $modelo = (string) $this->config['modelo'];

        $cuerpo = [
            'model' => $modelo,
            'messages' => $this->mensajes($mensajes, $instrucciones),
        ];

        if (!empty($this->config['max_tokens'])) {
            $cuerpo['max_completion_tokens'] = (int) $this->config['max_tokens'];
        }

        // Solo los modelos gpt-oss aceptan reasoning_effort; en otros daría error 400
        if (!empty($this->config['reasoning_effort']) && str_starts_with($modelo, 'openai/gpt-oss')) {
            $cuerpo['reasoning_effort'] = $this->config['reasoning_effort'];
        }

        if ($herramientas !== []) {
            $cuerpo['tools'] = array_map(fn (HerramientaIA $h) => [
                'type' => 'function',
                'function' => [
                    'name' => $h->nombre,
                    'description' => $h->descripcion,
                    'parameters' => $this->esquema($h->parametros),
                ],
            ], array_values($herramientas));
            $cuerpo['tool_choice'] = 'auto';
        }

        return $cuerpo;
    }

    protected function interpretar(array $datos): RespuestaIA
    {
        if (isset($datos['error'])) {
            return RespuestaIA::fallo('la API devolvió un error en una respuesta 200');
        }

        $eleccion = $datos['choices'][0] ?? null;
        $mensaje = is_array($eleccion) ? ($eleccion['message'] ?? null) : null;

        if (!is_array($mensaje)) {
            return RespuestaIA::fallo('respuesta sin choices');
        }

        // message.reasoning (el razonamiento del modelo) se ignora: nunca llega al visitante
        $texto = is_string($mensaje['content'] ?? null) ? trim($mensaje['content']) : '';
        $llamadas = [];

        foreach (array_values($mensaje['tool_calls'] ?? []) as $i => $llamada) {
            $nombre = $llamada['function']['name'] ?? null;

            if (!is_string($nombre) || $nombre === '') {
                continue;
            }

            $argumentos = $llamada['function']['arguments'] ?? '';
            $decodificados = is_string($argumentos) && trim($argumentos) !== '' ? json_decode($argumentos, true) : [];

            if (!is_array($decodificados)) {
                return RespuestaIA::fallo('argumentos de herramienta que no son JSON válido');
            }

            $llamadas[] = new LlamadaHerramienta((string) ($llamada['id'] ?? 'llamada_' . $i), $nombre, $decodificados);
        }

        if ($llamadas !== []) {
            return RespuestaIA::conLlamadas($llamadas, $texto !== '' ? $texto : null);
        }

        if ($texto === '') {
            $motivo = $eleccion['finish_reason'] ?? null;

            return RespuestaIA::fallo('respuesta sin texto ni llamadas'
                . (is_string($motivo) && preg_match('/^[a-z_]{1,30}$/', $motivo) ? ' (finish_reason: ' . $motivo . ')' : ''));
        }

        return RespuestaIA::conTexto($texto);
    }

    // Groq describe el error con `type` (invalid_request_error…); su `message` puede repetir lo enviado
    protected function codigoError(Response $respuesta): string
    {
        $codigo = $respuesta->json('error.type') ?? $respuesta->json('error.code');

        return is_string($codigo) && preg_match('/^[A-Za-z0-9_]{1,60}$/', $codigo) ? ' (' . $codigo . ')' : '';
    }

    /** Historial neutro -> mensajes de chat completions */
    private function mensajes(array $mensajes, ?string $instrucciones): array
    {
        $salida = [];

        if ($instrucciones !== null && trim($instrucciones) !== '') {
            $salida[] = ['role' => 'system', 'content' => $instrucciones];
        }

        foreach ($mensajes as $mensaje) {
            if ($mensaje->rol === MensajeIA::USUARIO) {
                $salida[] = ['role' => 'user', 'content' => (string) $mensaje->texto];
            } elseif ($mensaje->rol === MensajeIA::HERRAMIENTA) {
                $salida[] = [
                    'role' => 'tool',
                    'tool_call_id' => (string) $mensaje->llamadaId,
                    'content' => $this->comoTexto($mensaje->resultado),
                ];
            } else {
                $asistente = [
                    'role' => 'assistant',
                    'content' => $mensaje->texto !== null && $mensaje->texto !== '' ? $mensaje->texto : null,
                ];

                if ($mensaje->llamadas !== []) {
                    $asistente['tool_calls'] = array_map(fn (LlamadaHerramienta $l) => [
                        'id' => $l->id,
                        'type' => 'function',
                        'function' => [
                            'name' => $l->nombre,
                            // Los argumentos viajan como cadena JSON; sin argumentos, "{}" y no "[]"
                            'arguments' => $this->json($l->argumentos === [] ? new \stdClass : $l->argumentos),
                        ],
                    ], array_values($mensaje->llamadas));
                }

                $salida[] = $asistente;
            }
        }

        return $salida;
    }

    private function comoTexto(array|string|null $resultado): string
    {
        return is_string($resultado) ? $resultado : $this->json($resultado ?? new \stdClass);
    }

    private function json(mixed $valor): string
    {
        return (string) json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
