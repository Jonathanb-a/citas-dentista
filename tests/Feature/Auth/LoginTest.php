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

    private function paciente(array $datos = []): User
    {
        $user = User::factory()->make(array_merge([
            'email' => 'lucia@correo.com',
            'telefono' => '7531112233',
            'apellidos' => 'Hernández',
            'activo' => true,
            'password' => Hash::make('secreta123'),
        ], $datos));
        $user->role = 'paciente';
        $user->save();

        return $user;
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
