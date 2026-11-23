<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Horario;
use App\Models\Servicio;
use App\Models\User;
use App\Services\DisponibilidadService;
use App\Support\Telefono;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Zona horaria del consultorio (7), traslapes (6), teléfonos de 10 dígitos (8b)
 * y cambio obligatorio de la contraseña temporal (9).
 */
class CorreccionesMediaPrioridadTest extends TestCase
{
    use RefreshDatabase;

    private Servicio $servicio30;
    private Servicio $servicio60;
    private Horario $horario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $esp = Especialidad::create(['nombre' => 'General']);
        $this->servicio30 = Servicio::create(['especialidad_id' => $esp->id, 'nombre' => 'Limpieza', 'duracion_minutos' => 30, 'activo' => true]);
        $this->servicio60 = Servicio::create(['especialidad_id' => $esp->id, 'nombre' => 'Resina', 'duracion_minutos' => 60, 'activo' => true]);
        $this->horario = Horario::create(['dia_semana' => 'lunes', 'hora_inicio' => '09:00', 'hora_fin' => '14:00', 'disponible' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function usuario(string $role, array $datos = []): User
    {
        $user = User::factory()->make(array_merge(['apellidos' => 'Pérez', 'activo' => true], $datos));
        $user->role = $role;
        $user->save();

        return $user;
    }

    // ---------- 7. Zona horaria ----------

    public function test_hoy_se_calcula_con_el_reloj_del_consultorio_y_no_con_utc(): void
    {
        // 03:00 UTC del martes = 21:00 del lunes en la Ciudad de México: para la clínica todavía es lunes
        Carbon::setTestNow(Carbon::parse('2026-11-24 03:00:00', 'UTC'));
        $lunes = '2026-11-23';

        // A las 21:00 locales ya pasó todo el horario de la clínica: hoy no hay espacios
        $this->assertSame([], app(DisponibilidadService::class)->espaciosDisponibles($lunes, $this->servicio30->id));
    }

    public function test_los_horarios_de_hoy_que_aun_no_pasan_se_ofrecen(): void
    {
        // 16:00 UTC = 10:00 en la Ciudad de México. Con UTC se escondían los espacios de 10:00 a 15:00
        Carbon::setTestNow(Carbon::parse('2026-11-23 16:00:00', 'UTC'));

        $horas = collect(app(DisponibilidadService::class)->espaciosDisponibles('2026-11-23', $this->servicio30->id))->pluck('hora');

        $this->assertTrue($horas->contains('10:30'), 'las 10:30 de hoy aún no pasan y deben ofrecerse');
        $this->assertTrue($horas->contains('13:30'));
        $this->assertFalse($horas->contains('09:30'), 'las 9:30 ya pasaron y no se ofrecen');
    }

    public function test_la_fecha_de_hoy_del_consultorio_se_acepta_al_agendar_aunque_en_utc_ya_sea_manana(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-24 03:00:00', 'UTC'));   // lunes 21:00 en México
        $admin = $this->usuario('admin');

        $this->actingAs($admin)
            ->postJson(route('admin.citas.disponibilidad'), ['fecha' => '2026-11-23', 'servicio_id' => $this->servicio30->id])
            ->assertOk();
    }

    // ---------- 6. Traslapes ----------

    public function test_un_servicio_largo_que_se_traslapa_con_otro_ya_agendado_se_rechaza(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-16 12:00:00', 'UTC'));
        $fecha = '2026-11-23';
        $primero = $this->usuario('paciente');
        $segundo = $this->usuario('paciente');

        // Resina (60 min) de 10:00 a 11:00
        Cita::create(['paciente_id' => $primero->id, 'servicio_id' => $this->servicio60->id, 'horario_id' => $this->horario->id,
            'fecha' => $fecha, 'hora' => '10:00', 'status' => 'pendiente']);

        // Limpieza (30 min) a las 10:30 cae dentro de la resina: aunque no tiene el mismo inicio, se rechaza
        $this->actingAs($segundo)
            ->post(route('paciente.citas.store'), [
                'servicio_id' => $this->servicio30->id, 'horario_id' => $this->horario->id,
                'fecha' => $fecha, 'hora' => '10:30',
            ])
            ->assertSessionHasErrors('hora');

        $this->assertSame(1, Cita::count());
    }

    public function test_el_espacio_con_bloqueo_devuelve_el_espacio_libre_y_su_horario(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-16 12:00:00', 'UTC'));

        $espacio = \DB::transaction(fn () => app(DisponibilidadService::class)->espacioConBloqueo('2026-11-23', $this->servicio30->id, '10:00'));

        $this->assertSame($this->horario->id, $espacio['horario_id']);
        $this->assertNull(\DB::transaction(fn () => app(DisponibilidadService::class)->espacioConBloqueo('2026-11-23', $this->servicio30->id, '20:00')));
    }

    public function test_reagendar_una_cita_a_su_mismo_espacio_ya_no_choca_consigo_misma(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-16 12:00:00', 'UTC'));
        $fecha = '2026-11-23';
        $admin = $this->usuario('admin');
        $paciente = $this->usuario('paciente');

        // Resina de 10:00 a 11:00 que se "reagenda" al mismo espacio: antes chocaba con ella misma
        $cita = Cita::create(['paciente_id' => $paciente->id, 'servicio_id' => $this->servicio60->id, 'horario_id' => $this->horario->id,
            'fecha' => $fecha, 'hora' => '10:00', 'status' => 'pendiente']);

        $this->actingAs($admin)
            ->patch(route('admin.citas.reagendar', $cita->id), ['fecha' => $fecha, 'hora' => '10:00', 'horario_id' => $this->horario->id])
            ->assertSessionHas('success');

        $this->assertSame('confirmada', $cita->fresh()->status);
    }

    // ---------- 8b. Teléfonos ----------

    public function test_el_telefono_se_normaliza_a_diez_digitos(): void
    {
        $this->assertSame('9991234567', Telefono::normalizar('999 123 4567'));
        $this->assertSame('9991234567', Telefono::normalizar('(999) 123-4567'));
        $this->assertSame('9991234567', Telefono::normalizar('+52 999 123 4567'));
        $this->assertSame('9991234567', Telefono::normalizar('+521 999 123 4567'));
        $this->assertNull(Telefono::normalizar('12345'));
        $this->assertNull(Telefono::normalizar('abc'));
        $this->assertNull(Telefono::normalizar('99912345678'));
    }

    public function test_el_registro_guarda_el_telefono_normalizado_y_rechaza_los_que_no_tienen_diez_digitos(): void
    {
        $datos = ['name' => 'Ana', 'apellidos' => 'Ejemplo', 'email' => 'ana@ejemplo.com', 'sexo' => 'femenino',
            'password' => 'clave-segura-1', 'password_confirmation' => 'clave-segura-1'];

        $this->post('/register', $datos + ['telefono' => '12345'])->assertSessionHasErrors('telefono');

        $this->post('/register', $datos + ['telefono' => '999 123 4567'])->assertRedirect(route('paciente.dashboard'));
        $this->assertSame('9991234567', User::where('email', 'ana@ejemplo.com')->value('telefono'));
    }

    public function test_el_mismo_telefono_escrito_con_otro_formato_cuenta_como_repetido(): void
    {
        $this->usuario('paciente', ['telefono' => '9991234567']);
        $this->usuario('paciente', ['telefono' => '(753) 111-2233']);   // guardado antes con formato

        $datos = ['name' => 'Luis', 'apellidos' => 'Ejemplo', 'email' => 'luis@ejemplo.com', 'sexo' => 'masculino',
            'password' => 'clave-segura-1', 'password_confirmation' => 'clave-segura-1'];

        $this->post('/register', $datos + ['telefono' => '999 123 4567'])->assertSessionHasErrors('telefono');
        $this->post('/register', $datos + ['telefono' => '7531112233'])->assertSessionHasErrors('telefono');
        $this->assertSame(2, User::count());
    }

    public function test_se_puede_iniciar_sesion_con_el_telefono_escrito_con_formato(): void
    {
        $this->usuario('paciente', ['telefono' => '9991234567', 'password' => bcrypt('clave-segura-1')]);

        $this->post('/login', ['identificador' => '999 123 4567', 'password' => 'clave-segura-1'])
            ->assertRedirect(route('paciente.dashboard'));
    }

    // ---------- 9. Contraseña temporal ----------

    public function test_la_contrasena_restablecida_obliga_a_cambiarla_antes_de_usar_el_sistema(): void
    {
        $admin = $this->usuario('admin');
        $paciente = $this->usuario('paciente');

        $this->actingAs($admin)->patch(route('admin.pacientes.restablecer-password', $paciente->id))->assertRedirect();
        $this->assertTrue($paciente->fresh()->debe_cambiar_password);

        // Mientras no la cambie, cualquier pantalla lo manda a cambiarla
        $this->actingAs($paciente->fresh())->get(route('paciente.dashboard'))->assertRedirect(route('password.cambiar'));
        $this->actingAs($paciente->fresh())->get(route('password.cambiar'))->assertOk()->assertSee('Cambia tu contraseña');
    }

    public function test_cambiar_la_contrasena_temporal_libera_la_cuenta(): void
    {
        $paciente = $this->usuario('paciente', ['password' => bcrypt('Temporal123')]);
        $paciente->debe_cambiar_password = true;
        $paciente->save();

        $this->actingAs($paciente)
            ->put(route('password.cambiar.actualizar'), ['password_actual' => 'Temporal123', 'password' => 'MiClaveNueva1', 'password_confirmation' => 'MiClaveNueva1'])
            ->assertRedirect(route('paciente.dashboard'));

        $paciente->refresh();
        $this->assertFalse($paciente->debe_cambiar_password);
        $this->assertTrue(\Hash::check('MiClaveNueva1', $paciente->password));
        $this->get(route('paciente.dashboard'))->assertOk();
    }

    public function test_la_contrasena_nueva_debe_ser_distinta_y_la_actual_correcta(): void
    {
        $paciente = $this->usuario('paciente', ['password' => bcrypt('Temporal123')]);
        $paciente->debe_cambiar_password = true;
        $paciente->save();

        $this->actingAs($paciente)
            ->put(route('password.cambiar.actualizar'), ['password_actual' => 'Temporal123', 'password' => 'Temporal123', 'password_confirmation' => 'Temporal123'])
            ->assertSessionHasErrors('password');
        $this->actingAs($paciente)
            ->put(route('password.cambiar.actualizar'), ['password_actual' => 'otra', 'password' => 'MiClaveNueva1', 'password_confirmation' => 'MiClaveNueva1'])
            ->assertSessionHasErrors('password_actual');

        $this->assertTrue($paciente->fresh()->debe_cambiar_password);
    }

    public function test_el_personal_nuevo_y_la_doctora_tambien_deben_cambiarla(): void
    {
        $admin = $this->usuario('admin');

        $this->actingAs($admin)->post(route('admin.personal.store'), [
            'name' => 'Rosa', 'apellidos' => 'Díaz', 'email' => 'rosa@clinica.com', 'role' => 'asistente',
        ])->assertRedirect();

        $this->assertTrue(User::where('email', 'rosa@clinica.com')->first()->debe_cambiar_password);

        $asistente = User::where('email', 'rosa@clinica.com')->first();
        $this->actingAs($asistente)->get(route('asistente.dashboard'))->assertRedirect(route('password.cambiar'));
    }

    public function test_quien_no_tiene_contrasena_temporal_no_ve_la_pantalla_de_cambio(): void
    {
        $paciente = $this->usuario('paciente');

        $this->actingAs($paciente)->get(route('password.cambiar'))->assertRedirect(route('paciente.dashboard'));
        $this->get('/cambiar-password')->assertRedirect(route('paciente.dashboard'));
    }

    public function test_sin_sesion_la_pantalla_de_cambio_manda_al_login(): void
    {
        $this->get(route('password.cambiar'))->assertRedirect('/login');
    }
}
