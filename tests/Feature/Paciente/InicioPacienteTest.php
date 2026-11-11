<?php

namespace Tests\Feature\Paciente;

use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Horario;
use App\Models\Servicio;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Inicio del paciente: resumen de citas (total, atendidas, canceladas) y "Tu próxima cita". Datos ficticios */
class InicioPacienteTest extends TestCase
{
    use RefreshDatabase;

    private Servicio $servicio;
    private Horario $horario;
    private int $contador = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Carbon::setTestNow('2026-11-09 18:00:00');                     // 12:00 en el consultorio

        $especialidad = Especialidad::create(['nombre' => 'Odontología General']);
        $this->servicio = Servicio::create(['especialidad_id' => $especialidad->id, 'nombre' => 'Limpieza dental ficticia', 'duracion_minutos' => 30, 'activo' => true]);
        $this->horario = Horario::create(['dia_semana' => 'lunes', 'hora_inicio' => '09:00', 'hora_fin' => '14:00', 'disponible' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // 'role' no está en $fillable: se asigna explícitamente
    private function paciente(array $datos = []): User
    {
        $user = User::factory()->make(array_merge(['apellidos' => 'Ficticio', 'activo' => true], $datos));
        $user->role = 'paciente';
        $user->save();

        return $user;
    }

    private function cita(User $paciente, string $status, string $fecha, string $hora = '10:00', bool $confirmada = false): Cita
    {
        return Cita::create([
            'paciente_id' => $paciente->id, 'servicio_id' => $this->servicio->id, 'horario_id' => $this->horario->id,
            'fecha' => $fecha, 'hora' => $hora, 'status' => $status, 'confirmado_por_paciente' => $confirmada,
        ]);
    }

    /** Valor del contador de una tarjeta del resumen: <span class="...">N</span><span ...>Etiqueta</span> */
    private function contador(string $html, string $etiqueta): int
    {
        $this->assertMatchesRegularExpression('#<span class="font-bold text-gray-800 text-2xl">(\d+)</span>\s*<span class="text-gray-600 text-xs font-medium">' . $etiqueta . '</span>#', $html);
        preg_match('#<span class="font-bold text-gray-800 text-2xl">(\d+)</span>\s*<span class="text-gray-600 text-xs font-medium">' . $etiqueta . '</span>#', $html, $m);

        return (int) $m[1];
    }

    private function inicio(User $paciente): string
    {
        return $this->actingAs($paciente)->get(route('paciente.dashboard'))->assertOk()->getContent();
    }

    // ---------- Resumen ----------

    public function test_el_resumen_cuenta_total_atendidas_y_canceladas(): void
    {
        $paciente = $this->paciente();
        foreach (['2026-10-01', '2026-10-02', '2026-10-03'] as $f) { $this->cita($paciente, 'atendida', $f); }
        foreach (['2026-10-04', '2026-10-05'] as $f) { $this->cita($paciente, 'cancelada', $f); }
        $this->cita($paciente, 'reagendada', '2026-10-06');
        $this->cita($paciente, 'pendiente', '2026-11-20');
        $this->cita($paciente, 'confirmada', '2026-11-21');

        $html = $this->inicio($paciente);

        $this->assertSame(8, $this->contador($html, 'Total'));        // todas, incluidas canceladas y reagendadas
        $this->assertSame(3, $this->contador($html, 'Atendidas'));
        $this->assertSame(2, $this->contador($html, 'Canceladas'));
        $this->assertStringNotContainsString('Pendientes', $html);    // reemplazados por el nuevo resumen
        $this->assertStringNotContainsString('Confirmadas', $html);
    }

    public function test_sin_citas_el_resumen_es_cero(): void
    {
        $html = $this->inicio($this->paciente());

        $this->assertSame([0, 0, 0], [$this->contador($html, 'Total'), $this->contador($html, 'Atendidas'), $this->contador($html, 'Canceladas')]);
    }

    public function test_el_resumen_no_cuenta_las_citas_de_otros_pacientes(): void
    {
        $yo = $this->paciente(['email' => 'yo@ejemplo.test']);
        $otro = $this->paciente(['email' => 'otro@ejemplo.test', 'name' => 'Otro Paciente Ficticio']);
        $this->cita($yo, 'atendida', '2026-10-01');
        foreach (['2026-10-02', '2026-10-03', '2026-10-04'] as $f) { $this->cita($otro, 'atendida', $f); }
        $this->cita($otro, 'cancelada', '2026-10-05');
        $this->cita($otro, 'pendiente', '2026-11-25', '11:00');

        $html = $this->inicio($yo);

        $this->assertSame([1, 1, 0], [$this->contador($html, 'Total'), $this->contador($html, 'Atendidas'), $this->contador($html, 'Canceladas')]);
        $this->assertStringNotContainsString('Otro Paciente Ficticio', $html);
        $this->assertStringContainsString('Aún no tienes una próxima cita', $html);   // la pendiente del otro no es mía
    }

    public function test_el_resumen_se_calcula_con_una_sola_consulta_agrupada_sin_cargar_citas(): void
    {
        $paciente = $this->paciente();
        foreach (range(1, 12) as $i) { $this->cita($paciente, $i % 2 ? 'atendida' : 'cancelada', "2026-09-" . str_pad((string) $i, 2, '0', STR_PAD_LEFT)); }

        $consultas = [];
        DB::listen(function ($c) use (&$consultas) { $consultas[] = strtolower($c->sql); });
        $this->inicio($paciente);

        $deCitas = array_values(array_filter($consultas, fn ($sql) => str_contains($sql, 'from "citas"')));
        $conteos = array_filter($deCitas, fn ($sql) => str_contains($sql, 'count(*)'));
        $this->assertCount(1, $conteos, 'El resumen debe ser una sola consulta');
        $this->assertStringContainsString('group by', array_values($conteos)[0]);
        // Y ninguna consulta trae las 12 citas a memoria: solo la de la próxima cita (limit 1)
        $sinConteo = array_values(array_filter($deCitas, fn ($sql) => !str_contains($sql, 'count(*)')));
        foreach ($sinConteo as $sql) {
            $this->assertStringContainsString('limit 1', $sql);
        }
    }

    // ---------- Próxima cita ----------

    public function test_sin_proxima_cita_muestra_un_mensaje_amable_y_el_boton_para_agendar(): void
    {
        $paciente = $this->paciente();
        $this->cita($paciente, 'atendida', '2026-10-01');
        $this->cita($paciente, 'cancelada', '2026-11-20');            // una cancelada futura no cuenta

        $this->actingAs($paciente)->get(route('paciente.dashboard'))->assertOk()
            ->assertSee('Aún no tienes una próxima cita')
            ->assertSee(route('paciente.citas.create'), false)
            ->assertSee('Agendar cita')
            ->assertDontSee('Tu próxima cita');
    }

    public function test_la_proxima_cita_muestra_fecha_hora_servicio_y_estado(): void
    {
        $paciente = $this->paciente();
        $this->cita($paciente, 'pendiente', '2026-11-16', '10:30');

        $this->actingAs($paciente)->get(route('paciente.dashboard'))->assertOk()
            ->assertSee('Tu próxima cita')->assertSee('Limpieza dental ficticia')->assertSee('Odontología General')
            ->assertSee('lunes 16 de noviembre, 2026')->assertSee('10:30 AM')
            ->assertSee('Pendiente: la clínica aún no la acepta')
            ->assertDontSee('Confirmar mi asistencia')                // no se puede confirmar antes de que la clínica acepte
            ->assertSee(route('paciente.citas.index'), false);
    }

    public function test_aceptada_por_la_clinica_y_sin_confirmar_ofrece_el_boton_de_confirmar(): void
    {
        $paciente = $this->paciente();
        $cita = $this->cita($paciente, 'confirmada', '2026-11-16', '10:30', confirmada: false);

        $this->actingAs($paciente)->get(route('paciente.dashboard'))->assertOk()
            ->assertSee('Aceptada por la clínica')->assertSee('Confirmar mi asistencia')
            ->assertSee('action="' . route('paciente.citas.confirmar', $cita->id) . '"', false);

        // El botón funciona con la ruta que ya existía
        $this->patch(route('paciente.citas.confirmar', $cita->id))->assertSessionHas('success');
        $this->assertTrue($cita->fresh()->confirmado_por_paciente);

        $this->get(route('paciente.dashboard'))->assertSee('Confirmada ✓')->assertSee('Ya confirmaste tu asistencia')->assertDontSee('Confirmar mi asistencia');
    }

    public function test_la_proxima_cita_es_la_mas_cercana_y_ignora_pasadas_canceladas_y_atendidas(): void
    {
        $paciente = $this->paciente();
        $this->cita($paciente, 'atendida', '2026-11-02');
        $this->cita($paciente, 'pendiente', '2026-11-02', '12:00');   // pasada
        $this->cita($paciente, 'cancelada', '2026-11-10');
        $lejana = $this->cita($paciente, 'confirmada', '2026-12-07', '09:00');
        $cercana = $this->cita($paciente, 'pendiente', '2026-11-23', '16:00');

        $html = $this->inicio($paciente);

        $this->assertStringContainsString('lunes 23 de noviembre, 2026', $html);
        $this->assertStringContainsString('04:00 PM', $html);
        $this->assertStringNotContainsString('07 de diciembre', $html);
        $this->assertNotNull($lejana);
        $this->assertNotNull($cercana);
    }

    public function test_una_cita_de_hoy_cuenta_como_proxima_con_la_zona_horaria_del_consultorio(): void
    {
        Carbon::setTestNow('2026-11-10 02:00:00');                    // en UTC ya es el 10, en México todavía el 9
        $paciente = $this->paciente();
        $this->cita($paciente, 'pendiente', '2026-11-09', '17:00');

        $this->actingAs($paciente)->get(route('paciente.dashboard'))->assertOk()->assertSee('Tu próxima cita')->assertSee('05:00 PM');
    }

    public function test_los_datos_de_otros_pacientes_nunca_aparecen_en_la_proxima_cita(): void
    {
        $yo = $this->paciente(['email' => 'yo@ejemplo.test']);
        $otro = $this->paciente(['email' => 'otro@ejemplo.test', 'name' => 'Otro Paciente Ficticio']);
        $this->cita($otro, 'confirmada', '2026-11-16', '09:00');
        $this->cita($yo, 'pendiente', '2026-11-30', '13:00');

        $html = $this->inicio($yo);

        $this->assertStringContainsString('lunes 30 de noviembre, 2026', $html);
        $this->assertStringNotContainsString('16 de noviembre', $html);
        $this->assertStringNotContainsString('Otro Paciente Ficticio', $html);
    }

    public function test_la_pantalla_escapa_el_nombre_y_no_trae_datos_del_expediente(): void
    {
        $paciente = $this->paciente(['name' => '<script>alert(1)</script>']);
        $this->cita($paciente, 'pendiente', '2026-11-16');

        $this->actingAs($paciente)->get(route('paciente.dashboard'))->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    }
}
