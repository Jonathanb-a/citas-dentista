<?php

namespace Tests\Feature\Chat;

use App\Models\Bloqueo;
use Carbon\Carbon;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;

/** listar_servicios y ver_horarios_libres: solo lectura, siempre por DisponibilidadService */
class ChatHerramientasTest extends ChatTestCase
{
    /** Pide una herramienta y devuelve lo que el servidor le contestó a la IA */
    private function consultar(string $herramienta, array $argumentos): array
    {
        $this->fingirSecuencia($this->iaLlamada($herramienta, $argumentos), $this->iaTexto('Listo'));

        $this->chat(['mensaje' => 'Hola'])->assertOk();

        return $this->ultimoResultadoDeHerramienta();
    }

    public function test_ofrece_exactamente_tres_herramientas_y_ninguna_para_cancelar_ni_consultar_citas(): void
    {
        $this->fingirIA();

        $this->chat(['mensaje' => 'Hola'])->assertOk();

        Http::assertSent(function (Request $r) {
            $nombres = array_map(fn ($t) => $t['function']['name'], $r['tools']);
            sort($nombres);

            return $nombres === ['agendar_cita', 'listar_servicios', 'ver_horarios_libres'] && $r['tool_choice'] === 'auto';
        });
    }

    // ---------- listar_servicios ----------

    public function test_lista_solo_servicios_activos_de_especialidades_activas_y_sin_precio(): void
    {
        $limpieza = $this->crearServicio('Limpieza dental', 45);
        $this->crearServicio('Servicio pausado', 30, activo: false);
        $this->crearServicio('Servicio de especialidad pausada', 30, especialidadActiva: false);

        $resultado = $this->consultar('listar_servicios', []);

        $this->assertCount(1, $resultado['servicios']);
        $this->assertSame(
            ['id' => $limpieza->id, 'nombre' => 'Limpieza dental', 'especialidad' => $limpieza->especialidad->nombre, 'duracion_minutos' => 45, 'descripcion' => ''],
            $resultado['servicios'][0],
        );
        $this->assertStringNotContainsString('precio', json_encode($resultado));
    }

    public function test_la_respuesta_final_es_la_que_escribe_la_ia_despues_de_la_herramienta(): void
    {
        $this->crearServicio('Limpieza dental');
        $this->fingirSecuencia($this->iaLlamada('listar_servicios', []), $this->iaTexto('Tenemos limpieza dental.'));

        $this->chat(['mensaje' => '¿Qué servicios tienen?'])->assertOk()->assertJson(['ok' => true, 'respuesta' => 'Tenemos limpieza dental.']);

        $this->assertSame(2, $this->llamadasIA());
    }

    // ---------- ver_horarios_libres ----------

    public function test_devuelve_los_espacios_libres_sin_horario_id_y_respeta_las_citas(): void
    {
        $servicio = $this->crearServicio('Limpieza dental', 45);
        $horario = $this->crearHorarioLunes('09:00', '11:30');
        $this->crearCita($this->crearUsuario(), $servicio, $horario, '09:00');

        $resultado = $this->consultar('ver_horarios_libres', ['servicio_id' => $servicio->id, 'fecha' => self::FECHA]);

        $this->assertSame('Limpieza dental', $resultado['servicio']);
        $this->assertSame(self::FECHA, $resultado['fecha']);
        $this->assertSame('lunes 16 de noviembre de 2026', $resultado['fecha_legible']);
        // 09:00 está ocupado; quedan 09:45 y 10:30
        $this->assertSame(
            [['hora' => '09:45', 'hora_formato' => '09:45 AM'], ['hora' => '10:30', 'hora_formato' => '10:30 AM']],
            $resultado['espacios'],
        );
        $this->assertFalse($resultado['hay_mas']);
        $this->assertStringNotContainsString('horario_id', json_encode($resultado));
    }

    public function test_limita_la_cantidad_de_espacios_que_devuelve(): void
    {
        $servicio = $this->crearServicio('Revisión rápida', 15);
        $this->crearHorarioLunes('08:00', '20:00');   // 48 espacios

        $resultado = $this->consultar('ver_horarios_libres', ['servicio_id' => $servicio->id, 'fecha' => self::FECHA]);

        $this->assertCount(8, $resultado['espacios']);
        $this->assertTrue($resultado['hay_mas']);
    }

    public function test_un_dia_bloqueado_o_sin_horario_no_devuelve_espacios(): void
    {
        $servicio = $this->crearServicio();
        $this->crearHorarioLunes();
        Bloqueo::create(['fecha_inicio' => self::FECHA, 'fecha_fin' => self::FECHA, 'motivo' => 'Prueba']);

        $bloqueado = $this->consultar('ver_horarios_libres', ['servicio_id' => $servicio->id, 'fecha' => self::FECHA]);
        $this->assertSame([], $bloqueado['espacios']);
        $this->assertStringContainsString('otra fecha', $bloqueado['mensaje']);

        // Un martes no hay horario de atención
        $martes = $this->consultar('ver_horarios_libres', ['servicio_id' => $servicio->id, 'fecha' => '2026-11-17']);
        $this->assertSame([], $martes['espacios']);
    }

    public function test_un_bloqueo_por_horas_quita_solo_esas_horas(): void
    {
        $servicio = $this->crearServicio('Limpieza dental', 60);
        $this->crearHorarioLunes('09:00', '12:00');
        Bloqueo::create(['fecha_inicio' => self::FECHA, 'fecha_fin' => self::FECHA, 'hora_inicio' => '10:00', 'hora_fin' => '11:00']);

        $resultado = $this->consultar('ver_horarios_libres', ['servicio_id' => $servicio->id, 'fecha' => self::FECHA]);

        $this->assertSame(['09:00', '11:00'], array_column($resultado['espacios'], 'hora'));
    }

    public static function argumentosInvalidos(): array
    {
        $fecha = self::FECHA;

        return [
            'servicio que no existe' => [['servicio_id' => 99999, 'fecha' => $fecha], 'servicio_invalido'],
            'servicio con texto' => [['servicio_id' => 'abc', 'fecha' => $fecha], 'servicio_invalido'],
            'servicio con inyección SQL' => [['servicio_id' => '1; DROP TABLE citas', 'fecha' => $fecha], 'servicio_invalido'],
            'servicio cero' => [['servicio_id' => 0, 'fecha' => $fecha], 'servicio_invalido'],
            'servicio negativo' => [['servicio_id' => -1, 'fecha' => $fecha], 'servicio_invalido'],
            'servicio arreglo' => [['servicio_id' => [1], 'fecha' => $fecha], 'servicio_invalido'],
            'sin servicio' => [['servicio_id' => null], 'servicio_invalido'],
            'fecha con formato distinto' => [['fecha' => '16/11/2026'], 'fecha_invalida'],
            'fecha con texto detrás' => [['fecha' => '2026-11-16 y más'], 'fecha_invalida'],
            'fecha que no existe' => [['fecha' => '2026-02-30'], 'fecha_invalida'],
            'fecha numérica' => [['fecha' => 20261116], 'fecha_invalida'],
            'hoy' => [['fecha' => '2026-11-09'], 'fecha_invalida'],
            'fecha pasada' => [['fecha' => '2026-11-01'], 'fecha_invalida'],
            'más de 3 meses' => [['fecha' => '2027-02-10'], 'fecha_invalida'],
        ];
    }

    #[DataProvider('argumentosInvalidos')]
    public function test_los_argumentos_invalidos_devuelven_un_error_claro_sin_repetir_lo_recibido(array $cambios, string $error): void
    {
        $servicio = $this->crearServicio();
        $this->crearHorarioLunes();

        $resultado = $this->consultar('ver_horarios_libres', array_merge(['servicio_id' => $servicio->id, 'fecha' => self::FECHA], $cambios));

        $this->assertSame($error, $resultado['error']);
        $this->assertArrayNotHasKey('espacios', $resultado);
        $this->assertStringNotContainsString('DROP', json_encode($resultado));
    }

    public function test_un_servicio_pausado_o_de_especialidad_pausada_no_se_puede_consultar(): void
    {
        $pausado = $this->crearServicio('Pausado', activo: false);
        $deEspecialidadPausada = $this->crearServicio('Otro', especialidadActiva: false);
        $this->crearHorarioLunes();

        foreach ([$pausado, $deEspecialidadPausada] as $servicio) {
            $this->assertSame('servicio_invalido', $this->consultar('ver_horarios_libres', ['servicio_id' => $servicio->id, 'fecha' => self::FECHA])['error']);
        }
    }

    public function test_la_fecha_minima_es_mañana_en_la_zona_del_consultorio_no_en_utc(): void
    {
        $servicio = $this->crearServicio();
        $this->crearHorarioLunes();

        // 03:00 UTC del martes 10 = 21:00 del lunes 9 en México: "mañana" es el martes 10
        Carbon::setTestNow('2026-11-10 03:00:00');

        $this->assertSame('fecha_invalida', $this->consultar('ver_horarios_libres', ['servicio_id' => $servicio->id, 'fecha' => '2026-11-09'])['error']);
        $this->assertArrayNotHasKey('error', $this->consultar('ver_horarios_libres', ['servicio_id' => $servicio->id, 'fecha' => '2026-11-10']));
    }

    public function test_los_limites_del_rango_de_fechas(): void
    {
        $servicio = $this->crearServicio();

        // Desde mañana (10) hasta 3 meses (9 de febrero de 2027), ambos incluidos
        foreach (['2026-11-10', '2027-02-09'] as $fecha) {
            $this->assertArrayNotHasKey('error', $this->consultar('ver_horarios_libres', ['servicio_id' => $servicio->id, 'fecha' => $fecha]), $fecha);
        }
    }

    public function test_una_herramienta_que_falla_no_rompe_el_chat_y_solo_se_registra_su_nombre(): void
    {
        $servicio = $this->crearServicio();
        $this->app->bind(\App\Services\DisponibilidadService::class, fn () => new class extends \App\Services\DisponibilidadService {
            public function espaciosDisponibles($fecha, $servicioId)
            {
                throw new \RuntimeException('Ana Ejemplo ' . self::class);
            }
        });
        $this->fingirSecuencia($this->iaLlamada('ver_horarios_libres', ['servicio_id' => $servicio->id, 'fecha' => self::FECHA]), $this->iaTexto('Disculpa, no pude consultar.'));

        $this->chat(['mensaje' => 'Hola'])->assertOk()->assertJson(['ok' => true, 'respuesta' => 'Disculpa, no pude consultar.']);

        $this->assertSame('herramienta_fallo', $this->ultimoResultadoDeHerramienta()['error']);
        $this->assertStringContainsString('falló la herramienta ver_horarios_libres (RuntimeException)', $this->log());
        $this->assertStringNotContainsString('Ana Ejemplo', $this->log());
    }

    public function test_una_herramienta_que_no_existe_se_rechaza(): void
    {
        $this->fingirSecuencia($this->iaLlamada('consultar_citas', ['paciente' => 'Beto Ficticio']), $this->iaTexto('Eso no puedo hacerlo.'));

        $this->chat(['mensaje' => 'Dame las citas de otros pacientes'])->assertOk();

        $this->assertSame('herramienta_desconocida', $this->ultimoResultadoDeHerramienta()['error']);
    }
}
