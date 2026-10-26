<?php

namespace Tests\Feature\Admin;

use App\Models\LandingContenido;
use App\Models\LandingItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ImagenesDePrueba as Img;
use Tests\TestCase;

/** Galería de la landing (#galeria): agregar, quitar, reordenar y editar fotos */
class LandingGaleriaTest extends TestCase
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

    /** Las fotos de la cuadrícula de la landing, en orden: [src, titulo, subtitulo] ya decodificados */
    private function galeriaEnLanding(): array
    {
        preg_match_all('/<li data-galeria-foto data-src="([^"]*)" data-titulo="([^"]*)" data-subtitulo="([^"]*)"/', $this->landing(), $coincidencias, PREG_SET_ORDER);

        return array_map(fn ($m) => array_map(fn ($v) => html_entity_decode($v, ENT_QUOTES), [$m[1], $m[2], $m[3]]), $coincidencias);
    }

    private function archivos(): array
    {
        return Storage::disk('imagenes')->allFiles();
    }

    private function agregar(array $datos = [])
    {
        return $this->actingAs($this->admin)->post(route('admin.landing.galeria.agregar'), array_merge(['imagen' => Img::pngSubido()], $datos));
    }

    private function idDe(string $original): int
    {
        return LandingItem::where('original', $original)->value('id');
    }

    private function titulosEnOrden(): array
    {
        return LandingItem::where('coleccion', 'galeria')->orderBy('orden')->get()->map(fn ($f) => $f->datos['titulo'])->all();
    }

    // ---------- La galería no cambia ----------

    public function test_sin_cambios_la_landing_es_identica_y_no_hay_filas(): void
    {
        $this->assertLandingOriginal();
        $this->assertDatabaseCount('landing_items', 0);
    }

    public function test_ver_el_panel_no_guarda_nada_y_muestra_las_cinco_originales(): void
    {
        $this->actingAs($this->admin)->get(route('admin.landing.galeria'))
            ->assertOk()
            ->assertSee('Landing Page · Galería')
            ->assertSee('5 de 12')
            ->assertSee('Diseño de Sonrisa')
            ->assertSee('Limpieza Dental')
            ->assertSee('Profunda y sin dolor')
            ->assertSee(route('admin.landing.galeria.subir', 'galeria-1'), false)
            ->assertDontSee('Modificada')
            ->assertDontSee('Restaurar la galería original');

        $this->assertDatabaseCount('landing_items', 0);
    }

    public function test_la_galeria_original_se_lee_igual_que_antes(): void
    {
        $fotos = $this->galeriaEnLanding();

        $this->assertCount(5, $fotos);
        $this->assertSame(['Diseño de Sonrisa', 'Ortodoncia', 'Blanqueamiento', 'Implantes', 'Limpieza Dental'], array_column($fotos, 1));
        $this->assertStringEndsWith('img/galeria-1.png', $fotos[0][0]);
        $this->assertSame('Solución permanente', $fotos[3][2]);
    }

    public function test_si_falla_la_lectura_de_la_galeria_la_landing_se_ve_original(): void
    {
        Schema::drop('landing_items');

        $this->assertLandingOriginal();
    }

    public function test_el_titulo_de_la_galeria_es_editable_y_se_restaura(): void
    {
        $this->actingAs($this->admin)->put(route('admin.landing.guardar', 'galeria'), ['galeria__titulo' => 'TRANSFORMACIONES REALES'])
            ->assertRedirect(route('admin.landing.galeria') . '#seccion-galeria');

        $this->get('/')->assertSee('TRANSFORMACIONES REALES')->assertDontSee('TRASFORMACIONES');

        $this->actingAs($this->admin)->delete(route('admin.landing.restaurar-seccion', 'galeria'));

        $this->assertLandingOriginal();
    }

    // ---------- Agregar ----------

    public function test_agregar_una_foto_la_pone_al_final_y_conserva_las_originales(): void
    {
        $this->agregar(['titulo' => 'Caso nuevo', 'subtitulo' => 'Antes y después'])->assertRedirect(route('admin.landing.galeria'))->assertSessionHas('success');

        $this->assertDatabaseCount('landing_items', 6);
        $this->assertSame(['Diseño de Sonrisa', 'Ortodoncia', 'Blanqueamiento', 'Implantes', 'Limpieza Dental', 'Caso nuevo'], $this->titulosEnOrden());
        $this->assertSame([1, 2, 3, 4, 5, 6], LandingItem::orderBy('orden')->pluck('orden')->all());

        $nueva = LandingItem::where('original', null)->first();
        $this->assertMatchesRegularExpression('#^landing/[a-z0-9]{26}\.png$#', $nueva->imagen);
        Storage::disk('imagenes')->assertExists($nueva->imagen);
        $this->assertSame([$nueva->imagen], $this->archivos(), 'Las originales no suben archivos.');

        $fotos = $this->galeriaEnLanding();
        $this->assertCount(6, $fotos);
        $this->assertStringEndsWith('img/galeria-1.png', $fotos[0][0], 'Las originales siguen con su imagen de public/img.');
        $this->assertSame(['Caso nuevo', 'Antes y después'], [$fotos[5][1], $fotos[5][2]]);
        $this->assertStringContainsString($nueva->imagen, $fotos[5][0]);
    }

    public function test_agregar_sin_textos_es_valido(): void
    {
        $this->agregar()->assertSessionHasNoErrors();

        $this->assertSame(['titulo' => '', 'subtitulo' => ''], LandingItem::where('original', null)->first()->datos);
    }

    public function test_agregar_exige_una_imagen_valida(): void
    {
        $malos = [
            null,
            Img::archivo('falso.jpg', 'no soy imagen'),
            Img::archivo('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            Img::pngSubido('pesada.png', 1, 1, 5 * 1024 * 1024 + 4096),
            Img::pngSubido('enorme.png', 5000, 5000),
        ];

        foreach ($malos as $archivo) {
            $respuesta = $this->actingAs($this->admin)->post(route('admin.landing.galeria.agregar'), $archivo ? ['imagen' => $archivo] : []);
            $respuesta->assertSessionHasErrorsIn('nueva', 'imagen');
        }

        $this->assertDatabaseCount('landing_items', 0); // un error no debe ni materializar la galería
        $this->assertSame([], $this->archivos());
        $this->assertLandingOriginal();
    }

    public function test_los_textos_tienen_limite(): void
    {
        $this->agregar(['titulo' => str_repeat('a', 61)])->assertSessionHasErrorsIn('nueva', 'titulo');
        $this->agregar(['subtitulo' => str_repeat('a', 81)])->assertSessionHasErrorsIn('nueva', 'subtitulo');

        $this->assertDatabaseCount('landing_items', 0);
        $this->assertSame([], $this->archivos());
    }

    public function test_hay_un_maximo_de_doce_fotos(): void
    {
        foreach (range(1, 7) as $n) {
            $this->agregar(['titulo' => "Foto $n"])->assertSessionHasNoErrors();
        }
        $this->assertDatabaseCount('landing_items', 12);
        $archivosAntes = $this->archivos();

        $this->agregar(['titulo' => 'La trece'])
            ->assertRedirect(route('admin.landing.galeria'))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'máximo 12'));

        $this->assertDatabaseCount('landing_items', 12);
        $this->assertSame($archivosAntes, $this->archivos(), 'La imagen de la foto rechazada no debe quedar guardada.');
    }

    public function test_el_panel_avisa_cuando_la_galeria_esta_llena(): void
    {
        foreach (range(1, 7) as $n) {
            $this->agregar();
        }

        $this->actingAs($this->admin)->get(route('admin.landing.galeria'))
            ->assertOk()
            ->assertSee('12 de 12')
            ->assertSee('ya tiene el máximo de 12 fotos')
            ->assertDontSee('name="imagen" accept="image/jpeg,image/png,image/webp" required', false);
    }

    public function test_si_falla_el_guardado_se_borra_la_imagen_recien_subida(): void
    {
        LandingItem::creating(function ($item) {
            if ($item->original === null) {
                throw new \RuntimeException('base caída');
            }
        });
        $this->withoutExceptionHandling();

        try {
            $this->agregar(['titulo' => 'No debe guardarse']);
            $this->fail('Debió propagar el error.');
        } catch (\RuntimeException $e) {
            $this->assertSame('base caída', $e->getMessage());
        }

        $this->assertSame([], $this->archivos());
        $this->assertDatabaseCount('landing_items', 0); // la transacción deshace también la galería materializada
        $this->assertLandingOriginal();
    }

    // ---------- Quitar ----------

    public function test_quitar_una_foto_original_la_saca_de_la_landing(): void
    {
        $this->actingAs($this->admin)->delete(route('admin.landing.galeria.eliminar', 'galeria-2'))
            ->assertRedirect(route('admin.landing.galeria'))
            ->assertSessionHas('success');

        $this->assertSame(['Diseño de Sonrisa', 'Blanqueamiento', 'Implantes', 'Limpieza Dental'], $this->titulosEnOrden());
        $this->assertSame([1, 2, 3, 4], LandingItem::orderBy('orden')->pluck('orden')->all());
        $this->assertSame(['Diseño de Sonrisa', 'Blanqueamiento', 'Implantes', 'Limpieza Dental'], array_column($this->galeriaEnLanding(), 1));
    }

    public function test_quitar_una_foto_agregada_borra_su_imagen(): void
    {
        $this->agregar(['titulo' => 'Mía']);
        $this->agregar(['titulo' => 'Otra']);
        $mia = LandingItem::where('datos->titulo', 'Mía')->first();
        $otra = LandingItem::where('datos->titulo', 'Otra')->first();

        $this->actingAs($this->admin)->delete(route('admin.landing.galeria.eliminar', $mia->id))->assertRedirect();

        $this->assertDatabaseMissing('landing_items', ['id' => $mia->id]);
        $this->assertSame([$otra->imagen], $this->archivos());
    }

    public function test_siempre_queda_al_menos_una_foto(): void
    {
        foreach (['galeria-1', 'galeria-2', 'galeria-3', 'galeria-4'] as $original) {
            $this->actingAs($this->admin)->delete(route('admin.landing.galeria.eliminar', $original))->assertSessionHas('success');
        }
        $this->assertDatabaseCount('landing_items', 1);

        $this->actingAs($this->admin)->delete(route('admin.landing.galeria.eliminar', $this->idDe('galeria-5')))
            ->assertRedirect(route('admin.landing.galeria'))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'al menos una foto'));

        $this->assertDatabaseCount('landing_items', 1);
        $this->assertCount(1, $this->galeriaEnLanding());
    }

    public function test_con_una_sola_foto_el_boton_de_quitar_esta_desactivado(): void
    {
        foreach (['galeria-1', 'galeria-2', 'galeria-3', 'galeria-4'] as $original) {
            $this->actingAs($this->admin)->delete(route('admin.landing.galeria.eliminar', $original));
        }

        $this->actingAs($this->admin)->get(route('admin.landing.galeria'))
            ->assertOk()
            ->assertSee('1 de 12')
            ->assertSee('La galería debe tener al menos una foto');
    }

    public function test_quitar_una_foto_que_no_existe_da_404(): void
    {
        $this->actingAs($this->admin)->delete(route('admin.landing.galeria.eliminar', 'galeria-99'))->assertNotFound();
        $this->actingAs($this->admin)->delete(route('admin.landing.galeria.eliminar', '9999'))->assertNotFound();
    }

    // ---------- Reordenar ----------

    public function test_subir_y_bajar_cambian_el_orden_en_la_landing(): void
    {
        $this->actingAs($this->admin)->patch(route('admin.landing.galeria.subir', 'galeria-3'))->assertRedirect(route('admin.landing.galeria'));
        $this->assertSame(['Diseño de Sonrisa', 'Blanqueamiento', 'Ortodoncia', 'Implantes', 'Limpieza Dental'], $this->titulosEnOrden());

        $this->actingAs($this->admin)->patch(route('admin.landing.galeria.bajar', $this->idDe('galeria-1')));
        $this->assertSame(['Blanqueamiento', 'Diseño de Sonrisa', 'Ortodoncia', 'Implantes', 'Limpieza Dental'], $this->titulosEnOrden());

        $this->assertSame(['Blanqueamiento', 'Diseño de Sonrisa', 'Ortodoncia', 'Implantes', 'Limpieza Dental'], array_column($this->galeriaEnLanding(), 1));
    }

    public function test_la_primera_no_sube_y_la_ultima_no_baja(): void
    {
        $this->actingAs($this->admin)->patch(route('admin.landing.galeria.subir', 'galeria-1'))->assertRedirect();
        $this->actingAs($this->admin)->patch(route('admin.landing.galeria.bajar', $this->idDe('galeria-5')))->assertRedirect();

        $this->assertSame(['Diseño de Sonrisa', 'Ortodoncia', 'Blanqueamiento', 'Implantes', 'Limpieza Dental'], $this->titulosEnOrden());
        $this->assertSame([1, 2, 3, 4, 5], LandingItem::orderBy('orden')->pluck('orden')->all());
    }

    public function test_mover_corrige_empates_y_huecos(): void
    {
        $this->actingAs($this->admin)->patch(route('admin.landing.galeria.subir', 'galeria-2'));
        LandingItem::query()->update(['orden' => 0]);

        $this->actingAs($this->admin)->patch(route('admin.landing.galeria.bajar', $this->idDe('galeria-1')));

        $this->assertSame([1, 2, 3, 4, 5], LandingItem::orderBy('orden')->pluck('orden')->all());
    }

    // ---------- Editar ----------

    public function test_editar_los_textos_de_una_foto(): void
    {
        $this->actingAs($this->admin)->put(route('admin.landing.galeria.actualizar', 'galeria-1'), ['titulo' => 'Carillas', 'subtitulo' => 'Resultado en 2 visitas'])
            ->assertRedirect(route('admin.landing.galeria'))
            ->assertSessionHas('success');

        $fotos = $this->galeriaEnLanding();
        $this->assertSame(['Carillas', 'Resultado en 2 visitas'], [$fotos[0][1], $fotos[0][2]]);
        $this->assertSame('Ortodoncia', $fotos[1][1], 'Las demás no cambian.');
        $this->assertStringEndsWith('img/galeria-1.png', $fotos[0][0], 'La imagen original se conserva.');
        $this->assertSame([], $this->archivos());
    }

    public function test_cambiar_la_imagen_de_una_original_y_restaurarla(): void
    {
        $this->actingAs($this->admin)->put(route('admin.landing.galeria.actualizar', 'galeria-2'), ['titulo' => 'Ortodoncia', 'subtitulo' => 'Brackets y alineadores', 'imagen' => Img::pngSubido()])
            ->assertSessionHasNoErrors();

        $foto = LandingItem::where('original', 'galeria-2')->first();
        Storage::disk('imagenes')->assertExists($foto->imagen);
        $this->assertStringContainsString($foto->imagen, $this->galeriaEnLanding()[1][0]);

        $this->actingAs($this->admin)->delete(route('admin.landing.galeria.restaurar-imagen', $foto->id))->assertRedirect()->assertSessionHas('success');

        $this->assertNull($foto->fresh()->imagen);
        $this->assertSame([], $this->archivos());
        $this->assertStringEndsWith('img/galeria-2.png', $this->galeriaEnLanding()[1][0]);
    }

    public function test_reemplazar_la_imagen_borra_la_anterior(): void
    {
        $this->agregar(['titulo' => 'Mía']);
        $foto = LandingItem::where('original', null)->first();
        $anterior = $foto->imagen;

        $this->actingAs($this->admin)->put(route('admin.landing.galeria.actualizar', $foto->id), ['titulo' => 'Mía', 'imagen' => Img::archivo('b.jpg', Img::jpg())])
            ->assertSessionHasNoErrors();

        $nueva = $foto->fresh()->imagen;
        $this->assertNotSame($anterior, $nueva);
        $this->assertSame([$nueva], $this->archivos());
    }

    public function test_una_imagen_invalida_al_editar_no_toca_nada(): void
    {
        $this->agregar(['titulo' => 'Mía']);
        $foto = LandingItem::where('original', null)->first();

        $this->actingAs($this->admin)->put(route('admin.landing.galeria.actualizar', $foto->id), ['titulo' => 'Cambio', 'imagen' => Img::archivo('falso.png', 'no soy imagen')])
            ->assertSessionHasErrorsIn('foto_' . $foto->id, 'imagen');

        $this->assertSame('Mía', $foto->fresh()->datos['titulo']);
        $this->assertSame([$foto->imagen], $this->archivos());
    }

    public function test_un_texto_muy_largo_al_editar_se_rechaza_en_su_propia_foto(): void
    {
        $this->actingAs($this->admin)->put(route('admin.landing.galeria.actualizar', 'galeria-1'), ['titulo' => str_repeat('a', 61)])
            ->assertSessionHasErrorsIn('foto_galeria-1', 'titulo');

        $this->assertDatabaseCount('landing_items', 0);
    }

    public function test_restaurar_la_imagen_de_una_foto_agregada_da_404(): void
    {
        $this->agregar();
        $foto = LandingItem::where('original', null)->first();

        $this->actingAs($this->admin)->delete(route('admin.landing.galeria.restaurar-imagen', $foto->id))->assertNotFound();
        $this->assertNotNull($foto->fresh()->imagen);
    }

    // ---------- Restaurar la galería ----------

    public function test_restaurar_la_galeria_borra_todo_y_deja_la_landing_identica(): void
    {
        $this->agregar(['titulo' => 'Nueva']);
        $this->actingAs($this->admin)->delete(route('admin.landing.galeria.eliminar', 'galeria-1'));
        $this->actingAs($this->admin)->patch(route('admin.landing.galeria.subir', $this->idDe('galeria-3')));
        $this->actingAs($this->admin)->put(route('admin.landing.galeria.actualizar', $this->idDe('galeria-2')), ['titulo' => 'Otro', 'imagen' => Img::pngSubido()]);
        $this->assertNotEmpty($this->archivos());

        $this->actingAs($this->admin)->delete(route('admin.landing.galeria.restaurar'))
            ->assertRedirect(route('admin.landing.galeria'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('landing_items', 0);
        $this->assertSame([], $this->archivos());
        $this->assertLandingOriginal();
    }

    public function test_el_panel_marca_la_galeria_modificada(): void
    {
        $this->agregar(['titulo' => 'Nueva']);

        $this->actingAs($this->admin)->get(route('admin.landing.galeria'))
            ->assertOk()
            ->assertSee('6 de 12')
            ->assertSee('Modificada')
            ->assertSee('Restaurar la galería original')
            ->assertSee('Nueva');
    }

    // ---------- Cómo se ve en la landing ----------

    public function test_todas_las_fotos_salen_en_cuadricula_con_lazy_y_texto_alternativo(): void
    {
        $html = $this->landing();

        $this->assertCount(5, $this->galeriaEnLanding());
        foreach (['Diseño de Sonrisa', 'Ortodoncia', 'Blanqueamiento', 'Implantes', 'Limpieza Dental'] as $titulo) {
            $this->assertMatchesRegularExpression('#<img src="[^"]+" alt="' . preg_quote(e($titulo), '#') . '" loading="lazy"#', $html);
            $this->assertStringContainsString('aria-label="Ampliar foto: ' . e($titulo) . '"', $html);
        }
        // Se ven todas a la vez: ya no hay carrusel con flechas ni puntos
        $this->assertStringNotContainsString('@click="actual = index"', $html);
    }

    public function test_el_lightbox_es_accesible_con_teclado(): void
    {
        $html = $this->landing();

        $this->assertStringContainsString('x-data="galeriaLightbox()"', $html);
        $this->assertStringContainsString('@keydown.escape.window="cerrar()"', $html);
        $this->assertStringContainsString('role="dialog" aria-modal="true"', $html);
        $this->assertStringContainsString('@keydown.tab="atraparFoco($event)"', $html);
        $this->assertStringContainsString('@keydown.arrow-right.prevent="siguiente()"', $html);
        foreach (['Foto anterior', 'Cerrar', 'Foto siguiente'] as $boton) {
            $this->assertStringContainsString('aria-label="' . $boton . '"', $html);
        }
        // Empieza cerrado aunque Alpine no haya cargado
        $this->assertMatchesRegularExpression('/<div x-show="abierta" style="display: none"/', $html);
    }

    public function test_con_menos_de_tres_fotos_la_cuadricula_queda_centrada(): void
    {
        foreach (['galeria-1', 'galeria-2', 'galeria-3'] as $original) {
            $this->actingAs($this->admin)->delete(route('admin.landing.galeria.eliminar', $original));
        }

        $this->assertCount(2, $this->galeriaEnLanding());
        $this->assertStringContainsString('<ul class="flex flex-wrap justify-center gap-6">', $this->landing());
    }

    public function test_sin_fotos_la_seccion_no_se_muestra(): void
    {
        config(['landing.colecciones.galeria.originales' => []]);

        $this->get('/')->assertOk()
            ->assertDontSee('id="galeria"', false)
            ->assertDontSee('galeriaLightbox', false)
            ->assertSee('id="contacto"', false);
    }

    public function test_los_textos_con_comillas_y_html_no_rompen_los_atributos_ni_la_pagina(): void
    {
        $this->agregar(['titulo' => "O'Brien \"x\" <b>negrita</b> \\ barra", 'subtitulo' => "Línea\nnueva </script><script>alert(1)</script>"]);

        $html = $this->landing();

        $this->assertStringNotContainsString('<b>negrita</b>', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);

        $fotos = $this->galeriaEnLanding();
        $this->assertCount(6, $fotos, 'Cada foto debe seguir siendo un elemento válido.');
        $this->assertSame("O'Brien \"x\" <b>negrita</b> \\ barra", $fotos[5][1]);
        $this->assertSame('Línea nueva </script><script>alert(1)</script>', $fotos[5][2]); // el subtítulo se guarda en una línea
    }

    public function test_una_ruta_de_imagen_invalida_en_la_base_no_rompe_la_landing(): void
    {
        $this->agregar(['titulo' => 'Mía']);
        LandingItem::where('original', null)->update(['imagen' => '../../.env']);

        $html = $this->landing();

        $this->assertStringNotContainsString('.env', $html);
        $this->assertCount(6, $this->galeriaEnLanding());
    }

    // ---------- Acceso ----------

    public function test_solo_el_admin_entra(): void
    {
        $rutas = [
            ['get', route('admin.landing.galeria')],
            ['post', route('admin.landing.galeria.agregar')],
            ['delete', route('admin.landing.galeria.restaurar')],
            ['put', route('admin.landing.galeria.actualizar', 'galeria-1')],
            ['delete', route('admin.landing.galeria.eliminar', 'galeria-1')],
            ['patch', route('admin.landing.galeria.subir', 'galeria-2')],
            ['patch', route('admin.landing.galeria.bajar', 'galeria-2')],
            ['delete', route('admin.landing.galeria.restaurar-imagen', 'galeria-1')],
        ];

        foreach ($rutas as [$metodo, $url]) {
            $this->{$metodo}($url)->assertRedirect('/login');
        }

        foreach (['asistente', 'paciente'] as $rol) {
            $usuario = $this->crearUsuario($rol);
            foreach ($rutas as [$metodo, $url]) {
                $this->actingAs($usuario)->{$metodo}($url, ['imagen' => Img::pngSubido()])->assertForbidden();
            }
        }

        $this->assertDatabaseCount('landing_items', 0);
        $this->assertSame([], $this->archivos());
        $this->assertDatabaseCount('landing_contenidos', 0);
    }

    public function test_la_pestana_galeria_esta_en_todas_las_pantallas_de_landing_page(): void
    {
        foreach ([route('admin.anuncios.index'), route('admin.landing.contenido'), route('admin.landing.galeria'), route('admin.landing.contacto')] as $url) {
            $this->actingAs($this->admin)->get($url)
                ->assertOk()
                ->assertSee('href="' . route('admin.landing.galeria') . '"', false);
        }
    }

    public function test_el_titulo_de_la_galeria_no_se_mezcla_con_otras_secciones(): void
    {
        $this->actingAs($this->admin)->get(route('admin.landing.contenido'))->assertDontSee('id="seccion-galeria"', false);
        $this->actingAs($this->admin)->get(route('admin.landing.galeria'))
            ->assertSee('id="seccion-galeria"', false)
            ->assertDontSee('id="seccion-hero"', false);
        $this->assertDatabaseCount('landing_contenidos', 0);
        $this->assertSame(0, LandingContenido::count());
    }
}
