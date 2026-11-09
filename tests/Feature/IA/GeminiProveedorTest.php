<?php

namespace Tests\Feature\IA;

use App\Services\IA\Datos\HerramientaIA;
use App\Services\IA\Datos\LlamadaHerramienta;
use App\Services\IA\Datos\MensajeIA;
use App\Services\IA\GeminiProveedor;
use App\Services\IA\ProveedorIA;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Todo con Http::fake(): ninguna prueba llama a la IA real */
class GeminiProveedorTest extends TestCase
{
    private const KEY = 'AIza-CLAVE-SECRETA-123';
    private const URL = 'https://generativelanguage.googleapis.com/v1beta/interactions';

    /** @var string[] */
    private array $log = [];

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config([
            'logging.default' => 'null',
            'ia.proveedor' => 'gemini',
            'ia.proveedores.gemini.api_key' => self::KEY,
            'ia.proveedores.gemini.modelo' => 'gemini-de-prueba',
        ]);

        Event::listen(MessageLogged::class, fn (MessageLogged $e) => $this->log[] = $e->message . ' ' . json_encode($e->context));
    }

    private function proveedor(): ProveedorIA
    {
        return app(ProveedorIA::class);
    }

    private function fingir(array $cuerpo, int $estado = 200): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($cuerpo, $estado)]);
    }

    private function respuestaTexto(string $texto = 'Hola, ¿en qué te ayudo?'): array
    {
        return [
            'id' => 'v1_abc',
            'status' => 'completed',
            'steps' => [
                ['type' => 'thought', 'signature' => 'FIRMA-TEXTO'],
                ['type' => 'model_output', 'content' => [['type' => 'text', 'text' => $texto]]],
            ],
        ];
    }

    private function respuestaLlamada(): array
    {
        return [
            'id' => 'v1_def',
            'status' => 'requires_action',
            'steps' => [
                ['type' => 'thought', 'signature' => 'FIRMA-LLAMADA'],
                ['type' => 'function_call', 'id' => 'call_1', 'name' => 'ver_horarios', 'arguments' => ['fecha' => '2026-12-01']],
            ],
        ];
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
                && $r->header('x-goog-api-key') === [self::KEY]
                && $r->header('Api-Revision') === ['2026-05-20']
                && $r['model'] === 'gemini-de-prueba'
                && $r['store'] === false
                && $r['system_instruction'] === 'Eres la asistente de Dentissa.'
                && $r['input'] === [['type' => 'user_input', 'content' => 'Hola']]
                && !isset($r['tools'])
                && !str_contains($r->url(), self::KEY)
                && !str_contains($r->body(), self::KEY);
        });
    }

    public function test_sin_instrucciones_no_manda_system_instruction(): void
    {
        $this->fingir($this->respuestaTexto());

        $this->proveedor()->responder([MensajeIA::usuario('Hola')]);

        Http::assertSent(fn (Request $r) => !isset($r['system_instruction']) && !isset($r['tools']));
    }

    public function test_con_herramientas_manda_su_definicion_y_devuelve_la_llamada(): void
    {
        $this->fingir($this->respuestaLlamada());

        $respuesta = $this->proveedor()->responder([MensajeIA::usuario('¿Hay lugar el 1 de diciembre?')], [$this->herramienta()]);

        $this->assertFalse($respuesta->falla);
        $this->assertTrue($respuesta->pideHerramientas());
        $this->assertCount(1, $respuesta->llamadas);
        $this->assertSame('call_1', $respuesta->llamadas[0]->id);
        $this->assertSame('ver_horarios', $respuesta->llamadas[0]->nombre);
        $this->assertSame(['fecha' => '2026-12-01'], $respuesta->llamadas[0]->argumentos);

        Http::assertSent(fn (Request $r) => $r['tools'] === [[
            'type' => 'function',
            'name' => 'ver_horarios',
            'description' => 'Consulta los horarios libres de un día',
            'parameters' => [
                'type' => 'object',
                'properties' => ['fecha' => ['type' => 'string', 'description' => 'Día en formato AAAA-MM-DD']],
                'required' => ['fecha'],
            ],
        ]]);
    }

    public function test_una_herramienta_sin_parametros_se_manda_como_objeto_vacio(): void
    {
        $this->fingir($this->respuestaTexto());

        $this->proveedor()->responder([MensajeIA::usuario('Hola')], [new HerramientaIA('ver_servicios', 'Lista los servicios')]);

        Http::assertSent(fn (Request $r) => str_contains($r->body(), '"parameters":{"type":"object","properties":{}}'));
    }

    public function test_los_resultados_de_herramientas_conservan_las_firmas_del_modelo(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
            ->push($this->respuestaLlamada())
            ->push($this->respuestaTexto('Sí, hay lugar a las 10:00.'))]);

        $primera = $this->proveedor()->responder([MensajeIA::usuario('¿Hay lugar?')], [$this->herramienta()]);
        $segunda = $this->proveedor()->responder([
            MensajeIA::usuario('¿Hay lugar?'),
            $primera->comoMensaje(),
            MensajeIA::resultado($primera->llamadas[0], ['horas' => ['10:00', '11:00']]),
        ], [$this->herramienta()]);

        $this->assertSame('Sí, hay lugar a las 10:00.', $segunda->texto);

        // El historial viaja como en la documentación: entrada, pasos del modelo (con firma) y resultado
        Http::assertSent(fn (Request $r) => $r['input'] === [
            ['type' => 'user_input', 'content' => '¿Hay lugar?'],
            ['type' => 'thought', 'signature' => 'FIRMA-LLAMADA'],
            ['type' => 'function_call', 'id' => 'call_1', 'name' => 'ver_horarios', 'arguments' => ['fecha' => '2026-12-01']],
            ['type' => 'function_result', 'name' => 'ver_horarios', 'call_id' => 'call_1',
                'result' => [['type' => 'text', 'text' => '{"horas":["10:00","11:00"]}']]],
        ]);
    }

    public function test_un_mensaje_del_asistente_sin_datos_del_proveedor_se_reconstruye(): void
    {
        $this->fingir($this->respuestaTexto());

        $this->proveedor()->responder([
            MensajeIA::usuario('Hola'),
            MensajeIA::asistente('Un momento', [new LlamadaHerramienta('c1', 'ver_servicios')]),
            MensajeIA::resultado(new LlamadaHerramienta('c1', 'ver_servicios'), 'Limpieza dental'),
        ]);

        // Sin argumentos viaja como objeto vacío ({}), no como lista vacía ([])
        $esperado = [
            ['type' => 'user_input', 'content' => 'Hola'],
            ['type' => 'model_output', 'content' => [['type' => 'text', 'text' => 'Un momento']]],
            ['type' => 'function_call', 'id' => 'c1', 'name' => 'ver_servicios', 'arguments' => new \stdClass],
            ['type' => 'function_result', 'name' => 'ver_servicios', 'call_id' => 'c1', 'result' => [['type' => 'text', 'text' => 'Limpieza dental']]],
        ];

        Http::assertSent(fn (Request $r) => json_encode($r['input']) === json_encode($esperado)
            && str_contains($r->body(), '"arguments":{}'));
    }

    public function test_los_datos_de_otro_proveedor_no_se_reenvian_a_gemini(): void
    {
        $this->fingir($this->respuestaTexto());

        $this->proveedor()->responder([
            MensajeIA::usuario('Hola'),
            MensajeIA::asistente('Buenas', [], [['type' => 'formato_de_otro']], 'groq'),
        ]);

        Http::assertSent(fn (Request $r) => !str_contains($r->body(), 'formato_de_otro') && str_contains($r->body(), 'Buenas'));
    }

    public function test_usa_los_timeouts_de_la_configuracion(): void
    {
        $this->assertSame(15, config('ia.timeout'));

        $proveedor = new class (config('ia.proveedores.gemini')) extends GeminiProveedor {
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
            '400 petición inválida' => [400, 'invalid_request'],
            '401 key inválida' => [401, 'authentication'],
            '403 sin permiso' => [403, 'permission_denied'],
            '429 límite' => [429, 'rate_limit_exceeded'],
            '500 error del servidor' => [500, 'api_error'],
            '503 saturado' => [503, 'service_unavailable'],
        ];
    }

    #[DataProvider('erroresHttp')]
    public function test_un_error_http_devuelve_el_mensaje_amable(int $estado, string $codigo): void
    {
        $this->fingir(['error' => ['code' => $codigo, 'message' => 'detalle técnico']], $estado);

        $respuesta = $this->proveedor()->responder([MensajeIA::usuario('Hola')]);

        $this->assertTrue($respuesta->falla);
        $this->assertSame(config('ia.mensaje_error'), $respuesta->texto);
        $this->assertFalse($respuesta->pideHerramientas());
        $this->assertSame("HTTP $estado ($codigo)", $respuesta->motivo);
        $this->assertStringContainsString("HTTP $estado ($codigo)", $this->log());
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
            'sin pasos' => [['status' => 'completed']],
            'pasos vacíos' => [['status' => 'completed', 'steps' => []]],
            'solo pensamiento (bloqueada)' => [['status' => 'completed', 'steps' => [['type' => 'thought', 'signature' => 'x']]]],
            'texto en blanco' => [['steps' => [['type' => 'model_output', 'content' => [['type' => 'text', 'text' => "  \n"]]]]]],
            'estado fallido' => [['status' => 'failed', 'steps' => [['type' => 'model_output', 'content' => [['type' => 'text', 'text' => 'x']]]]]],
            'error dentro de un 200' => [['error' => ['code' => 'safety', 'message' => 'bloqueado']]],
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

    public function test_sin_api_key_no_llama_a_nadie(): void
    {
        config(['ia.proveedores.gemini.api_key' => '']);
        Http::fake();

        $respuesta = $this->proveedor()->responder([MensajeIA::usuario('Hola')]);

        $this->assertTrue($respuesta->falla);
        $this->assertSame(config('ia.mensaje_error'), $respuesta->texto);
        Http::assertNothingSent();
    }

    // ---------- Secretos y datos del paciente fuera del log ----------

    public function test_la_key_nunca_aparece_en_el_log_ni_siquiera_si_la_excepcion_la_trae(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 7: no se pudo conectar (x-goog-api-key: ' . self::KEY . ')'));

        $respuesta = $this->proveedor()->responder([MensajeIA::usuario('Hola')]);

        $this->assertTrue($respuesta->falla);
        $this->assertNotEmpty($this->log);
        $this->assertStringNotContainsString(self::KEY, $this->log());
        $this->assertStringNotContainsString(self::KEY, (string) $respuesta->motivo);
        $this->assertStringContainsString('***', $this->log());
    }

    public function test_los_mensajes_del_usuario_no_aparecen_en_el_log(): void
    {
        $this->fingir(['error' => ['code' => 'invalid_request', 'message' => 'Valor inválido: Ana Ejemplo 5550100001']], 400);

        $this->proveedor()->responder([MensajeIA::usuario('Soy Ana Ejemplo, mi teléfono es 5550100001')]);

        $this->assertNotEmpty($this->log);
        $this->assertStringNotContainsString('Ana Ejemplo', $this->log());
        $this->assertStringNotContainsString('5550100001', $this->log());
    }

    public function test_el_servicio_no_lee_env_directamente(): void
    {
        foreach (glob(app_path('Services/IA/{,Datos/}*.php'), GLOB_BRACE) as $archivo) {
            $this->assertStringNotContainsString('env(', file_get_contents($archivo), basename($archivo));
        }
    }
}
