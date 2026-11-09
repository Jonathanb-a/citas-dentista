<?php

namespace Tests\Feature\Chat;

use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Horario;
use App\Models\Servicio;
use App\Models\User;
use App\Services\Chat\FirmaHistorial;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** Base de las pruebas del chat: la IA siempre es Http::fake(), nunca la real */
abstract class ChatTestCase extends TestCase
{
    use RefreshDatabase;

    protected const IP = '203.0.113.10';

    // Todos los nombres y teléfonos de estas pruebas son ficticios. "Ahora" es el lunes 9 de nov de 2026,
    // 12:00 en el consultorio; el lunes siguiente es la fecha para agendar
    protected const AHORA = '2026-11-09 18:00:00';
    protected const FECHA = '2026-11-16';
    protected const TELEFONO = '5550100001';

    /** @var string[] */
    protected array $log = [];

    protected ?\Illuminate\Http\Client\ResponseSequence $secuenciaIA = null;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(self::AHORA);
        Http::preventStrayRequests();
        config([
            'logging.default' => 'null',
            'ia.proveedor' => 'groq',
            'ia.proveedores.groq.api_key' => 'gsk_clave-de-prueba',
            'ia.proveedores.groq.modelo' => 'openai/gpt-oss-120b',
        ]);

        Event::listen(MessageLogged::class, fn (MessageLogged $e) => $this->log[] = $e->message . ' ' . json_encode($e->context));
    }

    // ---------- Datos ficticios ----------

    protected function crearServicio(string $nombre = 'Limpieza dental', int $minutos = 45, bool $activo = true, bool $especialidadActiva = true): Servicio
    {
        $especialidad = Especialidad::create(['nombre' => 'Especialidad ' . Str::random(6), 'activo' => $especialidadActiva]);

        return Servicio::create(['especialidad_id' => $especialidad->id, 'nombre' => $nombre, 'duracion_minutos' => $minutos, 'activo' => $activo]);
    }

    protected function crearHorarioLunes(string $inicio = '09:00', string $fin = '14:00'): Horario
    {
        return Horario::create(['dia_semana' => 'lunes', 'hora_inicio' => $inicio, 'hora_fin' => $fin, 'disponible' => true]);
    }

    /** Paciente real (con cuenta): 'role' no está en $fillable, se asigna explícitamente */
    protected function crearUsuario(string $role = 'paciente', array $datos = []): User
    {
        $usuario = User::factory()->make(array_merge(['apellidos' => 'Ficticio', 'activo' => true], $datos));
        $usuario->role = $role;
        $usuario->save();

        return $usuario;
    }

    protected function crearCita(User $paciente, Servicio $servicio, Horario $horario, string $hora = '09:00', string $status = 'pendiente', string $fecha = self::FECHA): Cita
    {
        return Cita::create([
            'paciente_id' => $paciente->id,
            'servicio_id' => $servicio->id,
            'horario_id' => $horario->id,
            'fecha' => $fecha,
            'hora' => $hora,
            'status' => $status,
            'confirmado_por_paciente' => false,
        ]);
    }

    protected function datosAgenda(Servicio $servicio, array $cambios = []): array
    {
        return array_merge([
            'servicio_id' => $servicio->id,
            'fecha' => self::FECHA,
            'hora' => '09:00',
            'nombre' => 'Ana',
            'apellidos' => 'Ejemplo Prueba',
            'telefono' => self::TELEFONO,
        ], $cambios);
    }

    protected function activarTelegram(): void
    {
        config(['services.telegram.token' => '123456:TOKEN-DE-PRUEBA', 'services.telegram.chat_ids' => '111']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    }

    // ---------- La IA (siempre simulada) ----------

    protected function iaTexto(string $texto): array
    {
        return ['choices' => [['finish_reason' => 'stop', 'message' => ['content' => $texto]]]];
    }

    /** Respuesta de la IA que pide una o varias herramientas: [[nombre, argumentos], ...] */
    protected function iaLlamadas(array ...$llamadas): array
    {
        $pedidas = [];
        foreach ($llamadas as $i => [$nombre, $argumentos]) {
            $pedidas[] = ['id' => 'call_' . ($i + 1), 'type' => 'function', 'function' => [
                'name' => $nombre,
                'arguments' => json_encode($argumentos, JSON_UNESCAPED_UNICODE),
            ]];
        }

        return ['choices' => [['finish_reason' => 'tool_calls', 'message' => ['content' => null, 'tool_calls' => $pedidas]]]];
    }

    protected function iaLlamada(string $nombre, array $argumentos): array
    {
        return $this->iaLlamadas([$nombre, $argumentos]);
    }

    /**
     * La IA responde en este orden, una respuesta por llamada. Es una sola secuencia por prueba:
     * cada llamada agrega las suyas al final (Http::fake() dos veces dejaría activa la primera),
     * así que hay que agregar exactamente las que se van a consumir.
     */
    protected function fingirSecuencia(array ...$respuestas): void
    {
        if ($this->secuenciaIA === null) {
            $this->secuenciaIA = Http::sequence();
            Http::fake(['api.groq.com/*' => $this->secuenciaIA]);
        }

        foreach ($respuestas as $respuesta) {
            $this->secuenciaIA->push($respuesta);
        }
    }

    protected function llamadasIA(): int
    {
        return Http::recorded(fn ($peticion) => str_contains($peticion->url(), 'api.groq.com'))->count();
    }

    /** Mensajes que recibió la IA en su n-ésima llamada (0 = la primera; -1 = la última) */
    protected function mensajesDeLlamada(int $n = -1): array
    {
        $llamadas = Http::recorded(fn ($peticion) => str_contains($peticion->url(), 'api.groq.com'))->values()->all();
        $llamada = $n < 0 ? $llamadas[count($llamadas) + $n] : $llamadas[$n];

        return $llamada[0]['messages'];
    }

    /** Resultado (ya decodificado) de la última herramienta que se le devolvió a la IA */
    protected function ultimoResultadoDeHerramienta(): array
    {
        $mensajes = array_values(array_filter($this->mensajesDeLlamada(-1), fn ($m) => $m['role'] === 'tool'));

        return json_decode(end($mensajes)['content'], true);
    }

    /** Todo lo que se mandó a la IA y todo lo que respondió, como un solo texto */
    protected function todoLoQueViajo(): string
    {
        return Http::recorded()->map(fn ($par) => $par[0]->body() . ' ' . $par[1]->body())->implode("\n");
    }

    // ---------- Agendado en dos pasos (propuesta y "sí") ----------

    /** Primera petición: la IA llama a agendar_cita y el servidor muestra el resumen */
    protected function proponer(array $datos, string $mensaje = 'Quiero agendar esa cita', string $ip = self::IP): TestResponse
    {
        $this->fingirSecuencia($this->iaLlamada('agendar_cita', $datos));

        return $this->chat(['mensaje' => $mensaje], $ip);
    }

    /**
     * Segunda petición: el visitante confirma con la propuesta firmada y la IA vuelve a llamar.
     * $despues: lo que responde la IA después de un error de la herramienta (si agenda, no hay otra llamada:
     * pásalo solo en los casos en que se espera el error)
     */
    protected function confirmar(TestResponse $propuesta, array $datos, string $mensaje = 'Sí, confirmo', string $ip = self::IP, ?string $despues = null, array $cambios = []): TestResponse
    {
        $this->fingirSecuencia($this->iaLlamada('agendar_cita', $datos));
        if ($despues !== null) {
            $this->fingirSecuencia($this->iaTexto($despues));
        }

        // $cambios sustituye campos de la petición (para probar historiales o tokens alterados)
        return $this->chat(array_merge([
            'mensaje' => $mensaje,
            'historial' => [
                ['rol' => 'user', 'texto' => 'Quiero agendar esa cita'],
                ['rol' => 'assistant', 'texto' => $propuesta->json('respuesta'), 'firma' => $propuesta->json('firma')],
            ],
            'pendiente' => $propuesta->json('pendiente'),
        ], $cambios), $ip);
    }

    /** Propuesta + "sí" */
    protected function agendarCompleto(array $datos, string $ip = self::IP, ?string $despues = null): TestResponse
    {
        return $this->confirmar($this->proponer($datos, ip: $ip), $datos, ip: $ip, despues: $despues);
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
