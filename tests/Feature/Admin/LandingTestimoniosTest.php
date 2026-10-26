<?php

namespace Tests\Feature\Admin;

use App\Models\LandingItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Opiniones de pacientes (#testimonios): pestaña "Opiniones" del panel y sección de la
 * landing, que solo muestra las que tienen la autorización del paciente.
 */
class LandingTestimoniosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('imagenes');
        $this->admin = $this->crearUsuario('admin');
    }

    // 'role' no está en $fillable: se asigna explícitamente
    private function crearUsuario(string $role): User
    {
        $user = User::factory()->make(['apellidos' => 'Pérez', 'activo' => true]);
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'nombre' => 'María G.',
            'texto' => 'Me atendieron con mucha paciencia y sin prisas.',
            'calificacion' => '5',
            'autorizado' => '1',
        ], $cambios);
    }

    private function agregar(array $cambios = [])
    {
        return $this->actingAs($this->admin)->post(route('admin.landing.testimonios.agregar'), $this->datos($cambios));
    }

    private function normalizar(string $html): string
    {
        return trim(preg_replace('/\s+/', ' ', $html));
    }

    private function assertLandingOriginal(): void
    {
        $original = file_get_contents(base_path('tests/Fixtures/landing-sin-anuncios.html'));

        // assertTrue y no assertSame: si fallara, PHPUnit tardaría minutos en calcular el diff de dos páginas enteras
        $this->assertTrue(
            $this->normalizar($original) === $this->normalizar($this->get('/')->assertOk()->getContent()),
            'La landing ya no es idéntica a la original (tests/Fixtures/landing-sin-anuncios.html).'
        );
    }

    // Solo el HTML de la sección
    private function seccion(): string
    {
        $html = $this->get('/')->assertOk()->getContent();
        $inicio = strpos($html, '<section id="testimonios"');
        $this->assertNotFalse($inicio, 'La landing no tiene la sección de opiniones.');

        return substr($html, $inicio, strpos($html, '</section>', $inicio) - $inicio);
    }

    // ---------- Landing ----------

    public function test_sin_opiniones_no_hay_seccion_y_la_landing_es_identica(): void
    {
        $this->get('/')->assertOk()->assertDontSee('id="testimonios"', false);
        $this->assertLandingOriginal();
    }

    public function test_una_opinion_autorizada_aparece_con_su_calificacion_y_sin_fotos(): void
    {
        $this->agregar(['calificacion' => '4'])->assertRedirect(route('admin.landing.testimonios'))->assertSessionHas('success');

        $seccion = $this->seccion();

        $this->assertStringContainsString('María G.', $seccion);
        $this->assertStringContainsString('Me atendieron con mucha paciencia y sin prisas.', $seccion);
        $this->assertStringContainsString('aria-label="Calificación: 4 de 5"', $seccion);
        $this->assertSame(4, substr_count($seccion, '<svg class="h-5 w-5 "'));
        $this->assertSame(1, substr_count($seccion, 'text-gray-300'));
        $this->assertStringNotContainsString('<img', $seccion);
    }

    public function test_las_opiniones_sin_autorizacion_no_se_publican(): void
    {
        // Solo pueden llegar así desde la base: el panel no deja guardar sin la casilla
        LandingItem::create(['coleccion' => 'testimonios', 'orden' => 1, 'datos' => [
            'nombre' => 'Sin permiso', 'texto' => 'No debe verse.', 'calificacion' => 5, 'autorizado' => false,
        ]]);

        $this->get('/')->assertOk()->assertDontSee('id="testimonios"', false)->assertDontSee('Sin permiso');
        $this->assertLandingOriginal();

        $this->agregar();

        $seccion = $this->seccion();
        $this->assertStringContainsString('María G.', $seccion);
        $this->assertStringNotContainsString('Sin permiso', $seccion);
    }

    public function test_la_seccion_va_despues_de_la_galeria_y_antes_de_certificados(): void
    {
        $this->agregar();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertLessThan(strpos($html, 'id="testimonios"'), strpos($html, 'id="galeria"'));
        $this->assertLessThan(strpos($html, 'id="certificados"'), strpos($html, 'id="testimonios"'));
    }

    public function test_el_html_en_los_textos_se_muestra_escapado(): void
    {
        $this->agregar(['nombre' => '<b>Ana</b>', 'texto' => '<script>alert(1)</script>']);

        $seccion = $this->seccion();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $seccion);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $seccion);
        $this->assertStringContainsString('&lt;b&gt;Ana&lt;/b&gt;', $seccion);
    }

    public function test_si_falla_la_lectura_la_landing_se_ve_sin_opiniones_y_sin_500(): void
    {
        $this->agregar();
        Schema::drop('landing_items');

        $this->get('/')->assertOk()->assertDontSee('id="testimonios"', false)->assertSee('id="galeria"', false);
    }

    // ---------- Panel: validación ----------

    public function test_la_autorizacion_es_obligatoria(): void
    {
        $this->agregar(['autorizado' => null])
            ->assertSessionHasErrorsIn('nueva', ['autorizado' => 'Marca la casilla "El paciente autorizó publicar esta opinión" para poder guardar.']);

        $this->assertDatabaseCount('landing_items', 0);
    }

    public function test_nombre_texto_y_calificacion_son_obligatorios(): void
    {
        $this->agregar(['nombre' => '', 'texto' => '', 'calificacion' => ''])
            ->assertSessionHasErrorsIn('nueva', ['nombre', 'texto', 'calificacion']);

        $this->assertDatabaseCount('landing_items', 0);
    }

    public function test_la_calificacion_va_de_1_a_5(): void
    {
        foreach (['0', '6', 'cinco', '3.5'] as $invalida) {
            $this->agregar(['calificacion' => $invalida])->assertSessionHasErrorsIn('nueva', 'calificacion');
        }
        $this->assertDatabaseCount('landing_items', 0);

        foreach (['1', '5'] as $valida) {
            $this->agregar(['calificacion' => $valida])->assertSessionDoesntHaveErrors();
        }
        $this->assertDatabaseCount('landing_items', 2);
    }

    public function test_los_textos_tienen_limite(): void
    {
        $this->agregar(['nombre' => str_repeat('a', 41)])->assertSessionHasErrorsIn('nueva', 'nombre');
        $this->agregar(['texto' => str_repeat('a', 401)])->assertSessionHasErrorsIn('nueva', 'texto');

        $this->assertDatabaseCount('landing_items', 0);
    }

    // ---------- Panel: guardar, editar, quitar, ordenar ----------

    public function test_se_guarda_en_landing_items_sin_imagen(): void
    {
        $this->agregar(['texto' => "Muy   buena\natención."]);

        $fila = LandingItem::where('coleccion', 'testimonios')->sole();

        $this->assertNull($fila->imagen);
        $this->assertNull($fila->original);
        $this->assertSame(['nombre' => 'María G.', 'texto' => 'Muy buena atención.', 'calificacion' => 5, 'autorizado' => true], $fila->datos);
        $this->assertSame([], Storage::disk('imagenes')->allFiles());
    }

    public function test_editar_una_opinion(): void
    {
        $this->agregar();
        $id = LandingItem::where('coleccion', 'testimonios')->value('id');

        $this->actingAs($this->admin)
            ->put(route('admin.landing.testimonios.actualizar', $id), $this->datos(['nombre' => 'Laura M.', 'calificacion' => '3']))
            ->assertRedirect(route('admin.landing.testimonios'))
            ->assertSessionHas('success');

        $seccion = $this->seccion();
        $this->assertStringContainsString('Laura M.', $seccion);
        $this->assertStringContainsString('Calificación: 3 de 5', $seccion);
    }

    public function test_no_se_puede_quitar_la_autorizacion_al_editar(): void
    {
        $this->agregar();
        $id = LandingItem::where('coleccion', 'testimonios')->value('id');

        $this->actingAs($this->admin)
            ->put(route('admin.landing.testimonios.actualizar', $id), $this->datos(['autorizado' => null, 'nombre' => 'Otro']))
            ->assertSessionHasErrorsIn('opinion_' . $id, 'autorizado');

        $this->assertSame('María G.', LandingItem::find($id)->datos['nombre']);
    }

    public function test_quitar_la_ultima_opinion_oculta_la_seccion(): void
    {
        $this->agregar();
        $id = LandingItem::where('coleccion', 'testimonios')->value('id');

        $this->actingAs($this->admin)->delete(route('admin.landing.testimonios.eliminar', $id))
            ->assertRedirect(route('admin.landing.testimonios'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('landing_items', 0);
        $this->get('/')->assertOk()->assertDontSee('id="testimonios"', false);
    }

    public function test_subir_y_bajar_cambian_el_orden_en_la_landing(): void
    {
        $this->agregar(['nombre' => 'Primera']);
        $this->agregar(['nombre' => 'Segunda']);
        $segunda = LandingItem::where('coleccion', 'testimonios')->orderByDesc('orden')->value('id');

        $this->actingAs($this->admin)->patch(route('admin.landing.testimonios.subir', $segunda))->assertRedirect(route('admin.landing.testimonios'));

        $seccion = $this->seccion();
        $this->assertLessThan(strpos($seccion, 'Primera'), strpos($seccion, 'Segunda'));
    }

    public function test_maximo_doce_opiniones(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->agregar(['nombre' => "Paciente $i"]);
        }

        $this->agregar(['nombre' => 'Sobra'])
            ->assertRedirect(route('admin.landing.testimonios'))
            ->assertSessionHas('error', 'Puede haber como máximo 12 opiniones. Quita alguna para agregar otra.');

        $this->assertDatabaseCount('landing_items', 12);
    }

    public function test_no_afecta_a_la_galeria(): void
    {
        $this->agregar();

        $this->assertSame(0, LandingItem::where('coleccion', 'galeria')->count());
        $this->assertStringContainsString('Diseño de Sonrisa', $this->get('/')->assertOk()->getContent());
    }

    // ---------- Panel: pantalla y acceso ----------

    public function test_el_panel_muestra_la_lista_y_el_formulario(): void
    {
        $this->actingAs($this->admin)->get(route('admin.landing.testimonios'))
            ->assertOk()
            ->assertSee('Landing Page · Opiniones')
            ->assertSee('Todavía no hay opiniones')
            ->assertSee('El paciente autorizó publicar esta opinión')
            ->assertSee('Puede ser solo el nombre y la inicial del apellido', false)
            ->assertSee('0 de 12');

        $this->agregar();

        $this->actingAs($this->admin)->get(route('admin.landing.testimonios'))
            ->assertOk()
            ->assertSee('María G.')
            ->assertSee('1 publicada')
            ->assertSee('1 de 12');
    }

    public function test_la_pestana_opiniones_esta_en_todas_las_pantallas_de_landing_page(): void
    {
        foreach (['admin.anuncios.index', 'admin.landing.contenido', 'admin.landing.galeria', 'admin.landing.testimonios', 'admin.landing.contacto'] as $ruta) {
            $this->actingAs($this->admin)->get(route($ruta))->assertOk()->assertSee('href="' . route('admin.landing.testimonios') . '"', false);
        }
    }

    public function test_solo_el_admin_entra(): void
    {
        $this->agregar();
        $id = LandingItem::where('coleccion', 'testimonios')->value('id');

        $rutas = [
            ['get', route('admin.landing.testimonios')],
            ['post', route('admin.landing.testimonios.agregar')],
            ['put', route('admin.landing.testimonios.actualizar', $id)],
            ['delete', route('admin.landing.testimonios.eliminar', $id)],
            ['patch', route('admin.landing.testimonios.subir', $id)],
            ['patch', route('admin.landing.testimonios.bajar', $id)],
        ];

        auth()->logout();
        foreach ($rutas as [$metodo, $url]) {
            $this->{$metodo}($url)->assertRedirect('/login');
        }

        foreach (['asistente', 'paciente'] as $rol) {
            $usuario = $this->crearUsuario($rol);
            foreach ($rutas as [$metodo, $url]) {
                $this->actingAs($usuario)->{$metodo}($url, $this->datos(['nombre' => 'Intruso']))->assertForbidden();
            }
        }

        $this->assertDatabaseCount('landing_items', 1);
        $this->assertSame('María G.', LandingItem::find($id)->datos['nombre']);
    }
}
