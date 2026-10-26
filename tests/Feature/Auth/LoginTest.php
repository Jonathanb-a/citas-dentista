<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function paciente(array $datos = [], string $rol = 'paciente'): User
    {
        $user = User::factory()->make(array_merge([
            'email' => 'lucia@correo.com',
            'telefono' => '7531112233',
            'apellidos' => 'Hernández',
            'activo' => true,
            'password' => Hash::make('secreta123'),
        ], $datos));
        $user->role = $rol;
        $user->save();

        return $user;
    }

    private function entrar(string $identificador = 'lucia@correo.com')
    {
        return $this->post('/login', ['identificador' => $identificador, 'password' => 'secreta123']);
    }

    public function test_el_boton_de_la_landing_lleva_a_agendar_y_ya_no_hay_testimonios(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="' . route('paciente.citas.create') . '"', false)
            ->assertDontSee('#testimonios', false)
            ->assertDontSee('paciente-1.png', false);
    }

    public function test_el_paciente_que_queria_agendar_vuelve_a_agendar_al_iniciar_sesion(): void
    {
        $this->paciente();

        $this->get(route('paciente.citas.create'))->assertRedirect(route('login'));

        $this->entrar()->assertRedirect(route('paciente.citas.create'));
        $this->assertAuthenticated();
    }

    public function test_sin_destino_guardado_el_paciente_va_a_su_panel(): void
    {
        $this->paciente();

        $this->entrar()->assertRedirect(route('paciente.dashboard'));
    }

    public function test_un_admin_con_destino_de_paciente_va_a_su_panel_y_no_a_un_403(): void
    {
        $this->paciente(['email' => 'doctora@clinica.mx', 'telefono' => '7530000001'], 'admin');

        $this->get(route('paciente.citas.create'))->assertRedirect(route('login'));

        $this->entrar('doctora@clinica.mx')->assertRedirect(route('admin.dashboard'));
    }

    public function test_la_asistente_vuelve_a_la_pagina_de_su_area_que_intentaba_abrir(): void
    {
        $this->paciente(['email' => 'asistente@clinica.mx', 'telefono' => '7530000002'], 'asistente');

        $this->get(route('asistente.citas.index'))->assertRedirect(route('login'));

        $this->entrar('asistente@clinica.mx')->assertRedirect(route('asistente.citas.index'));
    }

    public function test_una_cuenta_desactivada_no_entra_aunque_tenga_destino_guardado(): void
    {
        $this->paciente(['activo' => false]);

        $this->get(route('paciente.citas.create'));

        $this->entrar()->assertRedirect()->assertSessionHasErrors('identificador');
        $this->assertGuest();
    }

    public function test_formulario_conserva_campos_ruta_enlaces_y_etiquetas(): void
    {
        $respuesta = $this->get(route('login'))->assertOk();

        $respuesta->assertSee('action="/login"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('href="/forgot-password"', false)
            ->assertSee('href="/register"', false);

        foreach (['identificador', 'password'] as $campo) {
            $respuesta->assertSee('name="' . $campo . '"', false)
                ->assertSee('for="' . $campo . '"', false)
                ->assertSee('id="' . $campo . '"', false);
        }

        // Sin login con Google ni testimonios inventados
        $respuesta->assertDontSee('Google')->assertDontSee('testimonio', false);
    }

    public function test_datos_incorrectos_conservan_el_identificador_y_muestran_el_error(): void
    {
        $this->paciente();

        $this->from(route('login'))
            ->followingRedirects()
            ->post('/login', ['identificador' => 'lucia@correo.com', 'password' => 'otra-clave'])
            ->assertOk()
            ->assertSee('Los datos no coinciden con nuestros registros.')
            ->assertSee('value="lucia@correo.com"', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertDontSee('value="otra-clave"', false);

        $this->assertGuest();
    }

    public function test_campos_vacios_muestran_sus_mensajes(): void
    {
        $this->from(route('login'))
            ->followingRedirects()
            ->post('/login', ['identificador' => '', 'password' => ''])
            ->assertSee('Escribe tu correo o teléfono.')
            ->assertSee('La contraseña es obligatoria.');
    }

    public function test_entra_con_correo_o_con_telefono(): void
    {
        $this->paciente();

        $this->post('/login', ['identificador' => 'lucia@correo.com', 'password' => 'secreta123'])
            ->assertRedirect(route('paciente.dashboard'));
        $this->post('/logout');

        $this->post('/login', ['identificador' => '7531112233', 'password' => 'secreta123'])
            ->assertRedirect(route('paciente.dashboard'));
        $this->assertAuthenticated();
    }
}
