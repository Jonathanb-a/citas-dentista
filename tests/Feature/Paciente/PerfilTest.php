<?php

namespace Tests\Feature\Paciente;

use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Expediente;
use App\Models\Horario;
use App\Models\NotaEvolucion;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PerfilTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Notification::fake();
    }

    // 'role' no está en $fillable: se asigna explícitamente
    private function crearUsuario(string $role, array $datos = []): User
    {
        $user = User::factory()->make(array_merge(['apellidos' => 'Pérez', 'activo' => true], $datos));
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'name' => 'María',
            'apellidos' => 'García López',
            'email' => 'maria@correo.com',
            'telefono' => '7531112233',
            'sexo' => 'femenino',
            'fecha_nacimiento' => '1995-05-15',
        ], $cambios);
    }

    public function test_el_paciente_ve_su_perfil_y_el_enlace_en_el_menu(): void
    {
        $paciente = $this->crearUsuario('paciente', ['email' => 'maria@correo.com']);

        $this->actingAs($paciente)->get(route('paciente.perfil.index'))
            ->assertOk()
            ->assertSee('Mi perfil')
            ->assertSee('Mis datos')
            ->assertSee('Seguridad')
            ->assertSee('Mi expediente')
            ->assertSee('maria@correo.com');

        $this->actingAs($paciente)->get(route('paciente.dashboard'))
            ->assertSee(route('paciente.perfil.index'), false);
    }

    public function test_actualiza_sus_datos_y_valida_unicos_ignorandose(): void
    {
        $paciente = $this->crearUsuario('paciente', ['email' => 'maria@correo.com', 'telefono' => '7531112233']);
        $this->crearUsuario('paciente', ['email' => 'otro@correo.com', 'telefono' => '7534445566']);

        // Conservar los propios es válido
        $this->actingAs($paciente)->put(route('paciente.perfil.datos'), $this->datos(['name' => 'Mari']))
            ->assertRedirect(route('paciente.perfil.index'))
            ->assertSessionHasNoErrors();

        $paciente->refresh();
        $this->assertSame('Mari', $paciente->name);
        $this->assertSame('femenino', $paciente->sexo);
        $this->assertSame('1995-05-15', $paciente->fecha_nacimiento->format('Y-m-d'));

        // Los de otra persona, no
        $this->actingAs($paciente)->put(route('paciente.perfil.datos'), $this->datos([
            'email' => 'otro@correo.com',
            'telefono' => '7534445566',
        ]))->assertSessionHasErrorsIn('datos', ['email', 'telefono']);

        $this->assertSame('maria@correo.com', $paciente->fresh()->email);
    }

    public function test_cambiar_el_correo_lo_deja_sin_verificar_y_manda_la_verificacion(): void
    {
        $paciente = $this->crearUsuario('paciente', ['email' => 'maria@correo.com']);
        $paciente->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($paciente)->put(route('paciente.perfil.datos'), $this->datos(['email' => 'nuevo@correo.com']))
            ->assertSessionHasNoErrors();

        $paciente->refresh();
        $this->assertSame('nuevo@correo.com', $paciente->email);
        $this->assertNull($paciente->email_verified_at);
        Notification::assertSentTo($paciente, VerifyEmail::class);

        // El aviso amarillo del dashboard vuelve a aparecer
        $this->actingAs($paciente)->get(route('paciente.dashboard'))
            ->assertSee('Verifica tu correo electrónico');
    }

    public function test_cambiar_solo_el_telefono_no_afecta_la_verificacion(): void
    {
        $paciente = $this->crearUsuario('paciente', ['email' => 'maria@correo.com']);
        $paciente->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($paciente)->put(route('paciente.perfil.datos'), $this->datos(['telefono' => '7539998877']))
            ->assertSessionHasNoErrors();

        $paciente->refresh();
        $this->assertNotNull($paciente->email_verified_at);
        $this->assertSame('7539998877', $paciente->telefono);
        Notification::assertNothingSent();
    }

    public function test_no_puede_cambiarse_el_rol_ni_reactivarse(): void
    {
        $paciente = $this->crearUsuario('paciente', ['email' => 'maria@correo.com']);
        $paciente->update(['activo' => false]);

        // Con la cuenta desactivada ni siquiera puede navegar
        $this->actingAs($paciente)->put(route('paciente.perfil.datos'), $this->datos(['role' => 'admin', 'activo' => 1]))
            ->assertRedirect('/login');

        $paciente->update(['activo' => true]);

        $this->actingAs($paciente)->put(route('paciente.perfil.datos'), $this->datos(['role' => 'admin', 'activo' => 0]))
            ->assertSessionHasNoErrors();

        $paciente->refresh();
        $this->assertSame('paciente', $paciente->role);
        $this->assertTrue($paciente->activo);
    }

    public function test_cambia_su_contrasena_confirmando_la_actual(): void
    {
        $paciente = $this->crearUsuario('paciente', ['email' => 'maria@correo.com']);
        $paciente->password = Hash::make('actual123');
        $paciente->save();

        // Contraseña actual incorrecta: no cambia nada
        $this->actingAs($paciente)->put(route('paciente.perfil.password'), [
            'password_actual' => 'equivocada',
            'password' => 'nueva12345',
            'password_confirmation' => 'nueva12345',
        ])->assertSessionHasErrorsIn('password', ['password_actual' => 'La contraseña actual no es correcta.']);

        $this->assertTrue(Hash::check('actual123', $paciente->fresh()->password));

        // Confirmación que no coincide
        $this->actingAs($paciente)->put(route('paciente.perfil.password'), [
            'password_actual' => 'actual123',
            'password' => 'nueva12345',
            'password_confirmation' => 'otra12345',
        ])->assertSessionHasErrorsIn('password', 'password');

        // Correcta
        $this->actingAs($paciente)->put(route('paciente.perfil.password'), [
            'password_actual' => 'actual123',
            'password' => 'nueva12345',
            'password_confirmation' => 'nueva12345',
        ])->assertRedirect(route('paciente.perfil.index'))
          ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('nueva12345', $paciente->fresh()->password));

        // Y puede entrar con la nueva
        $this->post('/logout');
        $this->post('/login', ['identificador' => 'maria@correo.com', 'password' => 'nueva12345'])
            ->assertRedirect(route('paciente.dashboard'));
    }

    public function test_al_cambiar_la_contrasena_se_cierran_las_otras_sesiones_pero_no_la_actual(): void
    {
        config(['session.driver' => 'database']);

        $paciente = $this->crearUsuario('paciente');
        $paciente->password = Hash::make('actual123');
        $paciente->save();

        DB::table('sessions')->insert([
            'id' => 'otro-dispositivo',
            'user_id' => $paciente->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => 'vacio',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($paciente)->put(route('paciente.perfil.password'), [
            'password_actual' => 'actual123',
            'password' => 'nueva12345',
            'password_confirmation' => 'nueva12345',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('sessions', ['id' => 'otro-dispositivo']);

        // Sigue dentro: la sesión actual no se cerró
        $this->assertAuthenticatedAs($paciente->fresh());
        $this->get(route('paciente.perfil.index'))->assertOk();
    }

    public function test_ve_su_expediente_pero_no_las_notas_internas_de_la_doctora(): void
    {
        $doctora = $this->crearUsuario('admin', ['name' => 'Doctora', 'apellidos' => 'Ramírez']);
        $paciente = $this->crearUsuario('paciente', ['sexo' => 'femenino']);

        $expediente = Expediente::create([
            'paciente_id' => $paciente->id,
            'alergias' => 'Penicilina',
            'diagnostico' => 'Caries en 36',
            'plan_tratamiento' => 'Resina en 36',
            'bruxismo' => true,
            'notas_doctora' => 'SECRETO: paciente muy ansiosa, explicar todo dos veces.',
            'observaciones' => 'PRIVADO: suele llegar tarde.',
        ]);
        $expediente->odontograma()->create([
            'numero_diente' => 36,
            'estado' => 'caries',
            'nota' => 'Cara oclusal',
            'actualizado_por' => $doctora->id,
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
        ]);
        NotaEvolucion::guardarDeCita($cita, [
            'procedimiento' => 'Limpieza y aplicación de flúor.',
            'indicaciones' => 'Enjuague con clorhexidina.',
        ], $doctora->id);

        $respuesta = $this->actingAs($paciente)->get(route('paciente.perfil.index'))->assertOk();

        // Lo que sí ve
        $respuesta->assertSee('Penicilina')
            ->assertSee('Caries en 36')
            ->assertSee('Resina en 36')
            ->assertSee('Cara oclusal')
            ->assertSee('Limpieza y aplicación de flúor.')
            ->assertSee('Enjuague con clorhexidina.')
            ->assertSee('ARCADA SUPERIOR');

        // Lo que no debe ver
        $respuesta->assertDontSee('SECRETO')
            ->assertDontSee('PRIVADO')
            ->assertDontSee('NOTAS DE LA DOCTORA');

        // Y no hay forma de editarlo desde aquí
        $respuesta->assertDontSee(route('admin.pacientes.expediente.update', $paciente->id), false)
            ->assertDontSee(route('admin.pacientes.odontograma.update', $paciente->id), false);
    }

    public function test_el_perfil_es_solo_del_paciente_autenticado(): void
    {
        $admin = $this->crearUsuario('admin');
        $paciente = $this->crearUsuario('paciente', ['email' => 'maria@correo.com']);
        $otro = $this->crearUsuario('paciente', ['email' => 'otro@correo.com', 'name' => 'Otro Paciente']);

        // La doctora no entra al panel del paciente
        $this->actingAs($admin)->get(route('paciente.perfil.index'))->assertForbidden();

        // Y cada quien ve lo suyo: no hay id en la ruta
        $this->actingAs($paciente)->get(route('paciente.perfil.index'))
            ->assertOk()
            ->assertDontSee('Otro Paciente');

        $this->assertSame('otro@correo.com', $otro->fresh()->email);
    }
}
