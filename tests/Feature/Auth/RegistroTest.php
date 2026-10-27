<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Notification::fake();
    }

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'name' => 'Lucía',
            'apellidos' => 'Hernández',
            'email' => 'lucia@correo.com',
            'telefono' => '7531112233',
            'sexo' => 'femenino',
            'password' => 'secreta123',
            'password_confirmation' => 'secreta123',
        ], $cambios);
    }

    public function test_formulario_muestra_selector_de_sexo(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('name="sexo"', false)
            ->assertSee('Masculino')
            ->assertSee('Femenino');
    }

    public function test_formulario_conserva_campos_ruta_y_etiquetas(): void
    {
        $respuesta = $this->get(route('register'))->assertOk();

        $respuesta->assertSee('action="/register"', false)
            ->assertSee('name="_token"', false);

        foreach (['name', 'apellidos', 'email', 'telefono', 'password', 'password_confirmation', 'terms'] as $campo) {
            $respuesta->assertSee('name="' . $campo . '"', false)
                ->assertSee('for="' . $campo . '"', false)
                ->assertSee('id="' . $campo . '"', false);
        }

        // Sin login con Google ni testimonios inventados
        $respuesta->assertDontSee('Google')->assertDontSee('testimonio', false);
    }

    public function test_tras_un_error_conserva_lo_escrito_y_muestra_el_mensaje(): void
    {
        $this->from(route('register'))
            ->followingRedirects()
            ->post('/register', $this->datos(['email' => 'no-es-correo', 'password_confirmation' => 'otra']))
            ->assertOk()
            ->assertSee('value="Lucía"', false)
            ->assertSee('value="Hernández"', false)
            ->assertSee('value="no-es-correo"', false)
            ->assertSee('Ingresa un correo válido.')
            ->assertSee('Las contraseñas no coinciden.')
            ->assertSee('aria-invalid="true"', false)
            ->assertDontSee('value="secreta123"', false);
    }

    public function test_registro_exige_sexo_valido(): void
    {
        $this->post('/register', $this->datos(['sexo' => null]))
            ->assertSessionHasErrors(['sexo' => 'Selecciona tu sexo.']);

        $this->post('/register', $this->datos(['sexo' => 'otro']))
            ->assertSessionHasErrors('sexo');

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_registro_guarda_el_sexo_del_paciente(): void
    {
        $this->post('/register', $this->datos(['sexo' => 'masculino']))
            ->assertRedirect(route('paciente.dashboard'));

        $usuario = User::where('email', 'lucia@correo.com')->first();
        $this->assertSame('masculino', $usuario->sexo);
        $this->assertSame('paciente', $usuario->role);
        $this->assertNotNull($usuario->expediente);
    }
}
