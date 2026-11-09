<?php

namespace Tests\Feature\Chat;

use App\Services\Chat\FirmaHistorial;
use App\Services\Chat\LimiteGlobalIA;
use Illuminate\Support\Facades\Http;

/** El bucle de herramientas: tope de vueltas, tope global en cada llamada y fallos a medias */
class ChatBucleTest extends ChatTestCase
{
    public function test_el_tope_de_vueltas_corta_el_bucle_con_un_mensaje_amable(): void
    {
        $this->crearServicio();
        // La IA pide una herramienta una y otra vez
        Http::fake(['api.groq.com/*' => Http::response($this->iaLlamada('listar_servicios', []))]);

        $this->chat(['mensaje' => 'Hola'])
            ->assertOk()
            ->assertExactJson(['ok' => false, 'respuesta' => config('chat.mensajes.vueltas')]);

        $this->assertSame(4, $this->llamadasIA());
        // Cada llamada a la IA gastó del tope global
        $this->assertSame(4, app(LimiteGlobalIA::class)->usadas());
    }

    public function test_el_tope_de_vueltas_es_configurable(): void
    {
        config(['chat.max_vueltas' => 2]);
        Http::fake(['api.groq.com/*' => Http::response($this->iaLlamada('listar_servicios', []))]);

        $this->chat(['mensaje' => 'Hola'])->assertOk()->assertJson(['ok' => false]);

        $this->assertSame(2, $this->llamadasIA());
    }

    public function test_el_tope_global_se_revisa_en_cada_vuelta_y_corta_el_bucle(): void
    {
        config(['chat.limite_global_dia' => 2]);
        Http::fake(['api.groq.com/*' => Http::response($this->iaLlamada('listar_servicios', []))]);

        $this->chat(['mensaje' => 'Hola'])
            ->assertOk()
            ->assertExactJson(['ok' => false, 'respuesta' => config('chat.mensajes.limite_global')]);

        $this->assertSame(2, $this->llamadasIA());
    }

    public function test_si_la_ia_falla_a_mitad_del_bucle_responde_el_mensaje_amable(): void
    {
        $this->crearServicio();
        Http::fake(['api.groq.com/*' => Http::sequence()
            ->push($this->iaLlamada('listar_servicios', []))
            ->push(['error' => ['message' => 'detalle', 'type' => 'internal_server_error']], 500)]);

        $this->chat(['mensaje' => 'Hola'])
            ->assertOk()
            ->assertExactJson(['ok' => false, 'respuesta' => config('ia.mensaje_error')]);

        $this->assertSame(2, $this->llamadasIA());
    }

    public function test_solo_se_atienden_las_primeras_herramientas_de_cada_vuelta(): void
    {
        $this->crearServicio();
        $llamadas = array_fill(0, 5, ['listar_servicios', []]);
        $this->fingirSecuencia($this->iaLlamadas(...$llamadas), $this->iaTexto('Listo'));

        $this->chat(['mensaje' => 'Hola'])->assertOk();

        $resultados = array_map(
            fn ($m) => json_decode($m['content'], true),
            array_values(array_filter($this->mensajesDeLlamada(-1), fn ($m) => $m['role'] === 'tool')),
        );

        $this->assertCount(5, $resultados);
        $this->assertArrayHasKey('servicios', $resultados[2]);
        $this->assertSame('demasiadas_herramientas', $resultados[3]['error']);
        $this->assertSame('demasiadas_herramientas', $resultados[4]['error']);
    }

    public function test_el_resumen_de_la_cita_y_su_confirmacion_no_gastan_otra_llamada_a_la_ia(): void
    {
        $servicio = $this->crearServicio();
        $this->crearHorarioLunes();
        $datos = $this->datosAgenda($servicio);

        $propuesta = $this->proponer($datos);
        $this->assertSame(1, $this->llamadasIA());
        $this->assertSame(1, app(LimiteGlobalIA::class)->usadas());

        $this->confirmar($propuesta, $datos)->assertOk()->assertJson(['ok' => true]);
        $this->assertSame(2, app(LimiteGlobalIA::class)->usadas());
    }

    // ---------- Respuestas largas y el historial ----------

    public function test_una_respuesta_larga_se_recorta_antes_de_firmarla_y_vuelve_valida_en_el_historial(): void
    {
        $this->fingirIA(str_repeat('palabra ', 400));   // 3.200 caracteres

        $respuesta = $this->chat(['mensaje' => 'Hola'])->assertOk();

        $this->assertLessThanOrEqual(1200, mb_strlen($respuesta->json('respuesta')));
        $this->assertTrue(app(FirmaHistorial::class)->valida($respuesta->json('respuesta'), $respuesta->json('firma')));

        $this->fingirIA();
        $this->chat([
            'mensaje' => 'Gracias',
            'historial' => [
                ['rol' => 'user', 'texto' => 'Hola'],
                ['rol' => 'assistant', 'texto' => $respuesta->json('respuesta'), 'firma' => $respuesta->json('firma')],
            ],
        ])->assertOk();
    }

    public function test_el_historial_acepta_respuestas_del_asistente_de_hasta_1200_caracteres_y_no_mas(): void
    {
        $this->fingirIA();
        $largo = str_repeat('a', 1200);
        $demasiado = str_repeat('a', 1201);

        $this->chat(['mensaje' => 'Hola', 'historial' => [
            ['rol' => 'user', 'texto' => 'Hola'],
            ['rol' => 'assistant', 'texto' => $largo, 'firma' => $this->firma($largo)],
        ]])->assertOk();

        $this->chat(['mensaje' => 'Hola', 'historial' => [
            ['rol' => 'user', 'texto' => 'Hola'],
            ['rol' => 'assistant', 'texto' => $demasiado, 'firma' => $this->firma($demasiado)],
        ]])->assertStatus(422);
    }

    public function test_los_mensajes_del_visitante_siguen_limitados_a_500_caracteres_aunque_el_asistente_pueda_mas(): void
    {
        Http::fake();

        $this->chat(['mensaje' => 'Hola', 'historial' => [
            ['rol' => 'user', 'texto' => str_repeat('a', 501)],
            ['rol' => 'assistant', 'texto' => 'Hola', 'firma' => $this->firma('Hola')],
        ]])->assertStatus(422);

        Http::assertNothingSent();
    }

    // ---------- Campo pendiente ----------

    public function test_el_campo_pendiente_acepta_nulo_vacio_o_un_token_inventado_sin_efecto(): void
    {
        $this->fingirIA();

        foreach ([null, '', 'token-inventado', 'a.b'] as $pendiente) {
            $this->chat(['mensaje' => 'Hola', 'pendiente' => $pendiente])->assertOk()->assertJson(['ok' => true, 'pendiente' => null]);
        }
    }

    public function test_el_campo_pendiente_debe_ser_texto_y_no_demasiado_largo(): void
    {
        Http::fake();

        $this->chat(['mensaje' => 'Hola', 'pendiente' => ['a']])->assertStatus(422);
        $this->chat(['mensaje' => 'Hola', 'pendiente' => 123])->assertStatus(422);
        $this->chat(['mensaje' => 'Hola', 'pendiente' => str_repeat('a', 2001)])->assertStatus(422);

        Http::assertNothingSent();
    }
}
