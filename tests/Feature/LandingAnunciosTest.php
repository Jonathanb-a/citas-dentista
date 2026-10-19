<?php

namespace Tests\Feature;

use App\Models\Anuncio;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LandingAnunciosTest extends TestCase
{
    use RefreshDatabase;

    private int $orden = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('imagenes');
    }

    private function anuncio(array $cambios = []): Anuncio
    {
        return Anuncio::create(array_merge([
            'titulo' => 'Aviso de prueba',
            'tipo' => 'aviso',
            'activo' => true,
            'color' => 'rosa',
            'orden' => ++$this->orden,
        ], $cambios));
    }

    // El HTML de la landing antes de los anuncios; solo se compara sin diferencias de espacios
    private function normalizar(string $html): string
    {
        return trim(preg_replace('/\s+/', ' ', $html));
    }

    private function assertIgualALaLandingOriginal(string $html): void
    {
        $original = file_get_contents(base_path('tests/Fixtures/landing-sin-anuncios.html'));

        $this->assertSame($this->normalizar($original), $this->normalizar($html));
    }

    // El HTML del carrusel, para revisar solo esa parte
    private function carrusel(string $html): string
    {
        $inicio = strpos($html, 'data-carrusel');
        $this->assertNotFalse($inicio, 'La landing no tiene carrusel.');

        return substr($html, $inicio, strpos($html, 'id="nosotros"') - $inicio);
    }

    // ---------- Sin anuncios la landing no cambia ----------

    public function test_sin_anuncios_la_landing_es_identica_a_la_original(): void
    {
        $respuesta = $this->get('/')->assertOk();

        $this->assertIgualALaLandingOriginal($respuesta->getContent());
        $respuesta->assertDontSee('data-carrusel', false)
            ->assertDontSee('NOVEDADES Y PROMOCIONES')
            ->assertDontSee('carruselAnuncios', false);
    }

    public function test_anuncios_inactivos_vencidos_o_programados_tampoco_cambian_nada(): void
    {
        $ayer = Carbon::now('America/Mexico_City')->subDay()->toDateString();
        $manana = Carbon::now('America/Mexico_City')->addDay()->toDateString();

        foreach (['aviso', 'promocion'] as $tipo) {
            $this->anuncio(['tipo' => $tipo, 'titulo' => "apagado $tipo", 'activo' => false]);
            $this->anuncio(['tipo' => $tipo, 'titulo' => "vencido $tipo", 'fecha_fin' => $ayer]);
            $this->anuncio(['tipo' => $tipo, 'titulo' => "programado $tipo", 'fecha_inicio' => $manana]);
        }

        $respuesta = $this->get('/')->assertOk();

        $this->assertIgualALaLandingOriginal($respuesta->getContent());
        $respuesta->assertDontSee('apagado')->assertDontSee('vencido')->assertDontSee('programado');
    }

    public function test_si_falla_la_consulta_de_anuncios_la_landing_se_ve_como_siempre(): void
    {
        Schema::drop('anuncios');

        $this->assertIgualALaLandingOriginal($this->get('/')->assertOk()->getContent());
    }

    public function test_con_anuncios_el_resto_de_la_landing_sigue_en_su_lugar(): void
    {
        $this->anuncio();

        $this->get('/')->assertOk()
            ->assertSee('animate-marquee', false)
            ->assertSee('CONSULTA GRATIS', false)
            ->assertSee('id="inicio"', false)
            ->assertSee('id="nosotros"', false)
            ->assertSee('id="galeria"', false)
            ->assertSee('id="contacto"', false)
            ->assertDontSee('data-avisos', false); // ya no hay franja fija arriba
    }

    // ---------- Posición ----------

    public function test_el_carrusel_va_despues_de_la_cinta_rosa_y_antes_de_nosotros(): void
    {
        $this->anuncio();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertGreaterThan(strpos($html, 'animate-marquee'), strpos($html, 'data-carrusel'));
        $this->assertLessThan(strpos($html, 'id="nosotros"'), strpos($html, 'data-carrusel'));
        $this->assertLessThan(strpos($html, 'data-carrusel'), strpos($html, 'id="inicio"'));
    }

    // ---------- Una slide o varias ----------

    public function test_con_una_sola_slide_no_hay_controles_ni_avance_automatico(): void
    {
        $this->anuncio(['titulo' => 'Única novedad']);

        $respuesta = $this->get('/')->assertOk()
            ->assertSee('NOVEDADES Y PROMOCIONES')
            ->assertSee('Única novedad');
        $carrusel = $this->carrusel($respuesta->getContent());

        $this->assertStringNotContainsString('x-data', $carrusel);
        $this->assertStringNotContainsString('carruselAnuncios', $carrusel);
        $this->assertStringNotContainsString('aria-label="Anterior"', $carrusel);
        $this->assertStringNotContainsString('aria-label="Siguiente"', $carrusel);
        $this->assertStringNotContainsString('Ir a la slide', $carrusel);
        $this->assertStringNotContainsString('opacity: 0', $carrusel);
    }

    public function test_con_varias_slides_hay_flechas_puntos_y_gestos(): void
    {
        foreach (['Uno', 'Dos', 'Tres'] as $titulo) {
            $this->anuncio(['titulo' => $titulo]);
        }

        $carrusel = $this->carrusel($this->get('/')->assertOk()->getContent());

        $this->assertStringContainsString('x-data="carruselAnuncios(3)"', $carrusel);
        $this->assertStringContainsString('aria-label="Anterior"', $carrusel);
        $this->assertStringContainsString('aria-label="Siguiente"', $carrusel);
        $this->assertSame(3, substr_count($carrusel, 'Ir a la slide'));
        // pausa con mouse y foco, deslizar con el dedo
        $this->assertStringContainsString('@mouseenter="pausado = true"', $carrusel);
        $this->assertStringContainsString('@mouseleave="pausado = false"', $carrusel);
        $this->assertStringContainsString('@touchstart.passive="empezarToque($event)"', $carrusel);
        $this->assertStringContainsString('@touchend="terminarToque($event)"', $carrusel);
    }

    public function test_solo_la_primera_slide_se_ve_antes_de_que_cargue_alpine(): void
    {
        foreach (['Uno', 'Dos', 'Tres'] as $titulo) {
            $this->anuncio(['titulo' => $titulo]);
        }

        $carrusel = $this->carrusel($this->get('/')->assertOk()->getContent());

        $this->assertSame(2, substr_count($carrusel, 'style="opacity: 0; pointer-events: none;"'));
    }

    public function test_las_slides_siguen_el_orden_del_panel_no_el_de_creacion(): void
    {
        $this->anuncio(['titulo' => 'Creado primero', 'orden' => 3]);
        $this->anuncio(['titulo' => 'Creado segundo', 'orden' => 1]);
        $this->anuncio(['titulo' => 'Creado tercero', 'orden' => 2]);

        $carrusel = $this->carrusel($this->get('/')->assertOk()->getContent());

        $this->assertLessThan(strpos($carrusel, 'Creado tercero'), strpos($carrusel, 'Creado segundo'));
        $this->assertLessThan(strpos($carrusel, 'Creado primero'), strpos($carrusel, 'Creado tercero'));
    }

    public function test_maximo_ocho_slides(): void
    {
        foreach (range(1, 11) as $n) {
            $this->anuncio(['titulo' => "Novedad número $n"]);
        }

        $carrusel = $this->carrusel($this->get('/')->assertOk()->getContent());

        $this->assertSame(8, substr_count($carrusel, 'Novedad número'));
        $this->assertStringContainsString('carruselAnuncios(8)', $carrusel);
        $this->assertStringContainsString('Novedad número 8', $carrusel);
        $this->assertStringNotContainsString('Novedad número 9', $carrusel);
    }

    // ---------- Contenido de cada slide ----------

    public function test_cada_slide_dice_si_es_aviso_o_promocion(): void
    {
        $this->anuncio(['titulo' => 'Cerrado el lunes', 'tipo' => 'aviso']);
        $this->anuncio(['titulo' => 'Blanqueamiento 2x1', 'tipo' => 'promocion']);

        $carrusel = $this->carrusel($this->get('/')->assertOk()->getContent());

        $this->assertStringContainsString('AVISO', $carrusel);
        $this->assertStringContainsString('PROMOCIÓN', $carrusel);
    }

    public function test_muestra_titulo_descripcion_emoji_y_vigencia(): void
    {
        $fin = Carbon::now('America/Mexico_City')->addDays(5);
        $this->anuncio([
            'tipo' => 'promocion', 'titulo' => 'Blanqueamiento 2x1', 'descripcion' => 'Trae a un acompañante',
            'emoji' => '✨', 'fecha_fin' => $fin->toDateString(),
        ]);

        $this->get('/')->assertOk()
            ->assertSee('Blanqueamiento 2x1')
            ->assertSee('Trae a un acompañante')
            ->assertSee('✨')
            ->assertSee('Válido hasta el ' . $fin->format('d/m/Y'));
    }

    public function test_la_imagen_sale_con_su_url_y_el_titulo_como_texto_alternativo(): void
    {
        $this->anuncio(['titulo' => 'Con foto', 'imagen' => 'anuncios/01jabcdefghjkmnpqrstvwxyz0.webp']);
        $this->anuncio(['titulo' => 'Sin foto']);

        $carrusel = $this->carrusel($this->get('/')->assertOk()->getContent());

        // Storage::fake cambia el prefijo de la URL; la ruta del archivo y el alt son lo que importa aquí
        $this->assertMatchesRegularExpression(
            '#<img src="/[^"]*anuncios/01jabcdefghjkmnpqrstvwxyz0\.webp" alt="Con foto"#',
            $carrusel
        );
        $this->assertSame(1, substr_count($carrusel, '<img'));
    }

    public function test_una_ruta_de_imagen_invalida_en_la_base_no_genera_imagen(): void
    {
        $this->anuncio(['imagen' => '../../.env']);

        $carrusel = $this->carrusel($this->get('/')->assertOk()->getContent());

        $this->assertStringNotContainsString('<img', $carrusel);
        $this->assertStringNotContainsString('.env', $carrusel);
    }

    public function test_sin_imagen_la_slide_usa_el_emoji_en_un_circulo(): void
    {
        $this->anuncio(['emoji' => '🦷']);

        $this->assertStringContainsString('🦷', $this->carrusel($this->get('/')->assertOk()->getContent()));
    }

    // ---------- Botón ----------

    public function test_el_aviso_sin_boton_no_lo_muestra(): void
    {
        $this->anuncio(['titulo' => 'Solo informativo']);

        $this->assertStringNotContainsString('<a ', $this->carrusel($this->get('/')->assertOk()->getContent()));
    }

    public function test_el_aviso_con_enlace_usa_el_texto_por_defecto(): void
    {
        $this->anuncio(['boton_enlace' => '/login']);

        $carrusel = $this->carrusel($this->get('/')->assertOk()->getContent());

        $this->assertStringContainsString('href="/login"', $carrusel);
        $this->assertStringContainsString('Agendar cita', $carrusel);
    }

    public function test_el_aviso_con_texto_y_sin_enlace_va_a_contacto(): void
    {
        $this->anuncio(['boton_texto' => 'Ver opciones']);

        $carrusel = $this->carrusel($this->get('/')->assertOk()->getContent());

        $this->assertStringContainsString('Ver opciones', $carrusel);
        $this->assertStringContainsString('href="#contacto"', $carrusel);
    }

    public function test_la_promocion_siempre_lleva_boton_con_valores_por_defecto(): void
    {
        $this->anuncio(['tipo' => 'promocion']);

        $carrusel = $this->carrusel($this->get('/')->assertOk()->getContent());

        $this->assertStringContainsString('href="#contacto"', $carrusel);
        $this->assertStringContainsString('Agendar cita', $carrusel);
    }

    public function test_los_enlaces_externos_abren_aparte_y_los_internos_no(): void
    {
        $this->anuncio(['tipo' => 'promocion', 'titulo' => 'Externa', 'boton_enlace' => 'https://wa.me/527531234567']);
        $this->anuncio(['tipo' => 'promocion', 'titulo' => 'Interna', 'boton_enlace' => '/login']);

        $carrusel = $this->carrusel($this->get('/')->assertOk()->getContent());

        $this->assertSame(1, substr_count($carrusel, 'target="_blank" rel="noopener noreferrer"'));
        $this->assertStringContainsString('href="https://wa.me/527531234567"', $carrusel);
    }

    // ---------- Seguridad del contenido ----------

    public function test_el_html_en_los_textos_se_muestra_escapado(): void
    {
        $this->anuncio(['titulo' => '<script>alert("a")</script>', 'descripcion' => '<img src=x onerror=alert(1)>', 'emoji' => '<b>']);

        $respuesta = $this->get('/')->assertOk();

        $respuesta->assertDontSee('<script>alert("a")</script>', false)
            ->assertDontSee('<img src=x', false)
            ->assertSee('&lt;script&gt;alert(&quot;a&quot;)&lt;/script&gt;', false);
    }

    public function test_el_titulo_con_comillas_no_rompe_el_alt_de_la_imagen(): void
    {
        $this->anuncio(['titulo' => 'x" onerror="alert(1)', 'imagen' => 'anuncios/01jabcdefghjkmnpqrstvwxyz0.png']);

        $this->get('/')->assertOk()->assertDontSee('onerror="alert(1)"', false);
    }

    public function test_una_comilla_en_el_enlace_no_puede_romper_el_atributo(): void
    {
        $this->anuncio(['tipo' => 'promocion', 'boton_enlace' => '/x" onclick="alert(1)']);

        $this->get('/')->assertOk()->assertDontSee('onclick="alert(1)"', false);
    }

    public function test_un_color_desconocido_en_la_base_cae_al_rosa(): void
    {
        $this->anuncio(['tipo' => 'promocion', 'color' => 'inventado; x']);

        $this->get('/')->assertOk()
            ->assertDontSee('inventado')
            ->assertSee('bg-[#d75078] hover:bg-[#f285a2]', false);
    }
}
