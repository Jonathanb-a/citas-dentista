<?php

namespace Tests\Feature\Paciente;

use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Expediente;
use App\Models\Horario;
use App\Models\NotaEvolucion;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El paciente NO ve su expediente clínico (ni en Mi perfil, ni en el dashboard, ni por ninguna ruta):
 * solo lo ven y editan la doctora (admin). La asistente tampoco tiene acceso. Nada se borra: el
 * expediente sigue existiendo. Todos los datos son ficticios.
 */
class ExpedienteSinAccesoPacienteTest extends TestCase
{
    use RefreshDatabase;

    // Textos que nunca deben llegar a una pantalla del paciente
    private const SECRETOS = [
        'Penicilina ficticia', 'Diagnóstico ficticio de caries', 'Plan ficticio de resina', 'SECRETO nota interna',
        'PRIVADO observación interna', 'Cara oclusal ficticia', 'Procedimiento ficticio realizado', 'Indicación ficticia para casa',
        'Observación ficticia de la consulta', 'Padecimiento ficticio', 'Medicamento ficticio', 'Hipertensión ficticia',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    // 'role' no está en $fillable: se asigna explícitamente
    private function usuario(string $role, array $datos = []): User
    {
        $user = User::factory()->make(array_merge(['apellidos' => 'Ficticio', 'activo' => true], $datos));
        $user->role = $role;
        $user->save();

        return $user;
    }

    /** Paciente con un expediente completo, un odontograma y una nota de evolución */
    private function pacienteConExpediente(string $nombre = 'Paciente'): User
    {
        $doctora = User::where('role', 'admin')->first() ?? $this->usuario('admin');
        $paciente = $this->usuario('paciente', ['name' => $nombre, 'sexo' => 'femenino']);

        $expediente = Expediente::create([
            'paciente_id' => $paciente->id,
            'alergias' => 'Penicilina ficticia',
            'enfermedades' => 'Hipertensión ficticia',
            'medicamentos' => 'Medicamento ficticio',
            'padecimiento_actual' => 'Padecimiento ficticio',
            'diagnostico' => 'Diagnóstico ficticio de caries',
            'plan_tratamiento' => 'Plan ficticio de resina',
            'notas_doctora' => 'SECRETO nota interna',
            'observaciones' => 'PRIVADO observación interna',
            'bruxismo' => true,
        ]);
        $expediente->odontograma()->create(['numero_diente' => 36, 'estado' => 'caries', 'nota' => 'Cara oclusal ficticia', 'actualizado_por' => $doctora->id]);

        $especialidad = Especialidad::create(['nombre' => 'Odontología General']);
        $servicio = Servicio::create(['especialidad_id' => $especialidad->id, 'nombre' => 'Limpieza dental', 'duracion_minutos' => 30]);
        $horario = Horario::firstOrCreate(['dia_semana' => 'lunes'], ['hora_inicio' => '09:00', 'hora_fin' => '14:00']);
        $cita = Cita::create([
            'paciente_id' => $paciente->id, 'servicio_id' => $servicio->id, 'horario_id' => $horario->id,
            'fecha' => now()->subDays(random_int(5, 400))->toDateString(), 'hora' => '10:00', 'status' => 'atendida',
        ]);
        NotaEvolucion::guardarDeCita($cita, [
            'procedimiento' => 'Procedimiento ficticio realizado',
            'observaciones' => 'Observación ficticia de la consulta',
            'indicaciones' => 'Indicación ficticia para casa',
        ], $doctora->id);

        return $paciente;
    }

    private function assertSinSecretos($respuesta): void
    {
        foreach (self::SECRETOS as $secreto) {
            $respuesta->assertDontSee($secreto);
        }
    }

    // ---------- Lo que ve el paciente ----------

    public function test_mi_perfil_no_muestra_nada_del_expediente_ni_la_pestana(): void
    {
        $paciente = $this->pacienteConExpediente();

        $respuesta = $this->actingAs($paciente)->get(route('paciente.perfil.index'))->assertOk();

        $this->assertSinSecretos($respuesta);
        $respuesta->assertSee('Mis datos')->assertSee('Seguridad')
            ->assertDontSee('Mi expediente')->assertDontSee('Mi odontograma')->assertDontSee('Mis consultas')
            ->assertDontSee('ARCADA SUPERIOR')->assertDontSee('ALERGIAS')
            ->assertDontSee(route('admin.pacientes.expediente.update', $paciente->id), false)
            ->assertDontSee(route('admin.pacientes.odontograma.update', $paciente->id), false);
    }

    public function test_el_dashboard_no_muestra_las_notas_de_la_doctora_ni_nada_del_expediente(): void
    {
        $paciente = $this->pacienteConExpediente();

        $respuesta = $this->actingAs($paciente)->get(route('paciente.dashboard'))->assertOk();

        $this->assertSinSecretos($respuesta);
        $respuesta->assertDontSee('Notas de tu doctora');
    }

    public function test_las_pantallas_del_paciente_no_consultan_el_expediente(): void
    {
        $paciente = $this->pacienteConExpediente();
        $consultas = [];
        \DB::listen(function ($consulta) use (&$consultas) { $consultas[] = $consulta->sql; });

        $this->actingAs($paciente)->get(route('paciente.perfil.index'))->assertOk();
        $this->get(route('paciente.dashboard'))->assertOk();

        $tablas = implode("\n", $consultas);
        foreach (['"expedientes"', '"odontograma"', '"notas_evolucion"'] as $tabla) {
            $this->assertStringNotContainsString($tabla, $tablas, $tabla);
        }
    }

    public function test_el_registro_y_el_login_ya_no_prometen_el_expediente(): void
    {
        foreach ([route('register'), route('login')] as $url) {
            $this->get($url)->assertOk()
                ->assertDontSee('Tu expediente a la mano')
                ->assertDontSee('diagnóstico y plan de tratamiento')
                ->assertSee('Tu cuenta, tus citas');
        }
    }

    public function test_las_pantallas_de_citas_del_paciente_no_traen_datos_del_expediente(): void
    {
        $paciente = $this->pacienteConExpediente();

        foreach ([route('paciente.citas.index'), route('paciente.citas.create')] as $url) {
            $this->assertSinSecretos($this->actingAs($paciente)->get($url)->assertOk());
        }
    }

    // ---------- Rutas de expediente: solo la doctora ----------

    private function rutasDeExpediente(User $paciente): array
    {
        return [
            ['get', route('admin.pacientes.show', $paciente->id), []],
            ['get', route('admin.pacientes.imprimir', $paciente->id), []],
            ['put', route('admin.pacientes.expediente.update', $paciente->id), ['diagnostico' => 'Intento de escritura']],
            ['put', route('admin.pacientes.odontograma.update', $paciente->id), ['dientes' => [16 => ['estado' => 'caries', 'nota' => 'Intento']]]],
            ['put', route('admin.pacientes.update', $paciente->id), ['name' => 'Intruso', 'apellidos' => 'Intruso']],
        ];
    }

    public function test_un_paciente_no_puede_ver_ni_editar_el_expediente_ni_el_suyo_ni_el_de_otro(): void
    {
        $yo = $this->pacienteConExpediente('Yo');
        $otro = $this->pacienteConExpediente('Otro');
        $antes = [Expediente::where('paciente_id', $yo->id)->first()->diagnostico, Expediente::where('paciente_id', $otro->id)->first()->diagnostico];

        foreach ([$yo, $otro] as $objetivo) {
            foreach ($this->rutasDeExpediente($objetivo) as [$metodo, $url, $datos]) {
                $this->actingAs($yo)->{$metodo}($url, $datos)->assertForbidden();
            }
        }

        // Nada cambió
        $this->assertSame($antes, [Expediente::where('paciente_id', $yo->id)->first()->diagnostico, Expediente::where('paciente_id', $otro->id)->first()->diagnostico]);
        $this->assertDatabaseMissing('odontograma', ['nota' => 'Intento']);
        $this->assertDatabaseMissing('users', ['name' => 'Intruso']);
    }

    public function test_la_asistente_no_tiene_acceso_al_expediente(): void
    {
        $paciente = $this->pacienteConExpediente();
        $asistente = $this->usuario('asistente');

        foreach ($this->rutasDeExpediente($paciente) as [$metodo, $url, $datos]) {
            $this->actingAs($asistente)->{$metodo}($url, $datos)->assertForbidden();
        }

        $this->assertSame('Diagnóstico ficticio de caries', Expediente::where('paciente_id', $paciente->id)->first()->diagnostico);
    }

    public function test_sin_sesion_las_rutas_de_expediente_mandan_al_login(): void
    {
        $paciente = $this->pacienteConExpediente();

        foreach ($this->rutasDeExpediente($paciente) as [$metodo, $url, $datos]) {
            $this->{$metodo}($url, $datos)->assertRedirect('/login');
        }
    }

    public function test_la_doctora_sigue_viendo_y_editando_el_expediente_y_el_odontograma(): void
    {
        $paciente = $this->pacienteConExpediente();
        $doctora = $this->usuario('admin');

        $this->actingAs($doctora)->get(route('admin.pacientes.show', $paciente->id))->assertOk()
            ->assertSee('Penicilina ficticia')->assertSee('Diagnóstico ficticio de caries')->assertSee('SECRETO nota interna')
            ->assertSee('Procedimiento ficticio realizado');
        $this->actingAs($doctora)->get(route('admin.pacientes.imprimir', $paciente->id))->assertOk();

        $this->actingAs($doctora)->put(route('admin.pacientes.expediente.update', $paciente->id), ['diagnostico' => 'Diagnóstico nuevo de la doctora'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Diagnóstico nuevo de la doctora', Expediente::where('paciente_id', $paciente->id)->first()->diagnostico);

        $this->actingAs($doctora)->put(route('admin.pacientes.odontograma.update', $paciente->id), ['dientes' => [16 => ['estado' => 'caries', 'nota' => 'Nueva']]])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('odontograma', ['numero_diente' => 16, 'nota' => 'Nueva']);
    }

    // ---------- Nada se borró ----------

    public function test_el_expediente_y_sus_datos_siguen_existiendo_para_la_clinica(): void
    {
        $paciente = $this->pacienteConExpediente();
        $this->actingAs($paciente)->get(route('paciente.perfil.index'))->assertOk();
        $this->get(route('paciente.dashboard'))->assertOk();

        $expediente = Expediente::where('paciente_id', $paciente->id)->firstOrFail();
        $this->assertSame('Penicilina ficticia', $expediente->alergias);
        $this->assertSame(1, $expediente->odontograma()->count());
        $this->assertSame(1, NotaEvolucion::where('paciente_id', $paciente->id)->count());
        $this->assertSame(1, $paciente->fresh()->notasEvolucion()->count());
    }
}
