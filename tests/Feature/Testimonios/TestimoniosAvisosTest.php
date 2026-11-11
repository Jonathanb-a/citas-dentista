<?php

namespace Tests\Feature\Testimonios;

use App\Models\Testimonio;
use Illuminate\Http\Client\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

/** Aviso por Telegram a la clínica y que nada del contenido llegue a los logs */
class TestimoniosAvisosTest extends TestimoniosTestCase
{
    private const TOKEN = '123456:SECRETO';

    private function activarTelegram(): void
    {
        config(['services.telegram.token' => self::TOKEN, 'services.telegram.chat_ids' => '111,222']);
    }

    private function mensajesEnviados(): array
    {
        return Http::recorded()->map(fn ($par) => (string) $par[0]['text'])->unique()->values()->all();
    }

    public function test_un_testimonio_nuevo_avisa_con_nombre_de_pila_estrellas_y_pendiente_sin_el_texto(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $this->activarTelegram();
        $paciente = $this->paciente(['name' => 'María', 'apellidos' => 'González Secreta']);

        $this->actingAs($paciente)->post(route('paciente.testimonio.store'), $this->datos(['calificacion' => 4, 'texto' => 'Texto privado del testimonio que no debe viajar.']));

        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'sendMessage'));
        $mensaje = $this->mensajesEnviados()[0];
        $this->assertStringContainsString('Nuevo testimonio', $mensaje);
        $this->assertStringContainsString('Paciente: María', $mensaje);
        $this->assertStringContainsString('★★★★☆ (4/5)', $mensaje);
        $this->assertStringContainsString('pendiente de revisión', $mensaje);
        $this->assertStringNotContainsString('Texto privado', $mensaje);
        $this->assertStringNotContainsString('González', $mensaje);
        $this->assertStringNotContainsString('Secreta', $mensaje);
        Http::assertSentCount(2);   // un envío por cada chat configurado
    }

    public function test_un_testimonio_editado_tambien_avisa(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $this->activarTelegram();
        $paciente = $this->paciente(['name' => 'Luz']);
        $this->testimonio($paciente, 'aprobado');

        $this->actingAs($paciente)->put(route('paciente.testimonio.update'), $this->datos(['calificacion' => 2, 'texto' => 'Texto editado privado que no debe viajar por aviso.']));

        $mensaje = $this->mensajesEnviados()[0];
        $this->assertStringContainsString('Testimonio editado', $mensaje);
        $this->assertStringContainsString('Paciente: Luz', $mensaje);
        $this->assertStringContainsString('★★☆☆☆ (2/5)', $mensaje);
        $this->assertStringContainsString('pendiente de revisión', $mensaje);
        $this->assertStringNotContainsString('Texto editado', $mensaje);
    }

    public function test_borrar_o_moderar_no_avisa(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $this->activarTelegram();
        $paciente = $this->paciente();
        $t = $this->testimonio($paciente);

        $this->actingAs($this->usuario('admin'))->patch(route('admin.testimonios.aprobar', $t->id));
        $this->actingAs($paciente)->delete(route('paciente.testimonio.destroy'));

        Http::assertNothingSent();
    }

    public function test_sin_telegram_configurado_no_se_envia_nada_y_el_testimonio_se_guarda(): void
    {
        Http::fake();
        config(['services.telegram.token' => null, 'services.telegram.chat_ids' => null]);

        $this->actingAs($this->paciente())->post(route('paciente.testimonio.store'), $this->datos())->assertSessionHas('success');

        Http::assertNothingSent();
        $this->assertSame(1, Testimonio::count());
    }

    public function test_si_telegram_falla_el_paciente_no_lo_nota_y_el_log_no_trae_el_contenido(): void
    {
        $logs = [];
        Event::listen(MessageLogged::class, function (MessageLogged $e) use (&$logs) { $logs[] = $e->message . ' ' . json_encode($e->context, JSON_UNESCAPED_UNICODE); });
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Bad Request'], 400)]);
        $this->activarTelegram();
        $paciente = $this->paciente(['name' => 'Nombre', 'apellidos' => 'ApellidoSecreto']);

        $this->actingAs($paciente)->post(route('paciente.testimonio.store'), $this->datos(['texto' => 'Contenido privado del testimonio para el log.']))
            ->assertRedirect(route('paciente.testimonio.index'))->assertSessionHas('success');

        $this->assertSame(1, Testimonio::count());
        $todo = implode("\n", $logs);
        foreach (['Contenido privado', 'ApellidoSecreto', self::TOKEN] as $secreto) {
            $this->assertStringNotContainsString($secreto, $todo);
        }
    }

    public function test_los_errores_de_la_landing_no_registran_contenido(): void
    {
        $logs = [];
        Event::listen(MessageLogged::class, function (MessageLogged $e) use (&$logs) { $logs[] = $e->message . ' ' . json_encode($e->context); });
        $this->testimonio($this->paciente(['name' => 'Nombre']), 'aprobado', ['texto' => 'Contenido privado que no va al log de la landing.']);
        // La consulta falla con un mensaje que trae contenido: el log solo debe llevar la clase de la excepción
        Testimonio::retrieved(fn () => throw new \RuntimeException('Contenido privado en el mensaje de la excepción'));

        $this->get('/')->assertOk();

        $todo = implode("\n", $logs);
        $this->assertStringContainsString('testimonios de pacientes', $todo);   // sí queda constancia del fallo
        $this->assertStringNotContainsString('Contenido privado', $todo);
        $this->assertStringNotContainsString('mensaje de la excepción', $todo);
    }
}
