<?php

namespace Tests\Feature\Chat;

use App\Services\Chat\InstruccionesChat;
use App\Services\Chat\LimpiezaRespuesta;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;

/** Acotaciones del modelo al final de sus respuestas: regla en el prompt y limpieza en el servidor */
class ChatLimpiezaRespuestaTest extends ChatTestCase
{
    // ---------- La regla en las instrucciones ----------

    public function test_las_instrucciones_prohiben_las_acotaciones_entre_parentesis_o_corchetes(): void
    {
        $texto = app(InstruccionesChat::class)->texto();

        $this->assertStringContainsString(
            'Nunca escribas acotaciones, notas internas ni descripciones de tu estado entre paréntesis o corchetes; solo habla directamente con el paciente.',
            $texto,
        );
    }

    // ---------- El limpiador ----------

    public static function conAcotacion(): array
    {
        return [
            'esperando, al final' => ['¿Para qué día te gustaría tu cita? (Esperando respuesta del usuario)', '¿Para qué día te gustaría tu cita?'],
            'en una línea aparte' => ["¿Cómo te llamas?\n\n(Esperando respuesta)", '¿Cómo te llamas?'],
            'corchetes' => ['Dime tu nombre completo. [Aguardando respuesta del paciente]', 'Dime tu nombre completo.'],
            'aguardando' => ['Perfecto. (Aguardando confirmación)', 'Perfecto.'],
            'pausa' => ['Un momento. (Pausa)', 'Un momento.'],
            'nota' => ['Listo, aquí estoy. (Nota: mensaje de prueba del sistema)', 'Listo, aquí estoy.'],
            'pensando' => ['Déjame revisar. (Pensando...)', 'Déjame revisar.'],
            'escribiendo' => ['Hola. (escribiendo)', 'Hola.'],
            'silencio' => ['Hola. [Silencio]', 'Hola.'],
            'sistema' => ['Hola. (Sistema: sin novedades)', 'Hola.'],
            'sin importar mayúsculas' => ['Hola. (ESPERANDO RESPUESTA)', 'Hola.'],
            'con acento' => ['Hola. (Acotación interna)', 'Hola.'],
            'con espacios dentro' => ['Hola. (   Esperando respuesta   )', 'Hola.'],
            'en cursiva o negrita' => ['Hola. *(Esperando respuesta)*', 'Hola.'],
            'con punto después' => ['Hola. (Esperando respuesta).', 'Hola.'],
            'varias seguidas' => ['Hola. (Pausa) (Esperando respuesta del usuario)', 'Hola.'],
            'mezcla de paréntesis y corchetes' => ['Hola. [Nota: x] (Esperando)', 'Hola.'],
            'sin espacio antes' => ['Hola.(Esperando)', 'Hola.'],
            'después de un paréntesis legítimo' => ['Te ofrezco las 10:00 (10:00 AM). (Esperando respuesta)', 'Te ofrezco las 10:00 (10:00 AM).'],
            'solo la acotación' => ['(Esperando respuesta del usuario)', ''],
            'solo la acotación entre corchetes' => ['  [Pausa]  ', ''],
        ];
    }

    #[DataProvider('conAcotacion')]
    public function test_quita_la_acotacion_del_final(string $entrada, string $esperado): void
    {
        $this->assertSame($esperado, app(LimpiezaRespuesta::class)->limpiar($entrada));
    }

    public static function sinCambios(): array
    {
        return [
            'hora legítima al final' => ['Te ofrezco este horario (10:00 AM)'],
            'dirección entre paréntesis' => ['Estamos en Calle Ficticia 123 (frente al parque)'],
            'varios paréntesis legítimos' => ['Tengo (09:00 AM) y (10:30 AM) disponibles (los dos de la mañana)'],
            'una palabra que solo se parece' => ['Pregunta por la Dra. Prueba (Esperanza es su asistente)'],
            'otra palabra parecida' => ['Es un horario (Notable por su tranquilidad)'],
            'acotación a mitad de la frase' => ['Hola (Nota: texto) ¿qué día te gustaría?'],
            'paréntesis sin cerrar' => ['Hola (Esperando'],
            'corchete sin abrir' => ['Hola Esperando]'],
            'sin paréntesis' => ['Con gusto te ayudo a agendar tu cita.'],
            'texto vacío' => [''],
            'paréntesis vacío' => ['Hola ()'],
            'anidado' => ['Hola (Esperando (respuesta))'],
        ];
    }

    #[DataProvider('sinCambios')]
    public function test_no_toca_los_parentesis_legitimos(string $entrada): void
    {
        $this->assertSame(trim($entrada), app(LimpiezaRespuesta::class)->limpiar($entrada));
    }

    public function test_un_parentesis_larguisimo_no_se_toca(): void
    {
        $largo = 'Hola. (Esperando ' . str_repeat('respuesta ', 30) . ')';

        $this->assertSame($largo, app(LimpiezaRespuesta::class)->limpiar($largo));
    }

    public function test_limpiar_dos_veces_da_lo_mismo(): void
    {
        $limpiador = app(LimpiezaRespuesta::class);
        $una = $limpiador->limpiar('Hola. (Pausa) (Esperando respuesta)');

        $this->assertSame($una, $limpiador->limpiar($una));
    }

    // ---------- En el chat: antes de firmar y devolver ----------

    public function test_la_respuesta_sale_limpia_y_la_firma_es_la_del_texto_limpio(): void
    {
        $this->fingirIA('¿Para qué día te gustaría tu cita? (Esperando respuesta del usuario)');

        $respuesta = $this->chat(['mensaje' => 'Quiero una cita'])->assertOk();

        $respuesta->assertJson(['ok' => true, 'respuesta' => '¿Para qué día te gustaría tu cita?']);
        $this->assertSame($this->firma('¿Para qué día te gustaría tu cita?'), $respuesta->json('firma'));

        // Y vuelve en el historial sin problema
        $this->fingirIA('Anotado.');
        $this->chat([
            'mensaje' => 'El lunes',
            'historial' => [
                ['rol' => 'user', 'texto' => 'Quiero una cita'],
                ['rol' => 'assistant', 'texto' => $respuesta->json('respuesta'), 'firma' => $respuesta->json('firma')],
            ],
        ])->assertOk()->assertJson(['ok' => true]);
    }

    public function test_el_historial_que_se_le_manda_a_la_ia_ya_va_sin_la_acotacion(): void
    {
        $this->fingirIA('¿Cómo te llamas? [Aguardando respuesta]');
        $primera = $this->chat(['mensaje' => 'Hola'])->assertOk();

        $this->fingirIA('Mucho gusto.');
        $this->chat([
            'mensaje' => 'Me llamo Ana Ejemplo',
            'historial' => [
                ['rol' => 'user', 'texto' => 'Hola'],
                ['rol' => 'assistant', 'texto' => $primera->json('respuesta'), 'firma' => $primera->json('firma')],
            ],
        ])->assertOk();

        $mensajes = $this->mensajesDeLlamada(-1);
        $this->assertSame('¿Cómo te llamas?', $mensajes[2]['content']);
    }

    public function test_si_la_respuesta_era_solo_una_acotacion_se_responde_el_mensaje_amable(): void
    {
        $this->fingirIA('(Esperando respuesta del usuario)');

        $this->chat(['mensaje' => 'Hola'])
            ->assertOk()
            ->assertExactJson(['ok' => false, 'respuesta' => config('ia.mensaje_error')]);
    }

    public function test_la_respuesta_con_un_parentesis_legitimo_no_cambia(): void
    {
        $texto = 'Tengo lugar el lunes a las 10:00 (10:00 AM). Estamos en Calle Ficticia 123 (frente al parque)';
        $this->fingirIA($texto);

        $this->chat(['mensaje' => 'Hola'])->assertOk()->assertJson(['ok' => true, 'respuesta' => $texto]);
    }

    public function test_tambien_se_limpia_la_respuesta_que_viene_despues_de_usar_una_herramienta(): void
    {
        $this->crearServicio('Limpieza dental');
        $this->fingirSecuencia($this->iaLlamada('listar_servicios', []), $this->iaTexto('Tenemos limpieza dental. (Pausa)'));

        $this->chat(['mensaje' => '¿Qué servicios tienen?'])
            ->assertOk()
            ->assertJson(['ok' => true, 'respuesta' => 'Tenemos limpieza dental.']);
    }

    public function test_los_resumenes_que_escribe_el_servidor_no_pasan_por_el_limpiador(): void
    {
        $servicio = $this->crearServicio('Limpieza dental');
        $this->crearHorarioLunes();

        $propuesta = $this->proponer($this->datosAgenda($servicio));

        // Termina exactamente como la plantilla y su firma sigue siendo válida
        $this->assertStringEndsWith('o dime qué quieres cambiar.', $propuesta->json('respuesta'));
        $this->assertSame($this->firma($propuesta->json('respuesta')), $propuesta->json('firma'));
        $this->assertNotNull($propuesta->json('pendiente'));
    }

    public function test_la_acotacion_no_llega_a_los_logs_ni_cambia_lo_que_se_registra(): void
    {
        $this->fingirIA('Hola. (Esperando respuesta de Ana Ejemplo)');

        $this->chat(['mensaje' => 'Hola'])->assertOk();

        $this->assertStringNotContainsString('Ana Ejemplo', $this->log());
        Http::assertSentCount(1);
    }
}
