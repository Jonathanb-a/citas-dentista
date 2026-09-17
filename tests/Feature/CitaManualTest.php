<?php

namespace Tests\Feature;

use App\Models\Especialidad;
use App\Models\Horario;
use App\Models\Servicio;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CitaManualTest extends TestCase
{
    use RefreshDatabase;

    private Servicio $servicio;
    private Horario $horario;
    private string $fecha;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $especialidad = Especialidad::create(['nombre' => 'Odontología General']);
        $this->servicio = Servicio::create(['especialidad_id' => $especialidad->id, 'nombre' => 'Limpieza dental', 'duracion_minutos' => 30, 'activo' => true]);
        $this->horario = Horario::create(['dia_semana' => 'lunes', 'hora_inicio' => '09:00', 'hora_fin' => '14:00', 'disponible' => true]);
        $this->fecha = Carbon::now()->next(Carbon::MONDAY)->toDateString();
    }

    // 'role' no está en $fillable: se asigna explícitamente
    private function crearUsuario(string $role, array $datos = []): User
    {
        $user = User::factory()->make(array_merge(['apellidos' => 'Pérez', 'activo' => true], $datos));
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function datosCita(array $cambios = []): array
    {
        return array_merge([
            'tipo_paciente' => 'nuevo',
            'nuevo_nombre' => 'Pedro',
            'nuevo_apellidos' => 'Ruiz',
            'nuevo_email' => 'pedro@correo.com',
            'nuevo_telefono' => '7532223344',
            'nuevo_sexo' => 'masculino',
            'servicio_id' => $this->servicio->id,
            'horario_id' => $this->horario->id,
            'fecha' => $this->fecha,
            'hora' => '10:00',
            'status' => 'pendiente',
        ], $cambios);
    }

    public static function roles(): array
    {
        return [
            'admin' => ['admin'],
            'asistente' => ['asistente'],
        ];
    }

    #[DataProvider('roles')]
    public function test_formulario_muestra_selector_de_sexo(string $rol): void
    {
        $this->actingAs($this->crearUsuario($rol))
            ->get(route("$rol.citas.create"))
            ->assertOk()
            ->assertSee('name="nuevo_sexo"', false);
    }

    #[DataProvider('roles')]
    public function test_paciente_nuevo_exige_sexo_valido(string $rol): void
    {
        $usuario = $this->crearUsuario($rol);

        $this->actingAs($usuario)->post(route("$rol.citas.store"), $this->datosCita(['nuevo_sexo' => null]))
            ->assertSessionHasErrors(['nuevo_sexo' => 'Selecciona el sexo del paciente.']);

        $this->actingAs($usuario)->post(route("$rol.citas.store"), $this->datosCita(['nuevo_sexo' => 'otro']))
            ->assertSessionHasErrors('nuevo_sexo');

        $this->assertDatabaseMissing('users', ['email' => 'pedro@correo.com']);
        $this->assertDatabaseCount('citas', 0);
    }

    #[DataProvider('roles')]
    public function test_error_de_validacion_conserva_tipo_y_datos_del_paciente_nuevo(string $rol): void
    {
        $this->crearUsuario('paciente', ['email' => 'pedro@correo.com']);

        $respuesta = $this->actingAs($this->crearUsuario($rol))
            ->from(route("$rol.citas.create"))
            ->followingRedirects()
            ->post(route("$rol.citas.store"), $this->datosCita(['nuevo_sexo' => 'femenino']))
            ->assertOk()
            ->assertSee('Ya existe un paciente con ese correo.')
            ->assertSee("tipoPaciente: \"nuevo\"", false)
            ->assertSee('name="nuevo_nombre" value="Pedro"', false)
            ->assertSee('name="nuevo_apellidos" value="Ruiz"', false)
            ->assertSee('name="nuevo_telefono" value="7532223344"', false)
            ->assertSee('name="nuevo_email" value="pedro@correo.com"', false);

        $this->assertMatchesRegularExpression('/value="femenino"[^>]*\bchecked\b/', $respuesta->getContent());
        $this->assertDoesNotMatchRegularExpression('/value="masculino"[^>]*\bchecked\b/', $respuesta->getContent());
    }

    #[DataProvider('roles')]
    public function test_error_con_paciente_existente_conserva_la_seleccion(string $rol): void
    {
        $paciente = $this->crearUsuario('paciente');

        $this->actingAs($this->crearUsuario($rol))
            ->from(route("$rol.citas.create"))
            ->followingRedirects()
            ->post(route("$rol.citas.store"), $this->datosCita([
                'tipo_paciente' => 'existente',
                'paciente_id' => $paciente->id,
                'status' => 'invalido',
            ]))
            ->assertOk()
            ->assertSee("tipoPaciente: \"existente\"", false)
            ->assertSee('value="' . $paciente->id . '" selected', false);
    }

    #[DataProvider('roles')]
    public function test_paciente_nuevo_guarda_el_sexo(string $rol): void
    {
        $this->actingAs($this->crearUsuario($rol))
            ->post(route("$rol.citas.store"), $this->datosCita(['nuevo_sexo' => 'femenino']))
            ->assertRedirect(route("$rol.citas.index"))
            ->assertSessionHasNoErrors();

        $paciente = User::where('email', 'pedro@correo.com')->first();
        $this->assertSame('femenino', $paciente->sexo);
        $this->assertSame('paciente', $paciente->role);
        $this->assertDatabaseHas('citas', ['paciente_id' => $paciente->id]);
    }

    #[DataProvider('roles')]
    public function test_paciente_existente_no_requiere_sexo(string $rol): void
    {
        $paciente = $this->crearUsuario('paciente');

        $this->actingAs($this->crearUsuario($rol))
            ->post(route("$rol.citas.store"), $this->datosCita([
                'tipo_paciente' => 'existente',
                'paciente_id' => $paciente->id,
                'nuevo_sexo' => null,
            ]))
            ->assertRedirect(route("$rol.citas.index"))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('citas', ['paciente_id' => $paciente->id]);
    }

    #[DataProvider('roles')]
    public function test_telefono_duplicado_muestra_mensaje_en_espanol(string $rol): void
    {
        $this->crearUsuario('paciente', ['telefono' => '7532223344']);

        $this->actingAs($this->crearUsuario($rol))
            ->post(route("$rol.citas.store"), $this->datosCita())
            ->assertSessionHasErrors(['nuevo_telefono' => 'Ya existe un paciente con ese teléfono.']);
    }
}
