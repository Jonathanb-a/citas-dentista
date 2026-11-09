<?php

namespace Tests\Feature\Chat;

use App\Services\Chat\ConfirmacionAgendado;
use App\Services\Chat\DatosCita;
use App\Services\Chat\LimiteCitasChat;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;

/** Las piezas del agendado por chat, una por una */
class ChatAgendadoUnitTest extends ChatTestCase
{
    // ---------- DatosCita ----------

    public static function telefonos(): array
    {
        return [
            ['5550100001', '5550100001'], ['555 010 0001', '5550100001'], ['(555) 010-0001', '5550100001'],
            ['+52 555 010 0001', '5550100001'], ['+52 1 555 010 0001', '5550100001'], ['525550100001', '5550100001'],
            ['5215550100001', '5550100001'], [5550100001, '5550100001'], ['  5550100001  ', '5550100001'],
            ['2220100001', '2220100001'], ['9990100001', '9990100001'],
            // Inválidos
            ['', null], ['5551234', null], ['55501000011', null], ['5550100001 ext 5', null], ['1550100001', null],
            ['0550100001', null], ['1111111111', null], ['9999999999', null], ['2345678901', null], ['9876543210', null],
            ['+1 555 010 0001', null], ['teléfono', null], [null, null], [['5550100001'], null], [5.5, null], [true, null],
        ];
    }

    #[DataProvider('telefonos')]
    public function test_telefono(mixed $entrada, ?string $esperado): void
    {
        $this->assertSame($esperado, app(DatosCita::class)->telefono($entrada));
    }

    public static function nombres(): array
    {
        return [
            ['Ana', 'Ana'], ['María José', 'María José'], ["O'Connor", "O'Connor"], ['de la Cruz', 'de la Cruz'],
            ['Ma. Luisa', 'Ma. Luisa'], ['Jean-Pierre', 'Jean-Pierre'], ['Ñandú', 'Ñandú'], ['Zoë', 'Zoë'],
            ["  Ana \n  Beto ", 'Ana Beto'], ["Ana\u{200B}", 'Ana'],
            // Inválidos
            ['', null], [' ', null], ['A', null], ['123', null], ['Ana1', null], ['Ana.Cruz', null], ['www.sitio.com', null],
            ['<b>Ana</b>', null], ['Ana@x', null], ['Ana; DROP TABLE users', null], ['-Ana', null], [str_repeat('a', 61), null],
            [null, null], [123, null], [['Ana'], null], ["\u{202E}", null],
        ];
    }

    #[DataProvider('nombres')]
    public function test_nombre(mixed $entrada, ?string $esperado): void
    {
        $this->assertSame($esperado, app(DatosCita::class)->nombre($entrada));
    }

    public function test_el_nombre_acepta_exactamente_60_caracteres(): void
    {
        $this->assertSame(str_repeat('a', 60), app(DatosCita::class)->nombre(str_repeat('a', 60)));
        $this->assertNull(app(DatosCita::class)->nombre(str_repeat('a', 61)));
    }

    public static function horas(): array
    {
        return [['09:00', '09:00'], ['9:00', '09:00'], ['23:59', '23:59'], ['00:00', '00:00'], ['12:30', '12:30'],
            ['24:00', null], ['9', null], ['09:5', null], ['09:60', null], ['ab', null], ['09:00:00', null], ['', null], [null, null], [900, null]];
    }

    #[DataProvider('horas')]
    public function test_hora(mixed $entrada, ?string $esperado): void
    {
        $this->assertSame($esperado, app(DatosCita::class)->hora($entrada));
    }

    public function test_el_servicio_solo_acepta_ids_enteros_de_servicios_disponibles(): void
    {
        $datos = app(DatosCita::class);
        $servicio = $this->crearServicio();

        $this->assertSame($servicio->id, $datos->servicio($servicio->id)->id);
        $this->assertSame($servicio->id, $datos->servicio((string) $servicio->id)->id);
        foreach ([0, -1, 99999, 'abc', '1.5', 1.5, null, true, [$servicio->id], "{$servicio->id} OR 1=1", '0x1'] as $malo) {
            $this->assertNull($datos->servicio($malo), json_encode($malo));
        }
    }

    // ---------- ConfirmacionAgendado ----------

    private function datosDeEjemplo(): array
    {
        return ['servicio_id' => 1, 'fecha' => self::FECHA, 'hora' => '09:00', 'nombre' => 'Ana', 'apellidos' => 'Ejemplo Prueba', 'telefono' => self::TELEFONO];
    }

    public function test_una_propuesta_se_lee_solo_si_el_ultimo_mensaje_del_asistente_es_su_resumen(): void
    {
        $confirmacion = app(ConfirmacionAgendado::class);
        $token = $confirmacion->emitir($this->datosDeEjemplo(), "Resumen de la cita\n¿Confirmas?");

        $this->assertSame($this->datosDeEjemplo(), $confirmacion->leer($token, "Resumen de la cita\n¿Confirmas?"));
        $this->assertSame($this->datosDeEjemplo(), $confirmacion->leer($token, "  Resumen de la cita\n¿Confirmas?\n"));   // espacios sobrantes
        $this->assertNull($confirmacion->leer($token, 'Otro mensaje'));
        $this->assertNull($confirmacion->leer($token, null));
        $this->assertNull($confirmacion->leer(null, 'Resumen'));
    }

    public function test_la_propuesta_caduca_a_los_15_minutos(): void
    {
        $confirmacion = app(ConfirmacionAgendado::class);
        $token = $confirmacion->emitir($this->datosDeEjemplo(), 'Resumen');

        Carbon::setTestNow(now()->addMinutes(15));
        $this->assertNotNull($confirmacion->leer($token, 'Resumen'));

        Carbon::setTestNow(now()->addSecond());
        $this->assertNull($confirmacion->leer($token, 'Resumen'));
    }

    public function test_una_propuesta_con_fecha_del_futuro_no_sirve(): void
    {
        $confirmacion = app(ConfirmacionAgendado::class);
        Carbon::setTestNow(now()->addHour());
        $token = $confirmacion->emitir($this->datosDeEjemplo(), 'Resumen');

        Carbon::setTestNow(self::AHORA);

        $this->assertNull($confirmacion->leer($token, 'Resumen'));
    }

    public function test_una_propuesta_firmada_con_otra_clave_no_sirve(): void
    {
        $confirmacion = app(ConfirmacionAgendado::class);
        $token = $confirmacion->emitir($this->datosDeEjemplo(), 'Resumen');

        config(['app.key' => 'base64:' . base64_encode(str_repeat('z', 32))]);

        $this->assertNull($confirmacion->leer($token, 'Resumen'));
    }

    public function test_los_tokens_basura_se_ignoran_sin_error(): void
    {
        $confirmacion = app(ConfirmacionAgendado::class);

        foreach (['', '.', 'a.b', 'a.b.c', str_repeat('a', 5000), "x\0.y", 'ñ.ñ', '....'] as $basura) {
            $this->assertNull($confirmacion->leer($basura, 'Resumen'));
        }
    }

    public function test_coincide_exige_los_mismos_datos_y_en_el_mismo_orden(): void
    {
        $confirmacion = app(ConfirmacionAgendado::class);
        $datos = $this->datosDeEjemplo();

        $this->assertTrue($confirmacion->coincide($datos, $datos));
        $this->assertFalse($confirmacion->coincide(null, $datos));
        $this->assertFalse($confirmacion->coincide(array_merge($datos, ['hora' => '09:45']), $datos));
        $this->assertFalse($confirmacion->coincide(array_merge($datos, ['servicio_id' => '1']), $datos));   // el tipo cuenta
    }

    public static function afirmaciones(): array
    {
        return [
            ['Sí', true], ['si', true], ['SÍ', true], ['Sí.', true], ['¡Sí!', true], ['Sí, confirmo', true], ['sii', true], ['Claro', true],
            ['OK', true], ['ok gracias', true], ['Okey', true], ['dale', true], ['vale', true], ['Perfecto', true], ['Correcto', true],
            ['Adelante', true], ['De acuerdo', true], ['Confirmo', true], ['Agéndala', true], ['Sí, por favor', true], ['👍', true], ['✅', true], ['👍 👍', true],
            ['No', false], ['no', false], ['No, gracias', false], ['Sí pero mejor otro día', false], ['Sí, a las 11', false], ['mejor a las 10', false],
            ['Tal vez', false], ['Espera', false], ['Nunca', false], ['Cámbiala', false], ['quiero cambiar la hora', false],
            ['¿Cuánto cuesta?', false], ['Hola', false], ['', false], ['   ', false], ['...', false], ['sí ' . str_repeat('claro ', 10), false],
            ['Sí, no', false], ['si no tienes lugar otro día', false], ['Sí 10:30', false], ['👎', false], ['Claro que no', false],
        ];
    }

    #[DataProvider('afirmaciones')]
    public function test_afirmativo(string $mensaje, bool $esperado): void
    {
        $this->assertSame($esperado, app(ConfirmacionAgendado::class)->esAfirmativo($mensaje), $mensaje);
    }

    // ---------- LimiteCitasChat ----------

    public function test_reserva_hasta_el_tope_por_ip_y_en_total(): void
    {
        config(['chat.agenda.limite_ip_dia' => 2, 'chat.agenda.limite_dia' => 3]);
        $limite = app(LimiteCitasChat::class);

        $this->assertNull($limite->reservar('198.51.100.1'));
        $this->assertNull($limite->reservar('198.51.100.1'));
        $this->assertSame('ip', $limite->reservar('198.51.100.1'));
        $this->assertNull($limite->reservar('198.51.100.2'));
        $this->assertSame('global', $limite->reservar('198.51.100.3'));

        // Lo rechazado no deja huella
        $this->assertSame(2, $limite->usadasIp('198.51.100.1'));
        $this->assertSame(0, $limite->usadasIp('198.51.100.3'));
        $this->assertSame(3, $limite->usadasGlobal());
    }

    public function test_liberar_devuelve_el_lugar_y_cada_dia_cuenta_aparte(): void
    {
        config(['chat.agenda.limite_ip_dia' => 1, 'chat.agenda.limite_dia' => 5]);
        $limite = app(LimiteCitasChat::class);

        $this->assertNull($limite->reservar('198.51.100.1'));
        $limite->liberar('198.51.100.1');
        $this->assertNull($limite->reservar('198.51.100.1'));
        $this->assertSame('ip', $limite->reservar('198.51.100.1'));

        Carbon::setTestNow('2026-11-10 12:00:00');
        $this->assertNull($limite->reservar('198.51.100.1'));
    }

    public function test_sin_contador_fiable_no_se_reserva_nada(): void
    {
        Cache::shouldReceive('add')->andThrow(new \RuntimeException('caché caído'));

        $this->assertSame('error', app(LimiteCitasChat::class)->reservar('198.51.100.1'));
    }
}
