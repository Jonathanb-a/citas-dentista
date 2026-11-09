<?php

namespace Tests\Feature\Chat;

use App\Services\Chat\FirmaHistorial;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** Base de las pruebas del chat: la IA siempre es Http::fake(), nunca la real */
abstract class ChatTestCase extends TestCase
{
    protected const IP = '203.0.113.10';

    /** @var string[] */
    protected array $log = [];

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config([
            'logging.default' => 'null',
            'ia.proveedor' => 'groq',
            'ia.proveedores.groq.api_key' => 'gsk_clave-de-prueba',
            'ia.proveedores.groq.modelo' => 'openai/gpt-oss-120b',
        ]);

        Event::listen(MessageLogged::class, fn (MessageLogged $e) => $this->log[] = $e->message . ' ' . json_encode($e->context));
    }

    protected function fingirIA(string $texto = '¡Hola! ¿En qué te puedo ayudar?'): void
    {
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => $texto]]]])]);
    }

    protected function fingirIACaida(int $estado = 500): void
    {
        Http::fake(['api.groq.com/*' => Http::response(['error' => ['message' => 'detalle', 'type' => 'internal_server_error']], $estado)]);
    }

    protected function fingirIASinConexion(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));
    }

    protected function chat(array $datos, string $ip = self::IP): TestResponse
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->postJson('/chat', $datos);
    }

    protected function firma(string $texto): string
    {
        return app(FirmaHistorial::class)->firmar($texto);
    }

    /** Un turno completo (visitante + asistente) con su firma */
    protected function turno(string $usuario = 'Hola', string $asistente = 'Buenas, ¿en qué te ayudo?'): array
    {
        return [
            ['rol' => 'user', 'texto' => $usuario],
            ['rol' => 'assistant', 'texto' => $asistente, 'firma' => $this->firma($asistente)],
        ];
    }

    /** Mensajes enviados a la IA en la última (o n-ésima) petición */
    protected function mensajesEnviados(int $indice = -1): array
    {
        $enviadas = Http::recorded()->all();
        $peticion = $indice < 0 ? $enviadas[count($enviadas) + $indice][0] : $enviadas[$indice][0];

        return $peticion['messages'];
    }

    protected function log(): string
    {
        return implode("\n", $this->log);
    }
}
