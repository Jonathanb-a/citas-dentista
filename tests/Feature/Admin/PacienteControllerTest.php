<?php

namespace Tests\Feature\Admin;

use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Expediente;
use App\Models\HistorialCita;
use App\Models\Horario;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PacienteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    // 'role' no está en $fillable: se asigna explícitamente
    private function crearUsuario(string $role, array $datos = []): User
    {
        $user = User::factory()->make(array_merge([
            'apellidos' => 'Pérez',
            'activo' => true,
        ], $datos));
        $user->role = $role;
        $user->save();

        return $user;
    }

    public function test_index_lista_solo_pacientes_y_busca(): void
    {
        $admin = $this->crearUsuario('admin', ['name' => 'Doctora']);
        $this->crearUsuario('asistente', ['name' => 'Asistente Oculta']);
        $this->crearUsuario('paciente', ['name' => 'Ana', 'apellidos' => 'López', 'telefono' => '5511112222']);
        $this->crearUsuario('paciente', ['name' => 'Bruno', 'apellidos' => 'Díaz', 'email' => 'bruno@correo.com']);

        $this->actingAs($admin)->get(route('admin.pacientes.index'))
            ->assertOk()
            ->assertSee('Ana')
            ->assertSee('Bruno')
            ->assertDontSee('Asistente Oculta');

        // Nombre completo, correo y teléfono
        $this->actingAs($admin)->get(route('admin.pacientes.index', ['buscar' => 'Ana López']))
            ->assertSee('Ana')->assertDontSee('Bruno');
        $this->actingAs($admin)->get(route('admin.pacientes.index', ['buscar' => 'bruno@correo']))
            ->assertSee('Bruno')->assertDontSee('5511112222');
        $this->actingAs($admin)->get(route('admin.pacientes.index', ['buscar' => '1111']))
            ->assertSee('Ana')->assertDontSee('Bruno');
    }

    public function test_show_muestra_expediente_e_historial(): void
    {
        $admin = $this->crearUsuario('admin');
        $paciente = $this->crearUsuario('paciente', ['name' => 'Carla']);

        Expediente::create(['paciente_id' => $paciente->id, 'alergias' => 'Penicilina']);

        $especialidad = Especialidad::create(['nombre' => 'Ortodoncia']);
        $servicio = Servicio::create(['especialidad_id' => $especialidad->id, 'nombre' => 'Limpieza dental', 'duracion_minutos' => 30]);
        $horario = Horario::create(['dia_semana' => 'lunes', 'hora_inicio' => '09:00', 'hora_fin' => '14:00']);
        $cita = Cita::create([
            'paciente_id' => $paciente->id,
            'servicio_id' => $servicio->id,
            'horario_id' => $horario->id,
            'fecha' => '2026-09-01',
            'hora' => '10:00',
            'status' => 'atendida',
            'motivo' => 'Revisión anual',
        ]);
        HistorialCita::create([
            'cita_id' => $cita->id,
            'user_id' => $admin->id,
            'accion' => 'atendida',
            'descripcion' => 'La cita fue marcada como atendida.',
        ]);

        $this->actingAs($admin)->get(route('admin.pacientes.show', $paciente->id))
            ->assertOk()
            ->assertSee('Carla')
            ->assertSee('Penicilina')
            ->assertSee('Limpieza dental')
            ->assertSee('Revisión anual')
            ->assertSee('La cita fue marcada como atendida.');
    }

    public function test_no_se_puede_ver_ni_modificar_a_un_no_paciente(): void
    {
        $admin = $this->crearUsuario('admin');
        $asistente = $this->crearUsuario('asistente');

        $this->actingAs($admin)->get(route('admin.pacientes.show', $asistente->id))->assertNotFound();
        $this->actingAs($admin)->patch(route('admin.pacientes.toggle', $admin->id))->assertNotFound();
        $this->assertTrue($admin->fresh()->activo);
    }

    public function test_update_valida_unicos_ignorando_al_propio_paciente(): void
    {
        $admin = $this->crearUsuario('admin');
        $paciente = $this->crearUsuario('paciente', ['email' => 'propio@correo.com', 'telefono' => '5500000001']);
        $this->crearUsuario('paciente', ['email' => 'otro@correo.com', 'telefono' => '5500000002']);

        // Conservar su propio correo y teléfono es válido
        $this->actingAs($admin)->put(route('admin.pacientes.update', $paciente->id), [
            'name' => 'Nuevo',
            'apellidos' => 'Nombre',
            'email' => 'propio@correo.com',
            'telefono' => '5500000001',
        ])->assertRedirect(route('admin.pacientes.show', $paciente->id))
          ->assertSessionHasNoErrors();

        $this->assertSame('Nuevo', $paciente->fresh()->name);

        // Usar datos de otro usuario no es válido
        $this->actingAs($admin)->put(route('admin.pacientes.update', $paciente->id), [
            'name' => 'Nuevo',
            'apellidos' => 'Nombre',
            'email' => 'otro@correo.com',
            'telefono' => '5500000002',
        ])->assertSessionHasErrors(['email', 'telefono']);
    }

    public function test_paciente_desactivado_no_puede_iniciar_sesion_ni_seguir_navegando(): void
    {
        $admin = $this->crearUsuario('admin');
        $paciente = $this->crearUsuario('paciente', ['email' => 'inactivo@correo.com']);

        $this->actingAs($admin)->patch(route('admin.pacientes.toggle', $paciente->id))->assertRedirect();
        $this->assertFalse($paciente->fresh()->activo);

        // Sesión abierta: se cierra en la siguiente petición
        $this->actingAs($paciente->fresh())->get(route('paciente.dashboard'))
            ->assertRedirect('/login')
            ->assertSessionHasErrors('identificador');
        $this->assertGuest();

        // Login nuevo: rechazado
        $this->post('/login', ['identificador' => 'inactivo@correo.com', 'password' => 'password'])
            ->assertSessionHasErrors('identificador');
        $this->assertGuest();

        // Reactivado: vuelve a entrar
        $this->actingAs($admin)->patch(route('admin.pacientes.toggle', $paciente->id));
        $this->post('/logout');
        $this->post('/login', ['identificador' => 'inactivo@correo.com', 'password' => 'password'])
            ->assertRedirect(route('paciente.dashboard'));
        $this->assertAuthenticatedAs($paciente->fresh());
    }

    public function test_rutas_requieren_rol_admin(): void
    {
        $paciente = $this->crearUsuario('paciente');

        $this->actingAs($paciente)->get(route('admin.pacientes.index'))->assertForbidden();
        $this->actingAs($paciente)->put(route('admin.pacientes.expediente.update', $paciente->id), [
            'diagnostico' => 'Intento no autorizado',
        ])->assertForbidden();
        $this->assertDatabaseCount('expedientes', 0);
    }

    public function test_actualizar_expediente_lo_crea_si_no_existe_y_registra_quien_lo_edito(): void
    {
        $admin = $this->crearUsuario('admin', ['name' => 'Doctora', 'apellidos' => 'Ramírez']);
        $paciente = $this->crearUsuario('paciente');

        $this->actingAs($admin)->put(route('admin.pacientes.expediente.update', $paciente->id), [
            'direccion' => 'Av. Principal 10, Lázaro Cárdenas, Michoacán',
            'tipo_sangre' => 'O+',
            'fuma' => '1',
            'frecuencia_cepillado' => '2 veces al día',
            'diagnostico' => 'Caries en 36',
        ])->assertRedirect(route('admin.pacientes.show', $paciente->id))
          ->assertSessionHasNoErrors();

        $expediente = $paciente->fresh()->expediente;
        $this->assertNotNull($expediente);
        $this->assertSame('O+', $expediente->tipo_sangre);
        $this->assertTrue($expediente->fuma);
        $this->assertSame('Caries en 36', $expediente->diagnostico);
        $this->assertSame($admin->id, $expediente->actualizado_por);
        $this->assertNotNull($expediente->ultima_actualizacion);

        $this->actingAs($admin)->get(route('admin.pacientes.show', $paciente->id))
            ->assertOk()
            ->assertSee('Caries en 36')
            ->assertSee('Última actualización:')
            ->assertSee('por Doctora Ramírez');
    }

    public function test_actualizar_expediente_existente_no_lo_duplica_y_conserva_campos_previos(): void
    {
        $admin = $this->crearUsuario('admin');
        $paciente = $this->crearUsuario('paciente');
        Expediente::create([
            'paciente_id' => $paciente->id,
            'fecha_nacimiento' => '1995-05-15',
            'alergias' => 'Penicilina',
            'bruxismo' => true,
        ]);

        // Checkbox desmarcado: el formulario solo envía el hidden con "0"
        $this->actingAs($admin)->put(route('admin.pacientes.expediente.update', $paciente->id), [
            'alergias' => 'Penicilina y látex',
            'bruxismo' => '0',
            'plan_tratamiento' => 'Guarda oclusal',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('expedientes', 1);
        $expediente = $paciente->fresh()->expediente;
        $this->assertSame('Penicilina y látex', $expediente->alergias);
        $this->assertFalse($expediente->bruxismo);
        $this->assertFalse($expediente->usa_hilo_dental);
        $this->assertSame('Guarda oclusal', $expediente->plan_tratamiento);
        $this->assertSame('1995-05-15', $expediente->fecha_nacimiento->format('Y-m-d'));
        $this->assertSame($admin->id, $expediente->actualizado_por);
    }

    public function test_actualizar_expediente_valida_opciones_cerradas(): void
    {
        $admin = $this->crearUsuario('admin');
        $paciente = $this->crearUsuario('paciente');

        $this->actingAs($admin)->put(route('admin.pacientes.expediente.update', $paciente->id), [
            'tipo_sangre' => 'Z+',
            'frecuencia_cepillado' => 'Cada hora',
            'fuma' => 'tal vez',
        ])->assertSessionHasErrorsIn('expediente', ['tipo_sangre', 'frecuencia_cepillado', 'fuma']);

        $this->assertDatabaseCount('expedientes', 0);
    }

    public function test_embarazada_no_aplica_a_pacientes_masculinos(): void
    {
        $admin = $this->crearUsuario('admin');
        $paciente = $this->crearUsuario('paciente', ['sexo' => 'masculino']);

        $this->actingAs($admin)->put(route('admin.pacientes.expediente.update', $paciente->id), [
            'embarazada' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertNull($paciente->fresh()->expediente->embarazada);
    }

    public function test_no_se_puede_editar_expediente_de_un_no_paciente(): void
    {
        $admin = $this->crearUsuario('admin');
        $asistente = $this->crearUsuario('asistente');

        $this->actingAs($admin)->put(route('admin.pacientes.expediente.update', $asistente->id), [
            'diagnostico' => 'No debería guardarse',
        ])->assertNotFound();

        $this->assertDatabaseCount('expedientes', 0);
    }
}
