<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Tests\TestCase;

/**
 * Un fallo del servicio de correo nunca debe tumbar una operación del usuario.
 * Aquí el correo siempre falla, como pasaría si Resend estuviera caído o
 * rechazara el envío (por ejemplo, sin dominio verificado).
 */
class CorreoFallidoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    // Transporte de correo que siempre lanza excepción
    private function simularCorreoCaido(): void
    {
        Mail::extend('caido', fn () => new class extends AbstractTransport {
            protected function doSend(SentMessage $message): void
            {
                throw new TransportException('Servicio de correo no disponible');
            }

            public function __toString(): string
            {
                return 'caido';
            }
        });

        config([
            'mail.mailers.caido' => ['transport' => 'caido'],
            'mail.default' => 'caido',
        ]);

        Log::spy();
    }

    // 'role' no está en $fillable: se asigna explícitamente
    private function crearPaciente(array $datos = []): User
    {
        $user = User::factory()->make(array_merge(['apellidos' => 'Pérez', 'activo' => true], $datos));
        $user->role = 'paciente';
        $user->save();

        return $user;
    }

    public function test_el_registro_sigue_aunque_el_correo_falle(): void
    {
        $this->simularCorreoCaido();

        $this->post('/register', [
            'name' => 'Lucía',
            'apellidos' => 'Hernández',
            'email' => 'lucia@correo.com',
            'telefono' => '7531112233',
            'sexo' => 'femenino',
            'password' => 'secreta123',
            'password_confirmation' => 'secreta123',
        ])->assertRedirect(route('paciente.dashboard'));

        $usuario = User::where('email', 'lucia@correo.com')->first();
        $this->assertNotNull($usuario);
        $this->assertNotNull($usuario->expediente);
        $this->assertAuthenticatedAs($usuario);

        // Llega al dashboard y ve el aviso para reenviar la verificación
        $this->get(route('paciente.dashboard'))
            ->assertOk()
            ->assertSee('Verifica tu correo electrónico');

        Log::shouldHaveReceived('error')
            ->withArgs(fn ($mensaje, $contexto) => str_contains($mensaje, 'verificación') && $contexto['usuario_id'] === $usuario->id)
            ->once();
    }

    public function test_reenviar_la_verificacion_avisa_sin_error_500(): void
    {
        $paciente = $this->crearPaciente();
        $this->simularCorreoCaido();

        $this->actingAs($paciente)
            ->from(route('paciente.dashboard'))
            ->post(route('verification.send'))
            ->assertRedirect(route('paciente.dashboard'))
            ->assertSessionHas('error', 'No pudimos enviar el correo en este momento. Intenta más tarde.');

        Log::shouldHaveReceived('error')->once();
    }

    public function test_cambiar_el_correo_guarda_los_datos_aunque_el_correo_falle(): void
    {
        $paciente = $this->crearPaciente(['email' => 'maria@correo.com']);
        $paciente->forceFill(['email_verified_at' => now()])->save();
        $this->simularCorreoCaido();

        $this->actingAs($paciente)->put(route('paciente.perfil.datos'), [
            'name' => $paciente->name,
            'apellidos' => $paciente->apellidos,
            'email' => 'nuevo@correo.com',
        ])->assertRedirect(route('paciente.perfil.index'))
          ->assertSessionHas('success', 'Datos actualizados, pero no pudimos enviar el correo de verificación. Puedes reenviarlo desde el inicio.');

        $paciente->refresh();
        $this->assertSame('nuevo@correo.com', $paciente->email);
        $this->assertNull($paciente->email_verified_at);
    }

    public function test_olvide_mi_contrasena_da_el_mismo_mensaje_aunque_el_correo_falle(): void
    {
        $this->crearPaciente(['email' => 'maria@correo.com']);
        $this->simularCorreoCaido();

        $mensaje = 'Si el correo está registrado, te enviamos un enlace. Si no te llega en unos minutos, comunícate con la clínica para que te generen una contraseña temporal.';

        // Cuenta existente, con el correo caído
        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'maria@correo.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('success', $mensaje);

        // Cuenta inexistente: exactamente la misma respuesta
        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'nadie@correo.com'])
            ->assertSessionHas('success', $mensaje);

        Log::shouldHaveReceived('error')
            ->withArgs(fn ($mensajeLog) => str_contains($mensajeLog, 'recuperación'))
            ->once();
    }

    public function test_con_el_correo_funcionando_las_notificaciones_siguen_saliendo(): void
    {
        Notification::fake();
        $paciente = $this->crearPaciente(['email' => 'maria@correo.com']);

        $this->assertTrue($paciente->enviarVerificacion());
        Notification::assertSentTo($paciente, VerifyEmail::class);

        $this->post(route('password.email'), ['email' => 'maria@correo.com']);
        Notification::assertSentTo($paciente, ResetPassword::class);
    }

    public function test_la_ruta_de_webhooks_de_resend_no_esta_expuesta(): void
    {
        $this->post('/resend/webhook', ['type' => 'email.sent'])->assertNotFound();
    }
}
