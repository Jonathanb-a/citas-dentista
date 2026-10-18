<?php

namespace Tests\Feature\Admin;

use App\Models\Bloqueo;
use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Horario;
use App\Models\Servicio;
use App\Models\User;
use App\Services\DisponibilidadService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BloqueoTest extends TestCase
{
    use RefreshDatabase;

    private Servicio $servicio;
    private Horario $horario;
    private Carbon $lunes;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $especialidad = Especialidad::create(['nombre' => 'Odontología General']);
        $this->servicio = Servicio::create(['especialidad_id' => $especialidad->id, 'nombre' => 'Limpieza dental', 'duracion_minutos' => 30, 'activo' => true]);
        $this->horario = Horario::create(['dia_semana' => 'lunes', 'hora_inicio' => '09:00', 'hora_fin' => '14:00', 'disponible' => true]);
        $this->lunes = Carbon::now()->next(Carbon::MONDAY);
        $this->admin = $this->crearUsuario('admin');
    }

    // 'role' no está en $fillable: se asigna explícitamente
    private function crearUsuario(string $role, array $datos = []): User
    {
        $user = User::factory()->make(array_merge(['apellidos' => 'Pérez', 'activo' => true], $datos));
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function horas(?Carbon $fecha = null): array
    {
        $espacios = app(DisponibilidadService::class)->espaciosDisponibles(($fecha ?? $this->lunes)->toDateString(), $this->servicio->id);

        return array_column($espacios, 'hora');
    }

    private function bloquear(array $datos = []): Bloqueo
    {
        return Bloqueo::create(array_merge([
            'fecha_inicio' => $this->lunes->toDateString(),
            'fecha_fin' => $this->lunes->toDateString(),
        ], $datos));
    }

    private function crearCita(string $hora, string $status = 'pendiente', ?Carbon $fecha = null): Cita
    {
        return Cita::create([
            'paciente_id' => $this->crearUsuario('paciente')->id,
            'servicio_id' => $this->servicio->id,
            'horario_id' => $this->horario->id,
            'fecha' => ($fecha ?? $this->lunes)->toDateString(),
            'hora' => $hora,
            'status' => $status,
        ]);
    }

    private function datosFormulario(array $cambios = []): array
    {
        return array_merge([
            'tipo' => 'dia',
            'fecha_inicio' => $this->lunes->toDateString(),
            'motivo' => 'Junta',
        ], $cambios);
    }

    // ---------- DisponibilidadService ----------

    public function test_sin_bloqueos_hay_espacios(): void
    {
        $this->assertContains('10:00', $this->horas());
    }

    public function test_dia_completo_no_ofrece_nada(): void
    {
        $this->bloquear();

        $this->assertSame([], $this->horas());
    }

    public function test_rango_de_dias_bloquea_todo_el_periodo_y_no_mas(): void
    {
        $this->bloquear(['fecha_fin' => $this->lunes->copy()->addDays(3)->toDateString()]);

        $this->assertSame([], $this->horas());
        $this->assertContains('10:00', $this->horas($this->lunes->copy()->addWeek()));
    }

    public function test_el_rango_de_dias_incluye_el_primero_y_el_ultimo(): void
    {
        $siguiente = $this->lunes->copy()->addWeek();
        $this->bloquear(['fecha_inicio' => $this->lunes->toDateString(), 'fecha_fin' => $siguiente->toDateString()]);

        $this->assertSame([], $this->horas($this->lunes));
        $this->assertSame([], $this->horas($siguiente));
    }

    public function test_bloqueo_por_horas_quita_solo_esos_espacios(): void
    {
        $this->bloquear(['hora_inicio' => '11:00', 'hora_fin' => '12:00']);
        $horas = $this->horas();

        $this->assertNotContains('11:00', $horas);
        $this->assertNotContains('11:30', $horas);
        // Los extremos no se traslapan: 10:30 termina a las 11:00 y 12:00 empieza donde acaba el bloqueo
        $this->assertContains('10:30', $horas);
        $this->assertContains('12:00', $horas);
        $this->assertContains('09:00', $horas);
    }

    public function test_bloqueo_por_horas_quita_el_espacio_que_lo_traslapa_parcialmente(): void
    {
        $this->bloquear(['hora_inicio' => '10:45', 'hora_fin' => '11:15']);
        $horas = $this->horas();

        $this->assertNotContains('10:30', $horas);
        $this->assertNotContains('11:00', $horas);
        $this->assertContains('10:00', $horas);
        $this->assertContains('11:30', $horas);
    }

    public function test_servicio_largo_que_cruza_el_bloqueo_tampoco_se_ofrece(): void
    {
        $this->servicio->update(['duracion_minutos' => 90]);
        $this->bloquear(['hora_inicio' => '11:00', 'hora_fin' => '12:00']);
        $horas = $this->horas();

        // 10:00 + 90 min llega hasta las 11:30, dentro del bloqueo
        $this->assertNotContains('10:00', $horas);
        $this->assertContains('09:00', $horas);
    }

    public function test_bloqueo_por_horas_no_afecta_otros_dias(): void
    {
        $this->bloquear(['hora_inicio' => '11:00', 'hora_fin' => '12:00']);

        $this->assertContains('11:00', $this->horas($this->lunes->copy()->addWeek()));
    }

    public function test_eliminar_el_bloqueo_libera_los_horarios(): void
    {
        $bloqueo = $this->bloquear();
        $this->assertSame([], $this->horas());

        $this->actingAs($this->admin)->delete(route('admin.bloqueos.destroy', $bloqueo->id))
            ->assertRedirect(route('admin.bloqueos.index'));

        $this->assertContains('10:00', $this->horas());
    }

    // ---------- Citas que pasan por el servicio ----------

    public function test_cita_manual_en_horario_bloqueado_se_rechaza_sin_crear_paciente(): void
    {
        $this->bloquear(['hora_inicio' => '10:00', 'hora_fin' => '11:00']);

        $this->actingAs($this->admin)->post(route('admin.citas.store'), [
            'tipo_paciente' => 'nuevo',
            'nuevo_nombre' => 'Pedro',
            'nuevo_apellidos' => 'Ruiz',
            'nuevo_email' => 'pedro@correo.com',
            'nuevo_sexo' => 'masculino',
            'servicio_id' => $this->servicio->id,
            'horario_id' => $this->horario->id,
            'fecha' => $this->lunes->toDateString(),
            'hora' => '10:00',
            'status' => 'pendiente',
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('citas', 0);
        $this->assertDatabaseMissing('users', ['email' => 'pedro@correo.com']);
    }

    public function test_asistente_tampoco_puede_agendar_en_horario_bloqueado(): void
    {
        $this->bloquear();
        $paciente = $this->crearUsuario('paciente');

        $this->actingAs($this->crearUsuario('asistente'))->post(route('asistente.citas.store'), [
            'tipo_paciente' => 'existente',
            'paciente_id' => $paciente->id,
            'servicio_id' => $this->servicio->id,
            'horario_id' => $this->horario->id,
            'fecha' => $this->lunes->toDateString(),
            'hora' => '10:00',
            'status' => 'pendiente',
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('citas', 0);
    }

    public function test_reagendar_a_un_horario_bloqueado_se_rechaza(): void
    {
        $cita = $this->crearCita('10:00');
        $otroLunes = $this->lunes->copy()->addWeek();
        $this->bloquear(['fecha_inicio' => $otroLunes->toDateString(), 'fecha_fin' => $otroLunes->toDateString()]);

        $this->actingAs($this->admin)->patch(route('admin.citas.reagendar', $cita->id), [
            'fecha' => $otroLunes->toDateString(),
            'hora' => '10:00',
            'horario_id' => $this->horario->id,
        ])->assertSessionHas('error');

        $this->assertSame($this->lunes->toDateString(), $cita->fresh()->fecha->toDateString());
    }

    public function test_paciente_no_ve_ni_puede_reservar_horarios_bloqueados(): void
    {
        $this->bloquear();
        $paciente = $this->crearUsuario('paciente');

        $this->actingAs($paciente)->postJson(route('paciente.citas.disponibilidad'), [
            'fecha' => $this->lunes->toDateString(),
            'servicio_id' => $this->servicio->id,
        ])->assertOk()->assertExactJson([]);

        $this->actingAs($paciente)->post(route('paciente.citas.store'), [
            'servicio_id' => $this->servicio->id,
            'horario_id' => $this->horario->id,
            'fecha' => $this->lunes->toDateString(),
            'hora' => '10:00',
        ])->assertSessionHasErrors('hora');

        $this->assertDatabaseCount('citas', 0);
    }

    public function test_cita_cancelada_libera_su_horario_para_otra_cita(): void
    {
        $this->crearCita('10:00', 'cancelada');

        $this->crearCita('10:00', 'pendiente');

        $this->assertDatabaseCount('citas', 2);
    }

    public function test_dos_citas_activas_en_la_misma_hora_siguen_prohibidas(): void
    {
        $this->crearCita('10:00', 'pendiente');

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->crearCita('10:00', 'confirmada');
    }

    // ---------- Sección de administración ----------

    public function test_solo_el_admin_entra_a_bloqueos(): void
    {
        $this->get(route('admin.bloqueos.index'))->assertRedirect('/login');
        $this->actingAs($this->crearUsuario('asistente'))->get(route('admin.bloqueos.index'))->assertForbidden();
        $this->actingAs($this->crearUsuario('paciente'))->get(route('admin.bloqueos.index'))->assertForbidden();
        $this->actingAs($this->crearUsuario('asistente'))->post(route('admin.bloqueos.store'), $this->datosFormulario())->assertForbidden();

        $this->actingAs($this->admin)->get(route('admin.bloqueos.index'))->assertOk()->assertSee('Bloqueos de agenda');
        $this->assertDatabaseCount('bloqueos', 0);
    }

    public function test_guarda_un_dia_completo(): void
    {
        $this->actingAs($this->admin)->post(route('admin.bloqueos.store'), $this->datosFormulario())
            ->assertRedirect(route('admin.bloqueos.index'))
            ->assertSessionHasNoErrors();

        $bloqueo = Bloqueo::first();
        $this->assertSame($this->lunes->toDateString(), $bloqueo->fecha_inicio->toDateString());
        $this->assertSame($this->lunes->toDateString(), $bloqueo->fecha_fin->toDateString());
        $this->assertNull($bloqueo->hora_inicio);
        $this->assertSame('Junta', $bloqueo->motivo);
        $this->assertSame($this->admin->id, $bloqueo->creado_por);
    }

    public function test_guarda_un_rango_de_dias_sin_motivo(): void
    {
        $fin = $this->lunes->copy()->addDays(10)->toDateString();

        $this->actingAs($this->admin)->post(route('admin.bloqueos.store'), $this->datosFormulario([
            'tipo' => 'rango', 'fecha_fin' => $fin, 'motivo' => null,
        ]))->assertSessionHasNoErrors();

        $bloqueo = Bloqueo::first();
        $this->assertSame($fin, $bloqueo->fecha_fin->toDateString());
        $this->assertNull($bloqueo->motivo);
    }

    public function test_guarda_un_rango_de_horas_en_un_solo_dia(): void
    {
        // Aunque llegue una fecha_fin, un bloqueo por horas es de un solo día
        $this->actingAs($this->admin)->post(route('admin.bloqueos.store'), $this->datosFormulario([
            'tipo' => 'horas', 'hora_inicio' => '14:00', 'hora_fin' => '16:00',
            'fecha_fin' => $this->lunes->copy()->addDays(5)->toDateString(),
        ]))->assertSessionHasNoErrors();

        $bloqueo = Bloqueo::first();
        $this->assertSame($this->lunes->toDateString(), $bloqueo->fecha_fin->toDateString());
        $this->assertStringStartsWith('14:00', $bloqueo->hora_inicio);
        $this->assertStringStartsWith('16:00', $bloqueo->hora_fin);
    }

    public function test_un_bloqueo_de_dia_completo_ignora_horas_enviadas(): void
    {
        $this->actingAs($this->admin)->post(route('admin.bloqueos.store'), $this->datosFormulario([
            'hora_inicio' => '14:00', 'hora_fin' => '16:00',
        ]));

        $this->assertNull(Bloqueo::first()->hora_inicio);
    }

    public function test_validaciones(): void
    {
        $this->actingAs($this->admin);
        $url = route('admin.bloqueos.store');

        $this->post($url, $this->datosFormulario(['tipo' => 'otro']))->assertSessionHasErrors('tipo');
        $this->post($url, $this->datosFormulario(['fecha_inicio' => null]))->assertSessionHasErrors('fecha_inicio');
        $this->post($url, $this->datosFormulario(['fecha_inicio' => Carbon::yesterday()->toDateString()]))
            ->assertSessionHasErrors(['fecha_inicio' => 'No puedes bloquear una fecha pasada.']);
        $this->post($url, $this->datosFormulario(['tipo' => 'rango']))->assertSessionHasErrors('fecha_fin');
        $this->post($url, $this->datosFormulario(['tipo' => 'rango', 'fecha_fin' => $this->lunes->copy()->subDay()->toDateString()]))
            ->assertSessionHasErrors(['fecha_fin' => 'La fecha final no puede ser anterior a la inicial.']);
        $this->post($url, $this->datosFormulario(['tipo' => 'horas']))->assertSessionHasErrors(['hora_inicio', 'hora_fin']);
        $this->post($url, $this->datosFormulario(['tipo' => 'horas', 'hora_inicio' => '16:00', 'hora_fin' => '14:00']))
            ->assertSessionHasErrors(['hora_fin' => 'La hora de fin debe ser posterior a la hora de inicio.']);
        $this->post($url, $this->datosFormulario(['tipo' => 'horas', 'hora_inicio' => '14:00', 'hora_fin' => '14:00']))
            ->assertSessionHasErrors('hora_fin');
        $this->post($url, $this->datosFormulario(['motivo' => str_repeat('a', 256)]))->assertSessionHasErrors('motivo');

        $this->assertDatabaseCount('bloqueos', 0);
    }

    public function test_lista_vigentes_y_pasados(): void
    {
        $this->bloquear(['motivo' => 'Vacaciones de invierno']);
        $this->bloquear([
            'fecha_inicio' => Carbon::now()->subDays(10)->toDateString(),
            'fecha_fin' => Carbon::now()->subDays(8)->toDateString(),
            'motivo' => 'Congreso viejo',
        ]);

        $this->actingAs($this->admin)->get(route('admin.bloqueos.index'))
            ->assertOk()
            ->assertSee('Vacaciones de invierno')
            ->assertSee('Bloqueos pasados')
            ->assertSee('Congreso viejo');
    }

    // ---------- Citas ya agendadas en el periodo ----------

    public function test_con_citas_en_el_periodo_avisa_y_no_guarda(): void
    {
        $cita = $this->crearCita('10:00', 'confirmada');

        $this->actingAs($this->admin)->post(route('admin.bloqueos.store'), $this->datosFormulario())
            ->assertSessionHas('conflictos', fn ($c) => count($c) === 1 && $c[0]['id'] === $cita->id);

        $this->assertDatabaseCount('bloqueos', 0);
        $this->assertSame('confirmada', $cita->fresh()->status);
    }

    public function test_el_aviso_se_muestra_con_las_dos_opciones_y_conserva_lo_escrito(): void
    {
        $cita = $this->crearCita('10:00');

        $this->actingAs($this->admin)->from(route('admin.bloqueos.index'))->followingRedirects()
            ->post(route('admin.bloqueos.store'), $this->datosFormulario())
            ->assertOk()
            ->assertSee('Hay 1 cita agendada en ese periodo')
            ->assertSee('Bloquear y conservar las citas')
            ->assertSee('Bloquear y cancelar las citas')
            ->assertSee('name="citas_vistas[]" value="' . $cita->id . '"', false)
            ->assertSee('value="Junta"', false);
    }

    public function test_solo_cuentan_citas_activas_que_se_traslapan(): void
    {
        $this->crearCita('09:00', 'cancelada');
        $this->crearCita('09:30', 'atendida');
        $this->crearCita('13:00', 'pendiente');                              // fuera de las horas bloqueadas
        $this->crearCita('10:00', 'pendiente', $this->lunes->copy()->addWeek()); // otro día

        $this->actingAs($this->admin)->post(route('admin.bloqueos.store'), $this->datosFormulario([
            'tipo' => 'horas', 'hora_inicio' => '09:00', 'hora_fin' => '12:00',
        ]))->assertSessionMissing('conflictos');

        $this->assertDatabaseCount('bloqueos', 1);
    }

    public function test_cita_que_termina_justo_al_inicio_del_bloqueo_no_cuenta(): void
    {
        $this->crearCita('10:30', 'pendiente'); // 10:30 a 11:00

        $this->actingAs($this->admin)->post(route('admin.bloqueos.store'), $this->datosFormulario([
            'tipo' => 'horas', 'hora_inicio' => '11:00', 'hora_fin' => '12:00',
        ]))->assertSessionMissing('conflictos');

        $this->assertDatabaseCount('bloqueos', 1);
    }

    public function test_conservar_guarda_el_bloqueo_y_deja_las_citas(): void
    {
        $cita = $this->crearCita('10:00', 'confirmada');

        $this->actingAs($this->admin)->post(route('admin.bloqueos.store'), $this->datosFormulario([
            'accion_citas' => 'conservar', 'citas_vistas' => [$cita->id],
        ]))->assertRedirect(route('admin.bloqueos.index'))
            ->assertSessionHas('success', fn ($m) => str_contains($m, '1 cita(s) marcadas para reagendar'));

        $this->assertDatabaseCount('bloqueos', 1);
        $this->assertSame('confirmada', $cita->fresh()->status);
        $this->assertDatabaseCount('historial_citas', 0);
    }

    public function test_cancelar_guarda_el_bloqueo_cancela_las_citas_y_deja_historial(): void
    {
        $cita = $this->crearCita('10:00', 'confirmada');
        $otra = $this->crearCita('11:00', 'pendiente');

        $this->actingAs($this->admin)->post(route('admin.bloqueos.store'), $this->datosFormulario([
            'accion_citas' => 'cancelar', 'citas_vistas' => [$cita->id, $otra->id],
        ]))->assertRedirect(route('admin.bloqueos.index'))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'Se cancelaron 2 cita(s)'));

        $this->assertDatabaseCount('bloqueos', 1);
        $this->assertSame('cancelada', $cita->fresh()->status);
        $this->assertSame('cancelada', $otra->fresh()->status);
        $this->assertDatabaseHas('historial_citas', [
            'cita_id' => $cita->id,
            'user_id' => $this->admin->id,
            'accion' => 'cancelada',
            'descripcion' => 'Cita cancelada por bloqueo de agenda (Junta).',
        ]);
    }

    public function test_cancelar_no_cancela_citas_que_la_doctora_no_vio(): void
    {
        $vista = $this->crearCita('10:00');
        $nueva = $this->crearCita('11:00'); // se agendó después de mostrarse el aviso

        $this->actingAs($this->admin)->post(route('admin.bloqueos.store'), $this->datosFormulario([
            'accion_citas' => 'cancelar', 'citas_vistas' => [$vista->id],
        ]))->assertSessionHas('conflictos', fn ($c) => count($c) === 2);

        $this->assertDatabaseCount('bloqueos', 0);
        $this->assertSame('pendiente', $vista->fresh()->status);
        $this->assertSame('pendiente', $nueva->fresh()->status);
    }

    public function test_la_lista_de_citas_marca_las_que_caen_en_un_bloqueo(): void
    {
        $this->crearCita('10:00', 'pendiente');
        $this->bloquear();

        foreach (['admin' => $this->admin, 'asistente' => $this->crearUsuario('asistente')] as $rol => $usuario) {
            $this->actingAs($usuario)->get(route("$rol.citas.index"))
                ->assertOk()
                ->assertSee('En periodo bloqueado');
        }
    }

    public function test_la_lista_no_marca_citas_fuera_de_un_bloqueo(): void
    {
        $this->crearCita('10:00', 'pendiente');
        $this->bloquear(['hora_inicio' => '12:00', 'hora_fin' => '13:00']);

        $this->actingAs($this->admin)->get(route('admin.citas.index'))
            ->assertOk()
            ->assertDontSee('En periodo bloqueado');
    }
}
