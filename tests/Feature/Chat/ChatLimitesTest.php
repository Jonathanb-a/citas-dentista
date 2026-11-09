<?php

namespace Tests\Feature\Chat;

use App\Services\Chat\LimiteGlobalIA;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ChatLimitesTest extends ChatTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-09 18:00:00');
    }

    public function test_las_cifras_iniciales(): void
    {
        $this->assertSame(6, config('chat.limite_minuto'));
        $this->assertSame(40, config('chat.limite_dia'));
        $this->assertSame(800, config('chat.limite_global_dia'));
        $this->assertSame(12, config('chat.max_mensajes'));
        $this->assertSame(500, config('chat.max_caracteres'));
    }

    // ---------- Por IP ----------

    public function test_el_septimo_mensaje_en_un_minuto_da_429_sin_llamar_a_la_ia(): void
    {
        $this->fingirIA();

        for ($i = 0; $i < 6; $i++) {
            $this->chat(['mensaje' => "Mensaje $i"])->assertOk();
        }

        $respuesta = $this->chat(['mensaje' => 'Otro'])->assertStatus(429);

        $this->assertSame(['ok' => false, 'respuesta' => config('chat.mensajes.limite_minuto')], $respuesta->json());
        $this->assertNotNull($respuesta->headers->get('Retry-After'));
        Http::assertSentCount(6);
    }

    public function test_pasado_el_minuto_puede_volver_a_escribir(): void
    {
        $this->fingirIA();

        for ($i = 0; $i < 7; $i++) {
            $this->chat(['mensaje' => "Mensaje $i"]);
        }
        $this->chat(['mensaje' => 'Otro'])->assertStatus(429);

        Carbon::setTestNow(now()->addSeconds(61));

        $this->chat(['mensaje' => 'Ya pasó el minuto'])->assertOk();
    }

    public function test_cada_ip_cuenta_por_separado(): void
    {
        $this->fingirIA();

        for ($i = 0; $i < 6; $i++) {
            $this->chat(['mensaje' => "Mensaje $i"], '203.0.113.1')->assertOk();
        }
        $this->chat(['mensaje' => 'Otro'], '203.0.113.1')->assertStatus(429);

        $this->chat(['mensaje' => 'Hola'], '203.0.113.2')->assertOk();
    }

    public function test_el_limite_diario_por_ip_da_429_con_su_propio_mensaje(): void
    {
        config(['chat.limite_dia' => 8]);
        $this->fingirIA();

        // 6 por minuto: dos ventanas de un minuto para llegar a 8 en el día
        for ($i = 0; $i < 6; $i++) {
            $this->chat(['mensaje' => "Mensaje $i"])->assertOk();
        }
        Carbon::setTestNow(now()->addSeconds(61));
        for ($i = 0; $i < 2; $i++) {
            $this->chat(['mensaje' => "Mensaje $i"])->assertOk();
        }

        $respuesta = $this->chat(['mensaje' => 'Otro'])->assertStatus(429);

        $this->assertSame(config('chat.mensajes.limite_dia'), $respuesta->json('respuesta'));
        Http::assertSentCount(8);
    }

    public function test_el_visitante_no_puede_cambiar_su_ip_con_x_forwarded_for(): void
    {
        $this->fingirIA();

        // Detrás del proxy de Railway la IP real va al final de X-Forwarded-For; lo que ponga el
        // visitante al principio no cuenta
        for ($i = 1; $i <= 6; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
                ->withHeader('X-Forwarded-For', "6.6.6.$i, 203.0.113.50")
                ->postJson('/chat', ['mensaje' => "Mensaje $i"])
                ->assertOk();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->withHeader('X-Forwarded-For', '6.6.6.99, 203.0.113.50')
            ->postJson('/chat', ['mensaje' => 'Otro'])
            ->assertStatus(429);
    }

    // ---------- Global diario ----------

    public function test_al_llegar_al_tope_global_responde_el_mensaje_amable_sin_llamar_a_la_ia(): void
    {
        config(['chat.limite_global_dia' => 3]);
        $this->fingirIA();

        for ($i = 0; $i < 3; $i++) {
            $this->chat(['mensaje' => "Mensaje $i"], "203.0.113.$i")->assertOk()->assertJson(['ok' => true]);
        }

        $this->chat(['mensaje' => 'Otro'], '203.0.113.9')
            ->assertOk()
            ->assertExactJson(['ok' => false, 'respuesta' => config('chat.mensajes.limite_global')]);

        Http::assertSentCount(3);
    }

    public function test_el_tope_global_se_reinicia_a_medianoche_en_la_zona_del_consultorio_no_en_utc(): void
    {
        config(['chat.limite_global_dia' => 2]);
        $this->fingirIA();

        Carbon::setTestNow('2026-11-10 12:00:00'); // 06:00 del 10 en México
        $this->chat(['mensaje' => 'a'], '203.0.113.1')->assertJson(['ok' => true]);
        $this->chat(['mensaje' => 'b'], '203.0.113.2')->assertJson(['ok' => true]);
        $this->chat(['mensaje' => 'c'], '203.0.113.3')->assertJson(['ok' => false]);

        // Ya es 11 en UTC pero todavía 10 (10:00 pm) en México: sigue el mismo día
        Carbon::setTestNow('2026-11-11 04:00:00');
        $this->chat(['mensaje' => 'd'], '203.0.113.4')->assertJson(['ok' => false]);

        // 01:00 am del 11 en México: día nuevo
        Carbon::setTestNow('2026-11-11 07:00:00');
        $this->chat(['mensaje' => 'e'], '203.0.113.5')->assertJson(['ok' => true]);

        Http::assertSentCount(3);
    }

    public function test_una_llamada_a_la_ia_que_falla_tambien_gasta_cuota(): void
    {
        $this->fingirIACaida(500);

        $this->chat(['mensaje' => 'Hola'])->assertOk()->assertJson(['ok' => false]);

        $this->assertSame(1, app(LimiteGlobalIA::class)->usadas());
    }

    public function test_el_contador_global_nunca_deja_pasar_mas_del_tope(): void
    {
        config(['chat.limite_global_dia' => 5]);
        $limite = app(LimiteGlobalIA::class);

        $permitidas = 0;
        for ($i = 0; $i < 9; $i++) {
            $permitidas += $limite->reservar() ? 1 : 0;
        }

        $this->assertSame(5, $permitidas);
    }

    public function test_sin_contador_fiable_no_se_gasta_cuota(): void
    {
        Cache::shouldReceive('add')->andThrow(new \RuntimeException('caché caído'));

        $this->assertFalse(app(LimiteGlobalIA::class)->reservar());
    }
}
