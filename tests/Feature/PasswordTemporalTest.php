<?php

namespace Tests\Feature;

use App\Models\Especialidad;
use App\Models\Horario;
use App\Models\Servicio;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PasswordTemporalTest extends TestCase
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
        return ['admin' => ['admin'], 'asistente' => ['asistente']];
    }

    #[DataProvider('roles')]
    public function test_paciente_nuevo_muestra_la_contrasena_en_un_panel_que_no_se_desvanece(string $rol): void
    {
        $usuario = $this->crearUsuario($rol);

        $respuesta = $this->actingAs($usuario)
            ->post(route("$rol.citas.store"), $this->datosCita())
            ->assertSessionHas('password_temporal');

        $panel = $respuesta->getSession()->get('password_temporal');
        $paciente = User::where('email', 'pedro@correo.com')->first();
        $this->assertTrue(Hash::check($panel['password'], $paciente->password));

        // El mensaje verde ya no la lleva dentro
        $this->assertSame('Cita creada correctamente.', $respuesta->getSession()->get('success'));

        $pagina = $this->actingAs($usuario)->get(route("$rol.citas.index"))->assertOk();
        $pagina->assertSee($panel['password']);
        $pagina->assertSee('Pedro Ruiz');

        // El panel de la contraseña no se cierra solo, a diferencia del mensaje de éxito
        $html = $pagina->getContent();
        $inicio = strpos($html, 'Contraseña temporal de');
        $this->assertNotFalse($inicio);

        // El mensaje verde empieza justo después del panel: ahí termina el recorte
        $fin = strpos($html, 'x-data="{ show: true }"', $inicio);
        $panelHtml = substr($html, $inicio, $fin === false ? 2500 : $fin - $inicio);

        $this->assertStringNotContainsString('setTimeout', $panelHtml);
        $this->assertStringContainsString('@click="visible = false"', $panelHtml);
    }

    #[DataProvider('roles')]
    public function test_paciente_existente_no_muestra_panel_de_contrasena(string $rol): void
    {
        $paciente = $this->crearUsuario('paciente');

        $this->actingAs($this->crearUsuario($rol))
            ->post(route("$rol.citas.store"), $this->datosCita([
                'tipo_paciente' => 'existente',
                'paciente_id' => $paciente->id,
            ]))
            ->assertSessionMissing('password_temporal');
    }

    public function test_la_doctora_restablece_la_contrasena_de_un_paciente(): void
    {
        $admin = $this->crearUsuario('admin', ['name' => 'Doctora', 'apellidos' => 'Ramírez']);
        $paciente = $this->crearUsuario('paciente', ['email' => 'paciente@correo.com']);

        $respuesta = $this->actingAs($admin)
            ->from(route('admin.pacientes.show', $paciente->id))
            ->patch(route('admin.pacientes.restablecer-password', $paciente->id))
            ->assertRedirect(route('admin.pacientes.show', $paciente->id))
            ->assertSessionHas('password_temporal');

        $nueva = $respuesta->getSession()->get('password_temporal')['password'];

        // Entra con la nueva, ya no con la anterior
        $this->post('/logout');
        $this->post('/login', ['identificador' => 'paciente@correo.com', 'password' => 'password'])
            ->assertSessionHasErrors('identificador');
        $this->post('/login', ['identificador' => 'paciente@correo.com', 'password' => $nueva])
            ->assertRedirect(route('paciente.dashboard'));

        // Y queda el rastro en el detalle del paciente
        $paciente->refresh();
        $this->assertSame($admin->id, $paciente->password_restablecida_por);
        $this->assertNotNull($paciente->password_restablecida_en);

        $this->actingAs($admin)->get(route('admin.pacientes.show', $paciente->id))
            ->assertSee('Contraseña restablecida el')
            ->assertSee('por Doctora Ramírez');
    }

    public function test_la_asistente_puede_restablecer_solo_contrasenas_de_pacientes(): void
    {
        $asistente = $this->crearUsuario('asistente');
        $paciente = $this->crearUsuario('paciente');
        $admin = $this->crearUsuario('admin');

        \App\Models\Cita::create([
            'paciente_id' => $paciente->id,
            'servicio_id' => $this->servicio->id,
            'horario_id' => $this->horario->id,
            'fecha' => $this->fecha,
            'hora' => '10:00',
            'status' => 'pendiente',
        ]);

        // El botón está en su lista de citas, junto al paciente
        $this->actingAs($asistente)->get(route('asistente.citas.index'))
            ->assertOk()
            ->assertSee(route('asistente.pacientes.restablecer-password', $paciente->id), false);

        $this->actingAs($asistente)
            ->patch(route('asistente.pacientes.restablecer-password', $paciente->id))
            ->assertSessionHas('password_temporal');

        $this->assertSame($asistente->id, $paciente->fresh()->password_restablecida_por);

        // No puede tocar cuentas de personal ni entrar a los módulos del admin
        $this->actingAs($asistente)->patch(route('asistente.pacientes.restablecer-password', $admin->id))->assertNotFound();
        $this->actingAs($asistente)->get(route('admin.personal.index'))->assertForbidden();
        $this->actingAs($asistente)->get(route('admin.pacientes.index'))->assertForbidden();
    }

    public function test_restablecer_cierra_las_sesiones_abiertas_del_usuario(): void
    {
        // En pruebas la sesión usa el driver 'array'; aquí se prueba con el de producción
        config(['session.driver' => 'database']);

        $admin = $this->crearUsuario('admin');
        $paciente = $this->crearUsuario('paciente');

        \Illuminate\Support\Facades\DB::table('sessions')->insert([
            'id' => 'sesion-de-prueba',
            'user_id' => $paciente->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => 'vacio',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($admin)->patch(route('admin.pacientes.restablecer-password', $paciente->id));

        $this->assertDatabaseMissing('sessions', ['user_id' => $paciente->id]);
    }
}
