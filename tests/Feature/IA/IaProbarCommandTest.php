<?php

namespace Tests\Feature\IA;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IaProbarCommandTest extends TestCase
{
    private const KEY = 'AIza-CLAVE-SECRETA-123';

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
    }

    public function test_imprime_proveedor_modelo_y_la_respuesta_sin_la_key(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'steps' => [['type' => 'model_output', 'content' => [['type' => 'text', 'text' => 'hola']]]],
        ])]);

        $this->artisan('ia:probar')
            ->expectsOutputToContain('Proveedor: gemini')
            ->expectsOutputToContain('Modelo:    gemini-de-prueba')
            ->expectsOutputToContain('API key:   configurada')
            ->expectsOutputToContain('hola')
            ->doesntExpectOutputToContain(self::KEY)
            ->assertSuccessful();

        // Un solo mensaje, sin herramientas
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $r) => $r['input'] === [['type' => 'user_input', 'content' => 'Responde solo: hola']]
            && !isset($r['tools']));
    }

    public function test_si_la_ia_falla_imprime_el_mensaje_amable_y_el_motivo_sin_la_key(): void
    {
        Http::fake(['*' => Http::response(['error' => ['code' => 'authentication', 'message' => 'key ' . self::KEY]], 401)]);

        $this->artisan('ia:probar')
            ->expectsOutputToContain(config('ia.mensaje_error'))
            ->expectsOutputToContain('HTTP 401 (authentication)')
            ->doesntExpectOutputToContain(self::KEY)
            ->assertFailed();
    }

    public function test_sin_key_lo_avisa_y_no_llama_a_nadie(): void
    {
        config(['ia.proveedores.gemini.api_key' => '']);
        Http::fake();

        $this->artisan('ia:probar')
            ->expectsOutputToContain('API key:   FALTA')
            ->expectsOutputToContain(config('ia.mensaje_error'))
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_usa_el_proveedor_de_ia_proveedor(): void
    {
        config([
            'ia.proveedor' => 'groq',
            'ia.proveedores.groq.api_key' => self::KEY,
            'ia.proveedores.groq.modelo' => 'openai/gpt-oss-120b',
        ]);
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => 'hola']]]])]);

        $this->artisan('ia:probar')
            ->expectsOutputToContain('Proveedor: groq')
            ->expectsOutputToContain('Modelo:    openai/gpt-oss-120b')
            ->expectsOutputToContain('hola')
            ->doesntExpectOutputToContain(self::KEY)
            ->assertSuccessful();

        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.groq.com/openai/v1/chat/completions'
            && $r['messages'] === [['role' => 'user', 'content' => 'Responde solo: hola']]
            && !isset($r['tools']));
    }
}
