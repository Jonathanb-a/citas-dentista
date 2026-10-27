<?php

namespace Tests\Feature\Admin;

use App\Models\LandingContenido;
use App\Models\User;
use App\Services\LandingContenidoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ImagenesDePrueba as Img;
use Tests\TestCase;

/** Imágenes y textos editables de la landing (pestañas "Imágenes y textos" y "Contacto") */
class LandingContenidoTest extends TestCase
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

    private function landing(): string
    {
        return $this->get('/')->assertOk()->getContent();
    }

    private function guardar(string $seccion, array $datos)
    {
        return $this->actingAs($this->admin)->put(route('admin.landing.guardar', $seccion), $datos);
    }

    private function archivos(): array
    {
        return Storage::disk('imagenes')->allFiles();
    }

    /** Un valor distinto y válido para cada campo de una sección (imágenes incluidas) */
    private function cambiosDeSeccion(string $seccion): array
    {
        $datos = [];

        foreach (LandingContenidoService::camposDeSeccion($seccion) as $clave => $campo) {
            $nombre = LandingContenidoService::nombreDeCampo($clave);

            $datos[$nombre] = match ($campo['tipo']) {
                'imagen' => Img::pngSubido("$nombre.png"),
                'titulo' => "Primera línea $nombre\nSegunda línea",
                'telefono' => '5512345678',
                'red' => 'https://www.' . $campo['hosts'][0] . '/prueba',
                'mapa' => 'https://www.google.com/maps/embed?pb=prueba123',
                default => "Texto nuevo $nombre",
            };
        }

        return $datos;
    }

    public static function secciones(): array
    {
        return [
            'hero' => ['hero'],
            'nosotros' => ['nosotros'],
            'certificados' => ['certificados'],
            'agendar' => ['agendar'],
            'contacto' => ['contacto'],
            'pie' => ['pie'],
        ];
    }

    // ---------- La landing no cambia ----------

    public function test_sin_cambios_la_landing_es_identica_a_la_original(): void
    {
        $this->assertLandingOriginal();
        $this->assertDatabaseCount('landing_contenidos', 0);
    }

    #[DataProvider('secciones')]
    public function test_personalizar_y_restaurar_una_seccion_deja_la_landing_idéntica(string $seccion): void
    {
        $this->guardar($seccion, $this->cambiosDeSeccion($seccion))->assertSessionHasNoErrors();
        $this->assertFalse($this->normalizar(file_get_contents(base_path('tests/Fixtures/landing-sin-anuncios.html'))) === $this->normalizar($this->landing()), 'La landing no cambió tras personalizar.');

        $this->actingAs($this->admin)->delete(route('admin.landing.restaurar-seccion', $seccion))->assertRedirect();

        $this->assertLandingOriginal();
        $this->assertDatabaseCount('landing_contenidos', 0);
        $this->assertSame([], $this->archivos(), 'No deben quedar imágenes subidas.');
    }

    public function test_si_falla_la_lectura_de_lo_personalizado_la_landing_se_ve_original(): void
    {
        Schema::drop('landing_contenidos');

        $this->assertLandingOriginal();
    }

    // ---------- Cada campo del catálogo llega a la landing ----------

    public function test_todos_los_campos_de_texto_del_catalogo_se_leen_en_la_landing(): void
    {
        foreach (LandingContenidoService::campos() as $clave => $campo) {
            if (in_array($campo['tipo'], ['imagen', 'telefono', 'titulo'], true)) {
                continue;
            }
            LandingContenido::create(['clave' => $clave, 'valor' => "MARCA-$clave"]);
        }

        $html = $this->landing();

        foreach (LandingContenidoService::campos() as $clave => $campo) {
            if (!in_array($campo['tipo'], ['imagen', 'telefono', 'titulo'], true)) {
                $this->assertStringContainsString("MARCA-$clave", $html, "La landing no usa el campo $clave.");
            }
        }
    }

    public function test_todas_las_imagenes_del_catalogo_se_leen_en_la_landing(): void
    {
        foreach (LandingContenidoService::campos() as $clave => $campo) {
            if ($campo['tipo'] === 'imagen') {
                LandingContenido::create(['clave' => $clave, 'valor' => 'landing/' . preg_replace('/[^a-z0-9]/', '', $clave) . '.png']);
            }
        }

        $html = $this->landing();

        foreach (LandingContenidoService::campos() as $clave => $campo) {
            if ($campo['tipo'] === 'imagen') {
                $this->assertStringContainsString('landing/' . preg_replace('/[^a-z0-9]/', '', $clave) . '.png', $html, "La landing no usa la imagen $clave.");
                $this->assertStringNotContainsString($campo['defecto'], $html, "Sigue mostrando la original de $clave.");
            }
        }
    }

    public function test_el_titulo_y_los_enlaces_de_telefono_se_leen_en_la_landing(): void
    {
        LandingContenido::create(['clave' => 'hero.titulo', 'valor' => "NUEVA LÍNEA UNO\nNUEVA LÍNEA DOS"]);
        LandingContenido::create(['clave' => 'contacto.telefono', 'valor' => '5512345678']);
        LandingContenido::create(['clave' => 'contacto.whatsapp', 'valor' => '5598765432']);

        $this->get('/')->assertOk()
            ->assertSee('NUEVA LÍNEA UNO <br> NUEVA LÍNEA DOS', false)
            ->assertSee('href="tel:+525512345678"', false)
            ->assertSee('href="https://wa.me/525598765432"', false)
            ->assertDontSee('tel:+529991231234', false)
            ->assertDontSee('wa.me/529991231234', false);
    }

    public function test_el_catalogo_esta_completo(): void
    {
        $tipos = ['texto', 'titulo', 'parrafo', 'imagen', 'telefono', 'red', 'mapa'];

        foreach (LandingContenidoService::campos() as $clave => $campo) {
            $this->assertMatchesRegularExpression('/^[a-z0-9_]+\.[a-z0-9_]+$/', $clave);
            $this->assertContains($campo['tipo'], $tipos, $clave);
            $this->assertNotEmpty($campo['etiqueta'], $clave);
            $this->assertArrayHasKey('defecto', $campo, $clave);
            $this->assertArrayHasKey(explode('.', $clave)[0], LandingContenidoService::secciones(), "$clave no tiene sección");

            if ($campo['tipo'] === 'imagen') {
                $this->assertNotEmpty($campo['ideal'] ?? null, "$clave necesita el tamaño ideal");
            }
            if (in_array($campo['tipo'], ['texto', 'titulo', 'parrafo'], true)) {
                $this->assertLessThanOrEqual($campo['max'], mb_strlen($campo['defecto']), "El original de $clave excede su máximo");
            }
            if ($campo['tipo'] === 'red') {
                $this->assertNotEmpty($campo['hosts'], $clave);
            }
        }
    }

    // ---------- Textos ----------

    public function test_cambiar_un_texto_lo_muestra_y_no_toca_lo_demas(): void
    {
        $this->guardar('hero', $this->cambiosDeSeccion('hero'))->assertRedirect();

        $this->get('/')->assertOk()
            ->assertSee('Texto nuevo hero__etiqueta')
            ->assertSee('Primera línea hero__titulo <br> Segunda línea', false)
            ->assertDontSee('SONRISAS QUE ENAMORAN')
            ->assertSee('Cuidar y transformar sonrisas') // misión: no se tocó
            ->assertSee('Plaza Marsella, Local 1');
    }

    public function test_el_html_en_los_textos_se_escapa(): void
    {
        $this->guardar('hero', ['hero__etiqueta' => '<script>alert(1)</script>', 'hero__titulo' => "<b>Hola</b>\n<i>mundo</i>"]);

        $this->get('/')->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<b>Hola</b>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertSee('&lt;b&gt;Hola&lt;/b&gt; <br> &lt;i&gt;mundo&lt;/i&gt;', false);
    }

    public function test_un_valor_igual_al_original_no_se_guarda(): void
    {
        $this->guardar('hero', ['hero__etiqueta' => 'SONRISAS QUE ENAMORAN', 'hero__boton' => 'AGENDAR CITA']);

        $this->assertDatabaseCount('landing_contenidos', 0);
    }

    public function test_guardar_el_formulario_sin_tocar_nada_no_crea_filas(): void
    {
        $original = [];
        foreach (LandingContenidoService::camposDeSeccion('hero') as $clave => $campo) {
            if ($campo['tipo'] !== 'imagen') {
                $original[LandingContenidoService::nombreDeCampo($clave)] = $campo['defecto'];
            }
        }

        $this->guardar('hero', $original)->assertSessionHasNoErrors();

        $this->assertDatabaseCount('landing_contenidos', 0);
    }

    public function test_dejar_un_campo_vacio_restaura_el_original(): void
    {
        $this->guardar('hero', ['hero__etiqueta' => 'Otra frase']);
        $this->assertDatabaseHas('landing_contenidos', ['clave' => 'hero.etiqueta', 'valor' => 'Otra frase']);

        $this->guardar('hero', ['hero__etiqueta' => ''])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('landing_contenidos', 0);
        $this->assertLandingOriginal();
    }

    public function test_un_texto_se_guarda_sin_espacios_ni_saltos_de_sobra(): void
    {
        $this->guardar('hero', ['hero__etiqueta' => "  Frase   con \n espacios  ", 'hero__titulo' => "  Uno  \n\n  Dos  "]);

        $this->assertDatabaseHas('landing_contenidos', ['clave' => 'hero.etiqueta', 'valor' => 'Frase con espacios']);
        $this->assertSame("Uno\nDos", LandingContenido::where('clave', 'hero.titulo')->value('valor'));
    }

    public function test_restaurar_un_solo_campo(): void
    {
        $this->guardar('hero', ['hero__etiqueta' => 'Frase nueva', 'hero__boton' => 'Reserva']);

        $this->actingAs($this->admin)->delete(route('admin.landing.restaurar-campo', 'hero.etiqueta'))->assertRedirect();

        $this->assertDatabaseMissing('landing_contenidos', ['clave' => 'hero.etiqueta']);
        $this->assertDatabaseHas('landing_contenidos', ['clave' => 'hero.boton', 'valor' => 'Reserva']);
    }

    public function test_validaciones_de_texto(): void
    {
        $this->guardar('hero', ['hero__etiqueta' => str_repeat('a', 61)])->assertSessionHasErrors('hero__etiqueta');
        $this->guardar('hero', ['hero__titulo' => "uno\ndos\ntres"])->assertSessionHasErrors(['hero__titulo' => 'El título puede tener como máximo 2 líneas.']);
        $this->guardar('hero', ['hero__titulo' => str_repeat('a', 121)])->assertSessionHasErrors('hero__titulo');
        $this->guardar('hero', ['hero__descripcion' => str_repeat('a', 301)])->assertSessionHasErrors('hero__descripcion');
        $this->guardar('nosotros', ['nosotros__mision_texto' => str_repeat('a', 501)])->assertSessionHasErrors('nosotros__mision_texto');
        $this->guardar('pie', ['pie__derechos' => str_repeat('a', 121)])->assertSessionHasErrors('pie__derechos');

        $this->assertDatabaseCount('landing_contenidos', 0);
    }

    public function test_un_error_en_un_campo_no_guarda_los_demas_de_la_seccion(): void
    {
        $this->guardar('hero', ['hero__etiqueta' => 'Válida', 'hero__boton' => str_repeat('a', 31)])->assertSessionHasErrors('hero__boton');

        $this->assertDatabaseCount('landing_contenidos', 0);
    }

    // ---------- Teléfono, redes y mapa ----------

    public static function telefonos(): array
    {
        return [
            'diez digitos' => ['5512345678', '5512345678'],
            'con espacios' => ['55 1234 5678', '5512345678'],
            'con guiones y parentesis' => ['(55) 1234-5678', '5512345678'],
            'con lada 52' => ['+52 55 1234 5678', '5512345678'],
            'con lada 521' => ['+52 1 55 1234 5678', '5512345678'],
        ];
    }

    #[DataProvider('telefonos')]
    public function test_el_telefono_se_normaliza_a_diez_digitos(string $escrito, string $guardado): void
    {
        $this->guardar('contacto', ['contacto__telefono' => $escrito, 'contacto__whatsapp' => $escrito])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('landing_contenidos', ['clave' => 'contacto.telefono', 'valor' => $guardado]);
    }

    public function test_telefonos_invalidos(): void
    {
        foreach (['12345', 'abcdefghij', '123456789012345', '555-1234'] as $malo) {
            $this->guardar('contacto', ['contacto__telefono' => $malo])->assertSessionHasErrors('contacto__telefono');
        }

        $this->assertDatabaseCount('landing_contenidos', 0);
    }

    public static function redes(): array
    {
        return [
            'instagram valido' => ['contacto__instagram', 'https://www.instagram.com/clinica', true],
            'instagram sin www' => ['contacto__instagram', 'https://instagram.com/clinica', true],
            'facebook valido' => ['contacto__facebook', 'https://www.facebook.com/clinica', true],
            'facebook fb.me' => ['contacto__facebook', 'https://fb.me/clinica', true],
            'instagram en facebook' => ['contacto__facebook', 'https://www.instagram.com/clinica', false],
            'http sin s' => ['contacto__instagram', 'http://www.instagram.com/clinica', false],
            'otro dominio' => ['contacto__instagram', 'https://evil.com/instagram.com', false],
            'subdominio falso' => ['contacto__instagram', 'https://instagram.com.evil.com/x', false],
            'javascript' => ['contacto__instagram', 'javascript:alert(1)', false],
            'sin esquema' => ['contacto__instagram', 'www.instagram.com/clinica', false],
            'con espacio' => ['contacto__instagram', 'https://www.instagram.com/mi clinica', false],
        ];
    }

    #[DataProvider('redes')]
    public function test_validacion_de_redes_sociales(string $campo, string $enlace, bool $valido): void
    {
        $respuesta = $this->guardar('contacto', [$campo => $enlace]);

        $valido ? $respuesta->assertSessionHasNoErrors() : $respuesta->assertSessionHasErrors($campo);
        $this->assertDatabaseCount('landing_contenidos', $valido ? 1 : 0);
    }

    public function test_las_redes_guardadas_salen_en_el_pie(): void
    {
        $this->guardar('contacto', ['contacto__instagram' => 'https://www.instagram.com/clinica', 'contacto__facebook' => 'https://www.facebook.com/clinica']);

        $this->get('/')->assertOk()
            ->assertSee('href="https://www.instagram.com/clinica"', false)
            ->assertSee('href="https://www.facebook.com/clinica"', false);
    }

    public function test_el_mapa_acepta_la_direccion_o_el_codigo_iframe_completo(): void
    {
        $src = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d100!2d-102.2!3d17.95!5e0!3m2!1ses!2smx!4v111';
        $iframe = '<iframe src="' . $src . '" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>';

        $this->guardar('contacto', ['contacto__mapa' => $iframe])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('landing_contenidos', ['clave' => 'contacto.mapa', 'valor' => $src]);
        $this->get('/')->assertSee('src="' . $src . '"', false);

        $this->guardar('contacto', ['contacto__mapa' => 'https://www.google.com/maps/embed?pb=otro'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('landing_contenidos', ['clave' => 'contacto.mapa', 'valor' => 'https://www.google.com/maps/embed?pb=otro']);
    }

    public static function mapasInvalidos(): array
    {
        return [
            'otro dominio' => ['https://evil.com/maps/embed?pb=1'],
            'dominio parecido' => ['https://www.google.com.evil.com/maps/embed?pb=1'],
            'javascript' => ['javascript:alert(1)'],
            'enlace normal de maps' => ['https://www.google.com/maps/place/Clinica'],
            'enlace corto' => ['https://maps.app.goo.gl/abc123'],
            'http sin s' => ['http://www.google.com/maps/embed?pb=1'],
            'iframe de otro sitio' => ['<iframe src="https://evil.com/embed" width="600"></iframe>'],
            'iframe con javascript' => ['<iframe src="javascript:alert(1)"></iframe>'],
            'texto cualquiera' => ['mi consultorio'],
        ];
    }

    #[DataProvider('mapasInvalidos')]
    public function test_mapas_invalidos_se_rechazan(string $mapa): void
    {
        $this->guardar('contacto', ['contacto__mapa' => $mapa])->assertSessionHasErrors('contacto__mapa');

        $this->assertDatabaseCount('landing_contenidos', 0);
    }

    // ---------- Imágenes ----------

    public function test_subir_una_imagen_reemplaza_a_la_original_en_la_landing(): void
    {
        $this->guardar('hero', ['hero__imagen' => Img::pngSubido('portada.png')])->assertSessionHasNoErrors();

        $ruta = LandingContenido::where('clave', 'hero.imagen')->value('valor');
        $this->assertMatchesRegularExpression('#^landing/[a-z0-9]{26}\.png$#', $ruta);
        Storage::disk('imagenes')->assertExists($ruta);

        $html = $this->landing();
        $this->assertStringContainsString($ruta, $html);
        $this->assertStringNotContainsString('img/image 1.png', $html);
        // lo demás sigue con su original
        $this->assertStringContainsString('img/mision.png', $html);
    }

    public function test_reemplazar_la_imagen_borra_la_anterior(): void
    {
        $this->guardar('hero', ['hero__imagen' => Img::pngSubido()]);
        $primera = LandingContenido::where('clave', 'hero.imagen')->value('valor');

        $this->guardar('hero', ['hero__imagen' => Img::archivo('b.jpg', Img::jpg())]);
        $segunda = LandingContenido::where('clave', 'hero.imagen')->value('valor');

        $this->assertNotSame($primera, $segunda);
        $this->assertSame([$segunda], $this->archivos());
    }

    public function test_guardar_otros_campos_conserva_la_imagen_subida(): void
    {
        $this->guardar('hero', ['hero__imagen' => Img::pngSubido()]);
        $ruta = LandingContenido::where('clave', 'hero.imagen')->value('valor');

        $this->guardar('hero', ['hero__etiqueta' => 'Frase nueva'])->assertSessionHasNoErrors();

        $this->assertSame($ruta, LandingContenido::where('clave', 'hero.imagen')->value('valor'));
        $this->assertSame([$ruta], $this->archivos());
    }

    public function test_restaurar_la_imagen_borra_el_archivo_subido_y_vuelve_a_la_original(): void
    {
        $this->guardar('nosotros', ['nosotros__mision_imagen' => Img::pngSubido(), 'nosotros__vision_imagen' => Img::pngSubido()]);
        $this->assertCount(2, $this->archivos());

        $this->actingAs($this->admin)->delete(route('admin.landing.restaurar-campo', 'nosotros.mision_imagen'))->assertRedirect();

        $this->assertCount(1, $this->archivos());
        $html = $this->landing();
        $this->assertStringContainsString('img/mision.png', $html);
        $this->assertStringNotContainsString('img/vision.png', $html);
    }

    public function test_nunca_se_borran_los_archivos_originales_de_public(): void
    {
        $original = public_path('img/image 1.png');
        $this->assertFileExists($original);

        $this->guardar('hero', ['hero__imagen' => Img::pngSubido()]);
        $this->actingAs($this->admin)->delete(route('admin.landing.restaurar-seccion', 'hero'));

        $this->assertFileExists($original);
    }

    public function test_imagenes_invalidas_se_rechazan_sin_guardar_nada(): void
    {
        $malos = [
            Img::archivo('falso.jpg', 'no soy imagen'),
            Img::archivo('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            Img::pngSubido('pesada.png', 1, 1, 5 * 1024 * 1024 + 4096),
            Img::pngSubido('enorme.png', 5000, 5000),
        ];

        foreach ($malos as $archivo) {
            $this->guardar('hero', ['hero__imagen' => $archivo, 'hero__etiqueta' => 'No debe guardarse'])->assertSessionHasErrors('hero__imagen');
        }

        $this->assertDatabaseCount('landing_contenidos', 0);
        $this->assertSame([], $this->archivos());
    }

    public function test_un_error_de_texto_no_deja_la_imagen_nueva_huerfana(): void
    {
        $this->guardar('hero', ['hero__imagen' => Img::pngSubido(), 'hero__boton' => str_repeat('a', 31)])->assertSessionHasErrors('hero__boton');

        $this->assertSame([], $this->archivos());
    }

    public function test_si_falla_el_guardado_en_la_base_se_borran_las_imagenes_recien_subidas(): void
    {
        LandingContenido::creating(fn () => throw new \RuntimeException('base caída'));
        $this->withoutExceptionHandling();

        try {
            $this->guardar('hero', ['hero__imagen' => Img::pngSubido(), 'hero__etiqueta' => 'Otra']);
            $this->fail('Debió propagar el error.');
        } catch (\RuntimeException $e) {
            $this->assertSame('base caída', $e->getMessage());
        }

        $this->assertSame([], $this->archivos());
        $this->assertDatabaseCount('landing_contenidos', 0);
    }

    public function test_una_ruta_de_imagen_invalida_en_la_base_cae_a_la_original(): void
    {
        LandingContenido::create(['clave' => 'hero.imagen', 'valor' => '../../.env']);

        $html = $this->landing();

        $this->assertStringContainsString('img/image 1.png', $html);
        $this->assertStringNotContainsString('.env', $html);
    }

    // ---------- Panel ----------

    public function test_solo_el_admin_entra(): void
    {
        $rutas = [
            ['get', route('admin.landing.contenido')],
            ['get', route('admin.landing.contacto')],
            ['put', route('admin.landing.guardar', 'hero')],
            ['delete', route('admin.landing.restaurar-seccion', 'hero')],
            ['delete', route('admin.landing.restaurar-campo', 'hero.titulo')],
        ];

        foreach ($rutas as [$metodo, $url]) {
            $this->{$metodo}($url)->assertRedirect('/login');
        }

        foreach (['asistente', 'paciente'] as $rol) {
            $usuario = $this->crearUsuario($rol);
            foreach ($rutas as [$metodo, $url]) {
                $this->actingAs($usuario)->{$metodo}($url, ['hero__etiqueta' => 'Intruso'])->assertForbidden();
            }
        }

        $this->assertDatabaseCount('landing_contenidos', 0);
    }

    public function test_secciones_y_campos_desconocidos_dan_404(): void
    {
        $this->guardar('inexistente', ['x' => 'y'])->assertNotFound();
        $this->actingAs($this->admin)->delete(route('admin.landing.restaurar-seccion', 'inexistente'))->assertNotFound();
        $this->actingAs($this->admin)->delete(route('admin.landing.restaurar-campo', 'hero.inexistente'))->assertNotFound();
        $this->actingAs($this->admin)->delete('/admin/landing/campos/HERO.titulo')->assertNotFound();
    }

    public function test_las_pestanas_muestran_sus_secciones_con_vista_previa_y_tamano_ideal(): void
    {
        $this->actingAs($this->admin)->get(route('admin.landing.contenido'))
            ->assertOk()
            ->assertSee('Imágenes y textos')
            ->assertSee('id="seccion-hero"', false)
            ->assertSee('id="seccion-nosotros"', false)
            ->assertSee('id="seccion-certificados"', false)
            ->assertDontSee('id="seccion-contacto"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('Imagen actual: Imagen de portada')
            ->assertSee('1160 × 846 px')
            ->assertSee('máximo 5 MB')
            ->assertSee('La imagen original no está disponible');

        $this->actingAs($this->admin)->get(route('admin.landing.contacto'))
            ->assertOk()
            ->assertSee('id="seccion-contacto"', false)
            ->assertSee('id="seccion-pie"', false)
            ->assertDontSee('id="seccion-hero"', false)
            ->assertSee('name="contacto__mapa"', false);
    }

    public function test_el_panel_muestra_el_texto_actual_y_marca_lo_modificado(): void
    {
        $this->actingAs($this->admin)->get(route('admin.landing.contenido'))
            ->assertOk()
            ->assertSee('SONRISAS QUE ENAMORAN')
            ->assertDontSee('Modificada')
            ->assertDontSee('Restaurar toda la sección')
            ->assertDontSee('Restaurar el original');

        $this->guardar('hero', ['hero__etiqueta' => 'Frase nueva']);

        $this->actingAs($this->admin)->get(route('admin.landing.contenido'))
            ->assertOk()
            ->assertSee('value="Frase nueva"', false)
            ->assertSee('Modificada')
            ->assertSee('Restaurar toda la sección')
            ->assertSee('Restaurar el original')
            ->assertSee(route('admin.landing.restaurar-campo', 'hero.etiqueta'), false);
    }

    public function test_el_boton_restaurar_de_una_imagen_solo_aparece_si_se_subio_una(): void
    {
        $this->actingAs($this->admin)->get(route('admin.landing.contenido'))->assertDontSee('Restaurar la original');

        $this->guardar('hero', ['hero__imagen' => Img::pngSubido()]);

        $this->actingAs($this->admin)->get(route('admin.landing.contenido'))
            ->assertSee('Restaurar la original')
            ->assertSee(route('admin.landing.restaurar-campo', 'hero.imagen'), false);
    }

    public function test_la_vista_previa_muestra_la_imagen_subida(): void
    {
        $this->guardar('hero', ['hero__imagen' => Img::pngSubido()]);
        $ruta = LandingContenido::where('clave', 'hero.imagen')->value('valor');

        $this->actingAs($this->admin)->get(route('admin.landing.contenido'))
            ->assertOk()
            ->assertSee($ruta);
    }

    public function test_un_error_de_validacion_conserva_lo_escrito_y_muestra_el_mensaje(): void
    {
        $this->actingAs($this->admin)->from(route('admin.landing.contacto'))->followingRedirects()
            ->put(route('admin.landing.guardar', 'contacto'), ['contacto__telefono' => '12345', 'contacto__horario' => 'Lunes a viernes'])
            ->assertOk()
            ->assertSee('debe tener 10 dígitos')
            ->assertSee('value="12345"', false)
            ->assertSee('value="Lunes a viernes"', false);
    }

    public function test_los_mensajes_de_exito_y_el_retorno_a_la_seccion(): void
    {
        $this->guardar('hero', ['hero__etiqueta' => 'Frase nueva'])
            ->assertRedirect(route('admin.landing.contenido') . '#seccion-hero')
            ->assertSessionHas('success');

        $this->guardar('pie', ['pie__derechos' => 'Otro'])
            ->assertRedirect(route('admin.landing.contacto') . '#seccion-pie');
    }

    public function test_las_pestanas_de_landing_page_estan_en_las_tres_pantallas(): void
    {
        foreach ([route('admin.anuncios.index'), route('admin.landing.contenido'), route('admin.landing.contacto')] as $url) {
            $this->actingAs($this->admin)->get($url)
                ->assertOk()
                ->assertSee('Secciones de Landing Page')
                ->assertSee('href="' . route('admin.anuncios.index') . '"', false)
                ->assertSee('href="' . route('admin.landing.contenido') . '"', false)
                ->assertSee('href="' . route('admin.landing.contacto') . '"', false);
        }
    }
}
