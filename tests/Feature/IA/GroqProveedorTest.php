<?php

namespace Tests\Feature\IA;

use App\Services\IA\Datos\HerramientaIA;
use App\Services\IA\Datos\LlamadaHerramienta;
use App\Services\IA\Datos\MensajeIA;
use App\Services\IA\GroqProveedor;
use App\Services\IA\ProveedorIA;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Todo con Http::fake(): ninguna prueba llama a la IA real */
class GroqProveedorTest extends TestCase
{
    private const KEY = 'gsk_CLAVE-SECRETA-123';
    private const URL = 'https://api.groq.com/openai/v1/chat/completions';

    /** @var string[] */
    private array $log = [];

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config([
            'logging.default' => 'null',
            'ia.proveedor' => 'groq',
            'ia.proveedores.groq.api_key' => self::KEY,
            'ia.proveedores.groq.modelo' => 'openai/gpt-oss-120b',
        ]);

        Event::listen(MessageLogged::class, fn (MessageLogged $e) => $this->log[] = $e->message . ' ' . json_encode($e->context));
    }

    private function proveedor(): ProveedorIA
    {
        return app(ProveedorIA::class);
    }

    private function fingir(array $cuerpo, int $estado = 200): void
    {
        Http::fake(['api.groq.com/*' => Http::response($cuerpo, $estado)]);
    }

    private function respuestaTexto(string $texto = 'Hola, ¿en qué te ayudo?'): array
    {
        return ['choices' => [['index' => 0, 'finish_reason' => 'stop',
            'message' => ['role' => 'assistant', 'content' => $texto, 'reasoning' => 'razonamiento interno que no debe salir']]]];
    }

    private function respuestaLlamada(string $argumentos = '{"fecha":"2026-12-01"}'): array
    {
        return ['choices' => [['index' => 0, 'finish_reason' => 'tool_calls', 'message' => [
            'role' => 'assistant',
            'content' => null,
            'tool_calls' => [['id' => 'call_1', 'type' => 'function', 'function' => ['name' => 'ver_horarios', 'arguments' => $argumentos]]],
        ]]]];
    }

    private function herramienta(): HerramientaIA
    {
        return new HerramientaIA('ver_horarios', 'Consulta los horarios libres de un día', [
            'type' => 'object',
            'properties' => ['fecha' => ['type' => 'string', 'description' => 'Día en formato AAAA-MM-DD']],
            'required' => ['fecha'],
        ]);
    }

    private function log(): string
    {
        return implode("\n", $this->log);
    }

    // ---------- Petición ----------

    public function test_texto_simple_manda_modelo_instrucciones_y_key_en_encabezado(): void
    {
        $this->fingir($this->respuestaTexto());

        $respuesta = $this->proveedor()->responder([MensajeIA::usuario('Hola')], [], 'Eres la asistente de Dentissa.');

        $this->assertFalse($respuesta->falla);
        $this->assertSame('Hola, ¿en qué te ayudo?', $respuesta->texto);
        $this->assertFalse($respuesta->pideHerramientas());

        Http::assertSentCount(1);
        Http::assertSent(function (Request $r) {
            return $r->method() === 'POST'
                && $r->url() === self::URL
                && $r->header('Authorization') === ['Bearer ' . self::KEY]
                && $r['model'] === 'openai/gpt-oss-120b'
                && $r['messages'] === [
                    ['role' => 'system', 'content' => 'Eres la asistente de Dentissa.'],
                    ['role' => 'user', 'content' => 'Hola'],
                ]
                && $r['max_completion_tokens'] === 1024
                && $r['reasoning_effort'] === 'low'
                && !isset($r['tools'])
                && !isset($r['tool_choice'])
                && !str_contains($r->url(), self::KEY)
                && !str_contains($r->body(), self::KEY);
        });
    }

    public function test_el_razonamiento_del_modelo_no_llega_al_visitante(): void
    {
        $this->fingir($this->respuestaTexto());

        $respuesta = $this->proveedor()->responder([MensajeIA::usuario('Hola')]);

        $this->assertStringNotContainsString('razonamiento interno', (string) $respuesta->texto);
    }

    public function test_sin_instrucciones_no_manda_mensaje_system(): void
    {
        $this->fingir($this->respuestaTexto());

        $this->proveedor()->responder([MensajeIA::usuario('Hola')]);

        Http::assertSent(fn (Request $r) => $r['messages'] === [['role' => 'user', 'content' => 'Hola']]);
    }

    public function test_reasoning_effort_solo_se_manda_a_modelos_gpt_oss(): void
    {
        config(['ia.proveedores.groq.modelo' => 'llama-3.3-70b-versatile']);
        $this->fingir($this->respuestaTexto());

        $this->proveedor()->responder([MensajeIA::usuario('Hola')]);

        Http::assertSent(fn (Request $r) => !isset($r['reasoning_effort']) && $r['model'] === 'llama-3.3-70b-versatile');
    }

    public function test_reasoning_effort_es_configurable_y_puede_apagarse(): void
    {
        config(['ia.proveedores.groq.reasoning_effort' => 'high']);
        $this->fingir($this->respuestaTexto());
        $this->proveedor()->responder([MensajeIA::usuario('Hola')]);
        Http::assertSent(fn (Request $r) => $r['reasoning_effort'] === 'high');

        config(['ia.proveedores.groq.reasoning_effort' => null]);
        Http::fake(['api.groq.com/*' => Http::response($this->respuestaTexto())]);
        $this->proveedor()->responder([MensajeIA::usuario('Otra vez')]);
        Http::assertSent(fn (Request $r) => $r['messages'] === [['role' => 'user', 'content' => 'Otra vez']] && !isset($r['reasoning_effort']));
    }

    public function test_con_herramientas_manda_su_definicion_y_devuelve_la_llamada_con_argumentos_decodificados(): void
    {
        $this->fingir($this->respuestaLlamada());

        $respuesta = $this->proveedor()->responder([MensajeIA::usuario('¿Hay lugar el 1 de diciembre?')], [$this->herramienta()]);

        $this->assertFalse($respuesta->falla);
        $this->assertTrue($respuesta->pideHerramientas());
        $this->assertNull($respuesta->texto);
        $this->assertCount(1, $respuesta->llamadas);
        $this->assertSame('call_1', $respuesta->llamadas[0]->id);
        $this->assertSame('ver_horarios', $respuesta->llamadas[0]->nombre);
        $this->assertSame(['fecha' => '2026-12-01'], $respuesta->llamadas[0]->argumentos);

        Http::assertSent(fn (Request $r) => $r['tool_choice'] === 'auto' && $r['tools'] === [[
            'type' => 'function',
            'function' => [
                'name' => 'ver_horarios',
                'description' => 'Consulta los horarios libres de un día',
                'parameters' => [
                    'type' => 'object',
                    'properties' => ['fecha' => ['type' => 'string', 'description' => 'Día en formato AAAA-MM-DD']],
                    'required' => ['fecha'],
                ],
            ],
        ]]);
    }

    public function test_varias_llamadas_y_texto_en_la_misma_respuesta(): void
    {
        $this->fingir(['choices' => [['finish_reason' => 'tool_calls', 'message' => [
            'content' => 'Reviso dos cosas',
            'tool_calls' => [
                ['id' => 'a', 'type' => 'function', 'function' => ['name' => 'ver_horarios', 'arguments' => '{"fecha":"2026-12-01"}']],
                ['id' => 'b', 'type' => 'function', 'function' => ['name' => 'ver_servicios', 'arguments' => '']],
            ],
        ]]]]);

        $respuesta = $this->proveedor()->responder([MensajeIA::usuario('Hola')], [$this->herramienta()]);

        $this->assertSame('Reviso dos cosas', $respuesta->texto);
        $this->assertSame(['a', 'b'], array_map(fn ($l) => $l->id, $respuesta->llamadas));
        $this->assertSame([], $respuesta->llamadas[1]->argumentos);
    }

    public function test_una_herramienta_sin_parametros_se_manda_como_objeto_vacio(): void
    {
        $this->fingir($this->respuestaTexto());

        $this->proveedor()->responder([MensajeIA::usuario('Hola')], [new HerramientaIA('ver_servicios', 'Lista los servicios')]);

        Http::assertSent(fn (Request $r) => str_contains($r->body(), '"parameters":{"type":"object","properties":{}}'));
    }

    public function test_el_historial_con_resultados_de_herramientas_viaja_en_formato_chat_completions(): void
    {
        Http::fake(['api.groq.com/*' => Http::sequence()
            ->push($this->respuestaLlamada())
            ->push($this->respuestaTexto('Sí, hay lugar a las 10:00.'))]);

        $primera = $this->proveedor()->responder([MensajeIA::usuario('¿Hay lugar?')], [$this->herramienta()]);
        $segunda = $this->proveedor()->responder([
            MensajeIA::usuario('¿Hay lugar?'),
            $primera->comoMensaje(),
            MensajeIA::resultado($primera->llamadas[0], ['horas' => ['10:00', '11:00']]),
        ], [$this->herramienta()]);

        $this->assertSame('Sí, hay lugar a las 10:00.', $segunda->texto);

        Http::assertSent(fn (Request $r) => ($r['messages'][1]['role'] ?? null) === 'assistant' && $r['messages'] === [
            ['role' => 'user', 'content' => '¿Hay lugar?'],
            ['role' => 'assistant', 'content' => null, 'tool_calls' => [[
                'id' => 'call_1',
                'type' => 'function',
                'function' => ['name' => 'ver_horarios', 'arguments' => '{"fecha":"2026-12-01"}'],
            ]]],
            // Sin `name`: Groq lo rechaza con 400
            ['role' => 'tool', 'tool_call_id' => 'call_1', 'content' => '{"horas":["10:00","11:00"]}'],
        ]);
    }

    public function test_los_argumentos_vacios_viajan_como_objeto_json_y_el_texto_del_asistente_se_conserva(): void
    {
        $this->fingir($this->respuestaTexto());

        $this->proveedor()->responder([
            MensajeIA::usuario('Hola'),
            MensajeIA::asistente('Un momento', [new LlamadaHerramienta('c1', 'ver_servicios')]),
            MensajeIA::resultado(new LlamadaHerramienta('c1', 'ver_servicios'), 'Limpieza dental'),
        ]);

        Http::assertSent(fn (Request $r) => $r['messages'][1]['content'] === 'Un momento'
            && $r['messages'][1]['tool_calls'][0]['function']['arguments'] === '{}'
            && $r['messages'][2] === ['role' => 'tool', 'tool_call_id' => 'c1', 'content' => 'Limpieza dental']);
    }

    public function test_ningun_mensaje_lleva_el_campo_name(): void
    {
        $this->fingir($this->respuestaTexto());

        $this->proveedor()->responder([
            MensajeIA::usuario('Hola'),
            MensajeIA::asistente(null, [new LlamadaHerramienta('c1', 'ver_servicios')]),
            MensajeIA::resultado(new LlamadaHerramienta('c1', 'ver_servicios'), ['ok' => true]),
        ], [], 'Instrucciones');

        Http::assertSent(function (Request $r) {
            foreach ($r['messages'] as $mensaje) {
                if (array_key_exists('name', $mensaje)) {
                    return false;
                }
            }

            return true;
        });
    }

    public function test_usa_los_timeouts_de_la_configuracion(): void
    {
        $proveedor = new class (config('ia.proveedores.groq')) extends GroqProveedor {
            public function opciones(): array
            {
                return $this->cliente('k')->getOptions();
            }
        };

        $this->assertSame(15, $proveedor->opciones()['timeout']);
        $this->assertSame(5, $proveedor->opciones()['connect_timeout']);
    }

    // ---------- Fallos: mensaje amable y nada se rompe ----------

    public static function erroresHttp(): array
    {
        return [
            '400 petición inválida' => [400, 'invalid_request_error'],
            '401 key inválida' => [401, 'invalid_api_key'],
            '403 sin permiso' => [403, 'permission_error'],
            '413 petición muy grande' => [413, 'invalid_request_error'],
            '422 no procesable' => [422, 'invalid_request_error'],
            '424 dependencia fallida' => [424, 'failed_dependency'],
            '429 límite' => [429, 'tokens'],
            '500 error del servidor' => [500, 'internal_server_error'],
            '503 saturado' => [503, 'service_unavailable'],
        ];
    }

    #[DataProvider('erroresHttp')]
    public function test_un_error_http_devuelve_el_mensaje_amable(int $estado, string $tipo): void
    {
        $this->fingir(['error' => ['message' => 'detalle técnico', 'type' => $tipo]], $estado);

        $respuesta = $this->proveedor()->responder([MensajeIA::usuario('Hola')]);

        $this->assertTrue($respuesta->falla);
        $this->assertSame(config('ia.mensaje_error'), $respuesta->texto);
        $this->assertFalse($respuesta->pideHerramientas());
        $this->assertSame("HTTP $estado ($tipo)", $respuesta->motivo);
        $this->assertStringContainsString("HTTP $estado ($tipo)", $this->log());
    }

    public function test_un_error_http_sin_cuerpo_json_tambien_es_amable(): void
    {
        Http::fake(['*' => Http::response('<html>Bad gateway</html>', 502)]);

        $respuesta = $this->proveedor()->responder([MensajeIA::usuario('Hola')]);

        $this->assertTrue($respuesta->falla);
        $this->assertSame('HTTP 502', $respuesta->motivo);
    }

    public function test_un_timeout_o_una_conexion_caida_devuelve_el_mensaje_amable(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out after 15001 milliseconds'));

        $respuesta = $this->proveedor()->responder([MensajeIA::usuario('Hola')]);

        $this->assertTrue($respuesta->falla);
        $this->assertSame(config('ia.mensaje_error'), $respuesta->texto);
        $this->assertStringContainsString('ConnectionException', $this->log());
    }

    public function test_una_respuesta_que_no_es_json_devuelve_el_mensaje_amable(): void
    {
        Http::fake(['*' => Http::response('<html>Bad gateway</html>', 200)]);

        $respuesta = $this->proveedor()->responder([MensajeIA::usuario('Hola')]);

        $this->assertTrue($respuesta->falla);
        $this->assertSame('respuesta que no es JSON', $respuesta->motivo);
    }

    public static function respuestasSinContenido(): array
    {
        return [
            'sin choices' => [[]],
            'choices vacío' => [['choices' => []]],
            'sin message' => [['choices' => [['finish_reason' => 'stop']]]],
            'contenido nulo' => [['choices' => [['finish_reason' => 'stop', 'message' => ['content' => null]]]]],
            'texto en blanco' => [['choices' => [['finish_reason' => 'stop', 'message' => ['content' => "  \n"]]]]],
            'cortada por longitud' => [['choices' => [['finish_reason' => 'length', 'message' => ['content' => '']]]]],
            'error dentro de un 200' => [['error' => ['message' => 'x', 'type' => 'invalid_request_error']]],
        ];
    }

    #[DataProvider('respuestasSinContenido')]
    public function test_una_respuesta_sin_contenido_util_es_un_fallo_amable(array $cuerpo): void
    {
        $this->fingir($cuerpo);

        $respuesta = $this->proveedor()->responder([MensajeIA::usuario('Hola')]);

        $this->assertTrue($respuesta->falla);
        $this->assertSame(config('ia.mensaje_error'), $respuesta->texto);
    }

    public function test_el_motivo_de_una_respuesta_cortada_incluye_finish_reason(): void
    {
        $this->fingir(['choices' => [['finish_reason' => 'length', 'message' => ['content' => '']]]]);

        $respuesta = $this->proveedor()->responder([MensajeIA::usuario('Hola')]);

        $this->assertStringContainsString('finish_reason: length', (string) $respuesta->motivo);
    }

    public static function argumentosInvalidos(): array
    {
        return [
            'JSON roto' => ['{"fecha": 2026-'],
            'texto suelto' => ['no es json'],
            'un número' => ['5'],
        ];
    }

    #[DataProvider('argumentosInvalidos')]
    public function test_una_llamada_con_argumentos_que_no_son_json_valido_es_un_fallo_amable(string $argumentos): void
    {
        $this->fingir($this->respuestaLlamada($argumentos));

        $respuesta = $this->proveedor()->responder([MensajeIA::usuario('Hola')], [$this->herramienta()]);

        $this->assertTrue($respuesta->falla);
        $this->assertFalse($respuesta->pideHerramientas());
        $this->assertSame(config('ia.mensaje_error'), $respuesta->texto);
    }

    public function test_sin_api_key_no_llama_a_nadie(): void
    {
        config(['ia.proveedores.groq.api_key' => '']);
        Http::fake();

        $respuesta = $this->proveedor()->responder([MensajeIA::usuario('Hola')]);

        $this->assertTrue($respuesta->falla);
        $this->assertSame(config('ia.mensaje_error'), $respuesta->texto);
        Http::assertNothingSent();
    }

    // ---------- Secretos y datos del paciente fuera del log ----------

    public function test_la_key_nunca_aparece_en_el_log_ni_siquiera_si_la_excepcion_la_trae(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 7: no se pudo conectar (Authorization: Bearer ' . self::KEY . ')'));

        $respuesta = $this->proveedor()->responder([MensajeIA::usuario('Hola')]);

        $this->assertTrue($respuesta->falla);
        $this->assertNotEmpty($this->log);
        $this->assertStringNotContainsString(self::KEY, $this->log());
        $this->assertStringNotContainsString(self::KEY, (string) $respuesta->motivo);
        $this->assertStringContainsString('***', $this->log());
    }

    public function test_los_mensajes_del_usuario_no_aparecen_en_el_log(): void
    {
        $this->fingir(['error' => ['message' => 'Valor inválido: Ana Ejemplo 5550100001', 'type' => 'invalid_request_error']], 400);

        $this->proveedor()->responder([MensajeIA::usuario('Soy Ana Ejemplo, mi teléfono es 5550100001')]);

        $this->assertNotEmpty($this->log);
        $this->assertStringNotContainsString('Ana Ejemplo', $this->log());
        $this->assertStringNotContainsString('5550100001', $this->log());
    }
}
