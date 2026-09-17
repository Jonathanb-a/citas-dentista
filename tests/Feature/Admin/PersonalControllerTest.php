<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PersonalControllerTest extends TestCase
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

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'name' => 'Sofía',
            'apellidos' => 'Mendoza',
            'email' => 'sofia@dentissa.com',
            'telefono' => '7534445566',
            'sexo' => 'femenino',
            'role' => 'asistente',
        ], $cambios);
    }

    public function test_index_lista_personal_y_nunca_pacientes(): void
    {
        $admin = $this->crearUsuario('admin', ['name' => 'Doctora']);
        $this->crearUsuario('asistente', ['name' => 'Asistente Visible']);
        $this->crearUsuario('paciente', ['name' => 'Paciente Oculto']);

        $this->actingAs($admin)->get(route('admin.personal.index'))
            ->assertOk()
            ->assertSee('Doctora')
            ->assertSee('Asistente Visible')
            ->assertDontSee('Paciente Oculto');
    }

    public function test_crear_personal_genera_contrasena_temporal_y_asigna_rol(): void
    {
        $admin = $this->crearUsuario('admin');

        $respuesta = $this->actingAs($admin)->post(route('admin.personal.store'), $this->datos())
            ->assertRedirect(route('admin.personal.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('password_temporal');

        $usuario = User::where('email', 'sofia@dentissa.com')->first();
        $this->assertSame('asistente', $usuario->role);
        $this->assertTrue($usuario->activo);

        // La contraseña mostrada es la que quedó guardada
        $panel = $respuesta->getSession()->get('password_temporal');
        $this->assertTrue(Hash::check($panel['password'], $usuario->password));

        // Se muestra en un panel que no se desvanece solo
        $this->actingAs($admin)->get(route('admin.personal.index'))
            ->assertOk()
            ->assertSee($panel['password'])
            ->assertSee('Sofía Mendoza');
    }

    public function test_crear_personal_valida_rol_y_correo_unico(): void
    {
        $admin = $this->crearUsuario('admin', ['email' => 'doctora@dentissa.com']);

        $this->actingAs($admin)->post(route('admin.personal.store'), $this->datos(['role' => 'paciente']))
            ->assertSessionHasErrorsIn('crear', 'role');

        $this->actingAs($admin)->post(route('admin.personal.store'), $this->datos(['email' => 'doctora@dentissa.com']))
            ->assertSessionHasErrorsIn('crear', 'email');

        $this->assertDatabaseMissing('users', ['name' => 'Sofía']);
    }

    public function test_editar_personal_actualiza_datos_y_rol(): void
    {
        $admin = $this->crearUsuario('admin');
        $asistente = $this->crearUsuario('asistente');

        $this->actingAs($admin)->put(route('admin.personal.update', $asistente->id), $this->datos([
            'name' => 'Sofía',
            'email' => $asistente->email,
            'role' => 'admin',
        ]))->assertRedirect(route('admin.personal.index'))
          ->assertSessionHasNoErrors();

        $asistente->refresh();
        $this->assertSame('Sofía', $asistente->name);
        $this->assertSame('admin', $asistente->role);
    }

    public function test_no_puede_desactivarse_a_si_misma(): void
    {
        $admin = $this->crearUsuario('admin');
        $this->crearUsuario('admin', ['email' => 'otra@dentissa.com']);   // así no es la última activa

        $this->actingAs($admin)->patch(route('admin.personal.toggle', $admin->id))
            ->assertSessionHas('error', 'No puedes desactivar tu propia cuenta.');

        $this->assertTrue($admin->fresh()->activo);
    }

    public function test_no_puede_cambiar_su_propio_rol(): void
    {
        $admin = $this->crearUsuario('admin');
        $this->crearUsuario('admin', ['email' => 'otra@dentissa.com']);   // no es la última admin

        $this->actingAs($admin)->put(route('admin.personal.update', $admin->id), $this->datos([
            'name' => $admin->name,
            'email' => $admin->email,
            'telefono' => $admin->telefono,
            'role' => 'asistente',
        ]))->assertSessionHas('error', 'No puedes cambiar tu propio rol.');

        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_la_ultima_doctora_activa_no_puede_desactivarse(): void
    {
        $unica = $this->crearUsuario('admin');
        $this->crearUsuario('admin', ['email' => 'inactiva@dentissa.com', 'activo' => false]);
        $this->crearUsuario('asistente');

        $this->actingAs($unica)->patch(route('admin.personal.toggle', $unica->id))
            ->assertSessionHas('error', 'Debe quedar al menos una cuenta de doctora activa.');

        $this->assertTrue($unica->fresh()->activo);

        // Con otra doctora activa, desactivar a una sí procede
        $otra = $this->crearUsuario('admin', ['email' => 'otra@dentissa.com']);
        $this->actingAs($unica)->patch(route('admin.personal.toggle', $otra->id))->assertSessionHas('success');
        $this->assertFalse($otra->fresh()->activo);
    }

    public function test_la_ultima_doctora_activa_no_puede_degradarse(): void
    {
        $unica = $this->crearUsuario('admin');
        $this->crearUsuario('admin', ['email' => 'inactiva@dentissa.com', 'activo' => false]);

        $this->actingAs($unica)->put(route('admin.personal.update', $unica->id), $this->datos([
            'name' => $unica->name,
            'email' => $unica->email,
            'telefono' => $unica->telefono,
            'role' => 'asistente',
        ]))->assertSessionHas('error', 'Debe quedar al menos una cuenta de doctora activa.');

        $this->assertSame('admin', $unica->fresh()->role);
    }

    public function test_restablecer_password_de_personal_deja_rastro(): void
    {
        $admin = $this->crearUsuario('admin', ['name' => 'Doctora', 'apellidos' => 'Ramírez']);
        $asistente = $this->crearUsuario('asistente');
        $passwordAnterior = $asistente->password;

        $respuesta = $this->actingAs($admin)->patch(route('admin.personal.restablecer-password', $asistente->id))
            ->assertSessionHas('password_temporal');

        $asistente->refresh();
        $this->assertNotSame($passwordAnterior, $asistente->password);
        $this->assertTrue(Hash::check($respuesta->getSession()->get('password_temporal')['password'], $asistente->password));
        $this->assertSame($admin->id, $asistente->password_restablecida_por);
        $this->assertNotNull($asistente->password_restablecida_en);
    }

    public function test_personal_no_incluye_pacientes_en_ninguna_accion(): void
    {
        $admin = $this->crearUsuario('admin');
        $paciente = $this->crearUsuario('paciente');

        $this->actingAs($admin)->put(route('admin.personal.update', $paciente->id), $this->datos())->assertNotFound();
        $this->actingAs($admin)->patch(route('admin.personal.toggle', $paciente->id))->assertNotFound();
        $this->actingAs($admin)->patch(route('admin.personal.restablecer-password', $paciente->id))->assertNotFound();
    }

    public function test_solo_la_doctora_entra_al_modulo(): void
    {
        $asistente = $this->crearUsuario('asistente');

        $this->actingAs($asistente)->get(route('admin.personal.index'))->assertForbidden();
        $this->actingAs($asistente)->post(route('admin.personal.store'), $this->datos())->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'sofia@dentissa.com']);
    }
}
