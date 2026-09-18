<?php

namespace Tests\Feature\Admin;

use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Expediente;
use App\Models\Horario;
use App\Models\NotaEvolucion;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpresionExpedienteTest extends TestCase
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
        $user = User::factory()->make(array_merge(['apellidos' => 'Pérez', 'activo' => true], $datos));
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function pacienteConExpediente(User $admin): User
    {
        $paciente = $this->crearUsuario('paciente', ['name' => 'María', 'apellidos' => 'García', 'sexo' => 'femenino']);

        $expediente = Expediente::create([
            'paciente_id' => $paciente->id,
            'direccion' => 'Av. Principal 10, Lázaro Cárdenas',
            'alergias' => 'Penicilina',
            'diagnostico' => 'Caries en 36',
            'plan_tratamiento' => 'Resina en 36',
            'fuma' => false,
            'bruxismo' => true,
        ]);
        $expediente->odontograma()->create([
            'numero_diente' => 36,
            'estado' => 'caries',
            'nota' => 'Cara oclusal',
            'actualizado_por' => $admin->id,
        ]);

        $especialidad = Especialidad::create(['nombre' => 'Odontología General']);
        $servicio = Servicio::create(['especialidad_id' => $especialidad->id, 'nombre' => 'Limpieza dental', 'duracion_minutos' => 30]);
        $horario = Horario::create(['dia_semana' => 'lunes', 'hora_inicio' => '09:00', 'hora_fin' => '14:00']);
        $cita = Cita::create([
            'paciente_id' => $paciente->id,
            'servicio_id' => $servicio->id,
            'horario_id' => $horario->id,
            'fecha' => '2026-09-07',
            'hora' => '10:00',
            'status' => 'atendida',
            'motivo' => 'Revisión anual',
        ]);

        NotaEvolucion::guardarDeCita($cita, [
            'procedimiento' => 'Limpieza y aplicación de flúor.',
            'indicaciones' => 'Enjuague con clorhexidina.',
        ], $admin->id);

        return $paciente;
    }

    public function test_la_impresion_incluye_todas_las_secciones(): void
    {
        $admin = $this->crearUsuario('admin', ['name' => 'Doctora', 'apellidos' => 'Ramírez']);
        $paciente = $this->pacienteConExpediente($admin);

        $respuesta = $this->actingAs($admin)->get(route('admin.pacientes.imprimir', $paciente->id))->assertOk();

        $respuesta->assertSee('Melissa López N.')
            ->assertSee('Lázaro Cárdenas, Michoacán')
            ->assertSee('Impreso el')
            ->assertSee('Ficha de identificación')
            ->assertSee('María García')
            ->assertSee('Antecedentes')
            ->assertSee('Penicilina')
            ->assertSee('Hábitos')
            ->assertSee('Odontograma')
            ->assertSee('Historial de citas')
            ->assertSee('Revisión anual')
            ->assertSee('Notas de evolución')
            ->assertSee('Limpieza y aplicación de flúor.')
            ->assertSee('Caries en 36');
    }

    public function test_el_odontograma_impreso_se_entiende_sin_color(): void
    {
        $admin = $this->crearUsuario('admin');
        $paciente = $this->pacienteConExpediente($admin);

        $respuesta = $this->actingAs($admin)->get(route('admin.pacientes.imprimir', $paciente->id))->assertOk();

        // Leyenda con letra además del color, y tramas para monocromático
        $respuesta->assertSee('C = Caries')
            ->assertSee('A = Ausente / extraído')
            ->assertSee('T = En tratamiento')
            ->assertSee('trama-diagonal', false)
            ->assertSee('print-color-adjust: exact', false);

        // La nota del diente también se imprime
        $respuesta->assertSee('Cara oclusal');
    }

    public function test_la_impresion_no_lleva_menus_ni_botones(): void
    {
        $admin = $this->crearUsuario('admin');
        $paciente = $this->pacienteConExpediente($admin);

        $html = $this->actingAs($admin)->get(route('admin.pacientes.imprimir', $paciente->id))->getContent();

        $this->assertStringNotContainsString('Cerrar sesión', $html);
        $this->assertStringNotContainsString('sidebar', $html);
        $this->assertStringNotContainsString('<button', $html);
        $this->assertStringContainsString('window.print()', $html);
        $this->assertStringContainsString('size: letter', $html);

        // Espacio de firma al final
        $this->assertStringContainsString('firma-linea', $html);
    }

    public function test_la_impresion_funciona_sin_expediente_y_es_solo_para_la_doctora(): void
    {
        $admin = $this->crearUsuario('admin');
        $asistente = $this->crearUsuario('asistente');
        $sinExpediente = $this->crearUsuario('paciente');

        $this->actingAs($admin)->get(route('admin.pacientes.imprimir', $sinExpediente->id))
            ->assertOk()
            ->assertSee('Sin información')
            ->assertSee('Sin citas registradas.')
            ->assertSee('Sin notas de evolución registradas.');

        $this->actingAs($asistente)->get(route('admin.pacientes.imprimir', $sinExpediente->id))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.pacientes.imprimir', $asistente->id))->assertNotFound();
    }
}
