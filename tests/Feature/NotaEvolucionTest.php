<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Horario;
use App\Models\NotaEvolucion;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotaEvolucionTest extends TestCase
{
    use RefreshDatabase;

    private Servicio $servicio;
    private Horario $horario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $especialidad = Especialidad::create(['nombre' => 'Odontología General']);
        $this->servicio = Servicio::create(['especialidad_id' => $especialidad->id, 'nombre' => 'Limpieza dental', 'duracion_minutos' => 30, 'activo' => true]);
        $this->horario = Horario::create(['dia_semana' => 'lunes', 'hora_inicio' => '09:00', 'hora_fin' => '14:00', 'disponible' => true]);
    }

    // 'role' no está en $fillable: se asigna explícitamente
    private function crearUsuario(string $role, array $datos = []): User
    {
        $user = User::factory()->make(array_merge(['apellidos' => 'Pérez', 'activo' => true], $datos));
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function crearCita(User $paciente, string $status = 'confirmada', string $fecha = '2026-09-07'): Cita
    {
        return Cita::create([
            'paciente_id' => $paciente->id,
            'servicio_id' => $this->servicio->id,
            'horario_id' => $this->horario->id,
            'fecha' => $fecha,
            'hora' => '10:00',
            'status' => $status,
        ]);
    }

    private function nota(array $cambios = []): array
    {
        return array_merge([
            'procedimiento' => 'Limpieza y aplicación de flúor.',
            'observaciones' => 'Encías inflamadas en zona inferior.',
            'indicaciones' => 'Enjuague con clorhexidina dos veces al día.',
            'proxima_cita_sugerida' => 'En 15 días para revisión',
        ], $cambios);
    }

    public function test_atender_con_datos_guarda_la_nota_y_la_firma(): void
    {
        $admin = $this->crearUsuario('admin', ['name' => 'Doctora', 'apellidos' => 'Ramírez']);
        $paciente = $this->crearUsuario('paciente');
        $cita = $this->crearCita($paciente);

        $this->actingAs($admin)
            ->patch(route('admin.citas.atender', $cita->id), $this->nota())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Cita atendida y nota de evolución guardada.');

        $this->assertSame('atendida', $cita->fresh()->status);
        $this->assertDatabaseHas('notas_evolucion', [
            'cita_id' => $cita->id,
            'paciente_id' => $paciente->id,
            'procedimiento' => 'Limpieza y aplicación de flúor.',
            'proxima_cita_sugerida' => 'En 15 días para revisión',
            'creado_por' => $admin->id,
        ]);
    }

    public function test_atender_sin_datos_no_crea_una_nota_vacia(): void
    {
        $admin = $this->crearUsuario('admin');
        $paciente = $this->crearUsuario('paciente');
        $cita = $this->crearCita($paciente);

        $this->actingAs($admin)
            ->patch(route('admin.citas.atender', $cita->id), [])
            ->assertSessionHas('success', 'Cita marcada como atendida.');

        $this->assertSame('atendida', $cita->fresh()->status);
        $this->assertDatabaseCount('notas_evolucion', 0);
    }

    public function test_agregar_la_nota_despues_y_editarla_no_duplica_registros(): void
    {
        $admin = $this->crearUsuario('admin');
        $paciente = $this->crearUsuario('paciente');
        $cita = $this->crearCita($paciente, 'atendida');

        $this->actingAs($admin)->put(route('admin.citas.nota', $cita->id), $this->nota())
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)->put(route('admin.citas.nota', $cita->id), $this->nota([
            'procedimiento' => 'Corregido: limpieza profunda.',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('notas_evolucion', 1);
        $this->assertSame('Corregido: limpieza profunda.', $cita->fresh()->notaEvolucion->procedimiento);
    }

    public function test_vaciar_todos_los_campos_elimina_la_nota(): void
    {
        $admin = $this->crearUsuario('admin');
        $paciente = $this->crearUsuario('paciente');
        $cita = $this->crearCita($paciente, 'atendida');
        NotaEvolucion::guardarDeCita($cita, $this->nota(), $admin->id);

        $this->actingAs($admin)->put(route('admin.citas.nota', $cita->id), [
            'procedimiento' => '',
            'observaciones' => '',
            'indicaciones' => '',
            'proxima_cita_sugerida' => '',
        ])->assertSessionHas('success', 'Nota de evolución eliminada.');

        $this->assertDatabaseCount('notas_evolucion', 0);
    }

    public function test_no_se_registra_evolucion_de_una_cita_que_no_fue_atendida(): void
    {
        $admin = $this->crearUsuario('admin');
        $paciente = $this->crearUsuario('paciente');
        $cita = $this->crearCita($paciente, 'confirmada');

        $this->actingAs($admin)->put(route('admin.citas.nota', $cita->id), $this->nota())
            ->assertSessionHas('error', 'Solo se puede registrar la evolución de una cita atendida.');

        $this->assertDatabaseCount('notas_evolucion', 0);
    }

    public function test_rechaza_campos_demasiado_largos(): void
    {
        $admin = $this->crearUsuario('admin');
        $paciente = $this->crearUsuario('paciente');
        $cita = $this->crearCita($paciente, 'atendida');

        $this->actingAs($admin)->put(route('admin.citas.nota', $cita->id), $this->nota([
            'procedimiento' => str_repeat('a', 2001),
        ]))->assertSessionHasErrorsIn('nota_' . $cita->id, 'procedimiento');

        $this->assertDatabaseCount('notas_evolucion', 0);
    }

    public function test_la_lista_de_citas_distingue_con_nota_y_sin_nota(): void
    {
        $admin = $this->crearUsuario('admin');
        $paciente = $this->crearUsuario('paciente');
        $conNota = $this->crearCita($paciente, 'atendida', '2026-09-07');
        $this->crearCita($paciente, 'atendida', '2026-09-08');
        NotaEvolucion::guardarDeCita($conNota, $this->nota(), $admin->id);

        $respuesta = $this->actingAs($admin)->get(route('admin.citas.index'))->assertOk();

        $respuesta->assertSee('Con nota')
            ->assertSee('Sin nota')
            ->assertSee('Ver nota')
            ->assertSee('Agregar nota');
    }

    public function test_la_pestana_evolucion_ordena_de_la_mas_reciente_a_la_mas_antigua(): void
    {
        $admin = $this->crearUsuario('admin', ['name' => 'Doctora', 'apellidos' => 'Ramírez']);
        $paciente = $this->crearUsuario('paciente');

        $vieja = $this->crearCita($paciente, 'atendida', '2026-09-01');
        $reciente = $this->crearCita($paciente, 'atendida', '2026-09-08');

        NotaEvolucion::guardarDeCita($vieja, $this->nota(['procedimiento' => 'Consulta antigua']), $admin->id);
        $this->travel(1)->minutes();
        NotaEvolucion::guardarDeCita($reciente, $this->nota(['procedimiento' => 'Consulta reciente']), $admin->id);
        $this->travelBack();

        $html = $this->actingAs($admin)->get(route('admin.pacientes.show', $paciente->id))
            ->assertOk()
            ->assertSee('Evolución')
            ->assertSee('Consulta reciente')
            ->assertSee('Escrita por Doctora Ramírez', false)
            ->getContent();

        $this->assertLessThan(
            strpos($html, 'Consulta antigua'),
            strpos($html, 'Consulta reciente'),
            'La nota más reciente debe aparecer antes que la más antigua.'
        );
    }

    public function test_la_asistente_ve_la_nota_pero_no_puede_escribirla(): void
    {
        $admin = $this->crearUsuario('admin');
        $asistente = $this->crearUsuario('asistente');
        $paciente = $this->crearUsuario('paciente');
        $cita = $this->crearCita($paciente, 'atendida');
        NotaEvolucion::guardarDeCita($cita, $this->nota(), $admin->id);

        // Ver: sí
        $this->actingAs($asistente)->get(route('asistente.citas.index'))
            ->assertOk()
            ->assertSee('Ver nota')
            // Los datos viajan en el x-data del modal, donde los acentos van escapados
            ->assertSee('Enjuague con clorhexidina');

        // Escribir: no
        $this->actingAs($asistente)->put(route('admin.citas.nota', $cita->id), $this->nota(['procedimiento' => 'Escrito por la asistente']))
            ->assertForbidden();

        $this->assertSame('Limpieza y aplicación de flúor.', $cita->fresh()->notaEvolucion->procedimiento);
    }

    public function test_la_asistente_atiende_sin_crear_nota(): void
    {
        $asistente = $this->crearUsuario('asistente');
        $paciente = $this->crearUsuario('paciente');
        $cita = $this->crearCita($paciente);

        $this->actingAs($asistente)
            ->patch(route('asistente.citas.atender', $cita->id), $this->nota())
            ->assertSessionHas('success');

        $this->assertSame('atendida', $cita->fresh()->status);
        $this->assertDatabaseCount('notas_evolucion', 0);
    }
}
