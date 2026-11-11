<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CorreoResend;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Mail\Transport\ResendTransport;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Resend\Client;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Tests\TestCase;

/**
 * Correo por la API HTTPS de Resend (Railway bloquea el SMTP). En pruebas nunca sale nada de verdad:
 * el mailer es "array" y el transporte de Resend solo se construye, sin llamar a la red.
 * Todos los correos y datos son ficticios.
 */
class CorreoResendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function paciente(array $datos = []): User
    {
        $user = User::factory()->make(array_merge(['apellidos' => 'Ficticio', 'activo' => true, 'email' => 'ana.prueba@ejemplo.test'], $datos));
        $user->role = 'paciente';
        $user->save();

        return $user;
    }

    /** Baja por reflexión hasta el cliente Guzzle que usa un transporte de Resend */
    private function guzzleDe(ResendTransport $transporte): array
    {
        $leer = function (object $objeto, string $propiedad) {
            $p = new \ReflectionProperty($objeto, $propiedad);
            $p->setAccessible(true);

            return $p->getValue($objeto);
        };

        $cliente = $leer($transporte, 'resend');                       // Resend\Client
        $transportador = $leer($cliente, 'transporter');                // HttpTransporter
        $guzzle = $leer($transportador, 'client');                      // GuzzleHttp\Client

        return $leer($guzzle, 'config');
    }

    // ---------- Ningún correo sale de verdad en pruebas ----------

    public function test_en_pruebas_el_mailer_es_array_y_no_hay_clave_de_resend(): void
    {
        $this->assertSame('array', config('mail.default'));
        $this->assertEmpty(config('services.resend.key'));
        $this->assertEmpty(config('resend.api_key'));
    }

    public function test_los_correos_de_verificacion_y_recuperacion_van_al_mailer_array_sin_tocar_la_red(): void
    {
        Http::preventStrayRequests();
        $paciente = $this->paciente();

        $this->assertTrue($paciente->enviarVerificacion());
        $this->post(route('password.email'), ['email' => $paciente->email])->assertRedirect();

        $mensajes = app('mail.manager')->mailer('array')->getSymfonyTransport()->messages();
        $this->assertCount(2, $mensajes);
        $asuntos = $mensajes->map(fn ($m) => $m->getOriginalMessage()->getSubject())->all();
        $this->assertCount(2, array_unique($asuntos));
    }

    public function test_el_remitente_sale_de_la_configuracion_del_env(): void
    {
        config(['mail.from.address' => 'citas@dominio-ejemplo.test', 'mail.from.name' => 'Dentissa']);
        $this->paciente()->enviarVerificacion();

        $mensaje = app('mail.manager')->mailer('array')->getSymfonyTransport()->messages()->first()->getOriginalMessage();

        $this->assertSame('citas@dominio-ejemplo.test', $mensaje->getFrom()[0]->getAddress());
        $this->assertSame('Dentissa', $mensaje->getFrom()[0]->getName());
    }

    public function test_el_remitente_de_pruebas_no_es_el_de_resend_ni_un_dominio_real(): void
    {
        $this->assertSame('citas@ejemplo.test', config('mail.from.address'));
    }

    // ---------- El mailer resend: tiempos límite y clave desde la configuración ----------

    public function test_el_mailer_resend_usa_nuestro_transporte_con_5_s_y_3_s_de_conexion(): void
    {
        config(['mail.default' => 'resend', 'services.resend.key' => 're_clave_de_prueba']);
        Mail::purge('resend');

        $transporte = app('mail.manager')->mailer('resend')->getSymfonyTransport();

        $this->assertInstanceOf(ResendTransport::class, $transporte);
        $config = $this->guzzleDe($transporte);
        $this->assertSame(5.0, $config['timeout']);
        $this->assertSame(3.0, $config['connect_timeout']);
    }

    public function test_los_tiempos_limite_se_ajustan_por_configuracion(): void
    {
        config(['services.resend.key' => 're_clave_de_prueba', 'mail.mailers.resend.timeout' => 8.0, 'mail.mailers.resend.connect_timeout' => 2.0]);
        Mail::purge('resend');

        $config = $this->guzzleDe(app('mail.manager')->mailer('resend')->getSymfonyTransport());

        $this->assertSame(8.0, $config['timeout']);
        $this->assertSame(2.0, $config['connect_timeout']);
    }

    public function test_los_valores_por_defecto_de_los_tiempos_son_5_y_3(): void
    {
        $this->assertSame(5.0, config('mail.mailers.resend.timeout'));
        $this->assertSame(3.0, config('mail.mailers.resend.connect_timeout'));
    }

    public function test_construir_el_cliente_no_hace_ninguna_peticion(): void
    {
        Http::preventStrayRequests();
        Http::fake();

        $cliente = app(CorreoResend::class)->cliente('re_clave_de_prueba');

        $this->assertInstanceOf(Client::class, $cliente);
        Http::assertNothingSent();
    }

    // ---------- Falta la clave ----------

    public function test_sin_clave_avisa_una_sola_vez_por_proceso_sin_datos_y_no_lanza_nada(): void
    {
        $logs = [];
        Event::listen(MessageLogged::class, function (MessageLogged $e) use (&$logs) {
            $logs[] = [$e->level, $e->message, $e->context];
        });
        config(['mail.default' => 'resend', 'services.resend.key' => null]);
        Mail::purge('resend');

        app('mail.manager')->mailer('resend')->getSymfonyTransport();
        Mail::purge('resend');
        app('mail.manager')->mailer('resend')->getSymfonyTransport();

        $avisos = array_values(array_filter($logs, fn ($l) => str_contains($l[1], 'RESEND_API_KEY')));
        $this->assertCount(1, $avisos);
        $this->assertSame('warning', $avisos[0][0]);
        $this->assertSame([], $avisos[0][2]);
    }

    public function test_con_clave_no_hay_aviso(): void
    {
        $logs = [];
        Event::listen(MessageLogged::class, function (MessageLogged $e) use (&$logs) { $logs[] = $e->message; });
        config(['services.resend.key' => 're_clave_de_prueba']);
        Mail::purge('resend');

        app('mail.manager')->mailer('resend')->getSymfonyTransport();

        $this->assertSame([], array_values(array_filter($logs, fn ($m) => str_contains($m, 'RESEND_API_KEY'))));
    }

    // ---------- Los logs de un fallo no traen direcciones ni contenido ----------

    /** @var string[] */
    private array $logs = [];

    private function correoCaidoConMensaje(string $mensaje): void
    {
        Mail::extend('caido', fn () => new class($mensaje) extends AbstractTransport {
            public function __construct(private string $texto) { parent::__construct(); }

            protected function doSend(SentMessage $message): void
            {
                throw new TransportException($this->texto);
            }

            public function __toString(): string { return 'caido'; }
        });
        config(['mail.mailers.caido' => ['transport' => 'caido'], 'mail.default' => 'caido']);

        Event::listen(MessageLogged::class, function (MessageLogged $e) {
            $this->logs[] = $e->level . ' ' . $e->message . ' ' . json_encode($e->context, JSON_UNESCAPED_UNICODE);
        });
    }

    public function test_el_log_de_un_fallo_de_verificacion_solo_lleva_la_clase_del_error(): void
    {
        $this->correoCaidoConMensaje('Rechazado para ana.prueba@ejemplo.test: dominio no verificado, contenido secreto');
        $paciente = $this->paciente();

        $this->assertFalse($paciente->enviarVerificacion());

        $todo = implode("\n", $this->logs);
        $this->assertStringContainsString('No se pudo enviar el correo de verificación', $todo);
        $this->assertStringContainsString('TransportException', $todo);
        foreach (['ana.prueba@ejemplo.test', 'ejemplo.test', 'contenido secreto', 'dominio no verificado', '"usuario_id"'] as $dato) {
            $this->assertStringNotContainsString($dato, $todo, $dato);
        }
    }

    public function test_el_log_de_un_fallo_de_recuperacion_solo_lleva_la_clase_del_error(): void
    {
        $this->correoCaidoConMensaje('Rechazado para ana.prueba@ejemplo.test: contenido secreto del enlace');
        $paciente = $this->paciente();

        $this->post(route('password.email'), ['email' => $paciente->email])->assertRedirect()->assertSessionHas('success');

        $todo = implode("\n", $this->logs);
        $this->assertStringContainsString('No se pudo enviar el enlace de recuperación', $todo);
        $this->assertStringContainsString('TransportException', $todo);
        foreach (['ana.prueba@ejemplo.test', 'contenido secreto'] as $dato) {
            $this->assertStringNotContainsString($dato, $todo, $dato);
        }
    }
}
