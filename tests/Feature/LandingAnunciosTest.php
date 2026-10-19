<?php

namespace Tests\Feature;

use App\Models\Anuncio;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LandingAnunciosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function anuncio(array $cambios = []): Anuncio
    {
        return Anuncio::create(array_merge([
            'titulo' => 'Aviso de prueba',
            'tipo' => 'aviso',
            'activo' => true,
            'color' => 'rosa',
        ], $cambios));
    }

    // El HTML de la landing antes de este módulo; solo se compara sin diferencias de espacios
    private function normalizar(string $html): string
    {
        return trim(preg_replace('/\s+/', ' ', $html));
    }

    private function assertIgualALaLandingOriginal(string $html): void
    {
        $original = file_get_contents(base_path('tests/Fixtures/landing-sin-anuncios.html'));

        $this->assertSame($this->normalizar($original), $this->normalizar($html));
    }

    // ---------- Sin anuncios la landing no cambia ----------

    public function test_sin_anuncios_la_landing_es_identica_a_la_original(): void
    {
        $respuesta = $this->get('/')->assertOk();

        $this->assertIgualALaLandingOriginal($respuesta->getContent());
        $respuesta->assertDontSee('data-avisos', false)
            ->assertDontSee('data-promociones', false)
            ->assertDontSee('PROMOCIONES');
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

        $respuesta = $this->get('/')->assertOk();

        $this->assertIgualALaLandingOriginal($respuesta->getContent());
    }

    public function test_la_cinta_de_servicios_original_sigue_igual_con_anuncios(): void
    {
        $this->anuncio();
        $this->anuncio(['tipo' => 'promocion', 'titulo' => 'Promo']);

        $this->get('/')->assertOk()
            ->assertSee('animate-marquee', false)
            ->assertSee('CONSULTA GRATIS', false)
            ->assertSee('id="inicio"', false)
            ->assertSee('id="nosotros"', false)
            ->assertSee('id="contacto"', false);
    }

    // ---------- Avisos ----------

    public function test_un_aviso_vigente_sale_en_la_franja_de_arriba_antes_del_hero(): void
    {
        $this->anuncio(['titulo' => 'Cerrado el lunes por capacitación', 'descripcion' => 'Reanudamos el martes', 'emoji' => '📢']);

        $respuesta = $this->get('/')->assertOk()
            ->assertSee('data-avisos', false)
            ->assertSee('Cerrado el lunes por capacitación')
            ->assertSee('Reanudamos el martes')
            ->assertSee('📢')
            ->assertDontSee('data-promociones', false);

        $html = $respuesta->getContent();
        $this->assertLessThan(strpos($html, 'id="inicio"'), strpos($html, 'data-avisos'));
    }

    public function test_el_aviso_sin_boton_no_lo_muestra(): void
    {
        $this->anuncio(['titulo' => 'Solo informativo']);

        $html = $this->get('/')->assertOk()->getContent();
        $franja = substr($html, strpos($html, 'data-avisos'), strpos($html, 'id="inicio"') - strpos($html, 'data-avisos'));

        $this->assertStringNotContainsString('<a ', $franja);
    }

    public function test_el_aviso_con_enlace_usa_el_texto_por_defecto(): void
    {
        $this->anuncio(['boton_enlace' => '/login']);

        $this->get('/')->assertOk()
            ->assertSee('href="/login"', false)
            ->assertSee('Agendar cita');
    }

    public function test_el_aviso_con_texto_y_sin_enlace_va_a_contacto(): void
    {
        $this->anuncio(['boton_texto' => 'Ver opciones']);

        $html = $this->get('/')->assertOk()->assertSee('Ver opciones')->getContent();
        $franja = substr($html, strpos($html, 'data-avisos'), strpos($html, 'id="inicio"') - strpos($html, 'data-avisos'));

        $this->assertStringContainsString('href="#contacto"', $franja);
    }

    public function test_maximo_tres_avisos_los_mas_recientes_primero(): void
    {
        foreach (range(1, 5) as $n) {
            $this->anuncio(['titulo' => "Aviso número $n", 'created_at' => now()->addMinutes($n)]);
        }

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Aviso número 5', $html);
        $this->assertStringContainsString('Aviso número 4', $html);
        $this->assertStringContainsString('Aviso número 3', $html);
        $this->assertStringNotContainsString('Aviso número 2', $html);
        $this->assertLessThan(strpos($html, 'Aviso número 4'), strpos($html, 'Aviso número 5'));
    }

    // ---------- Promociones ----------

    public function test_una_promocion_vigente_sale_en_su_seccion_despues_de_la_cinta(): void
    {
        $this->anuncio([
            'tipo' => 'promocion',
            'titulo' => 'Blanqueamiento 2x1',
            'descripcion' => 'Trae a un acompañante',
            'emoji' => '✨',
            'fecha_fin' => Carbon::now('America/Mexico_City')->addDays(5)->toDateString(),
        ]);

        $respuesta = $this->get('/')->assertOk()
            ->assertSee('id="promociones"', false)
            ->assertSee('PROMOCIONES')
            ->assertSee('Blanqueamiento 2x1')
            ->assertSee('Trae a un acompañante')
            ->assertSee('✨')
            ->assertSee('Válida hasta el ' . Carbon::now('America/Mexico_City')->addDays(5)->format('d/m/Y'))
            ->assertDontSee('data-avisos', false);

        $html = $respuesta->getContent();
        $this->assertGreaterThan(strpos($html, 'CONSULTA GRATIS'), strpos($html, 'id="promociones"'));
        $this->assertLessThan(strpos($html, 'id="nosotros"'), strpos($html, 'id="promociones"'));
    }

    public function test_la_promocion_siempre_lleva_boton_con_valores_por_defecto(): void
    {
        $this->anuncio(['tipo' => 'promocion', 'titulo' => 'Promo sin botón configurado']);

        $html = $this->get('/')->assertOk()->getContent();
        $seccion = substr($html, strpos($html, 'id="promociones"'), strpos($html, 'id="nosotros"') - strpos($html, 'id="promociones"'));

        $this->assertStringContainsString('href="#contacto"', $seccion);
        $this->assertStringContainsString('Agendar cita', $seccion);
        $this->assertStringNotContainsString('Válida hasta', $seccion);
    }

    public function test_los_enlaces_externos_abren_aparte_y_sin_opener(): void
    {
        $this->anuncio(['tipo' => 'promocion', 'boton_texto' => 'Escríbenos', 'boton_enlace' => 'https://wa.me/527531234567']);

        $this->get('/')->assertOk()
            ->assertSee('href="https://wa.me/527531234567"', false)
            ->assertSee('target="_blank" rel="noopener noreferrer"', false)
            ->assertSee('Escríbenos');
    }

    public function test_los_enlaces_internos_no_abren_pestana_nueva(): void
    {
        $this->anuncio(['tipo' => 'promocion', 'boton_enlace' => '/login']);

        $html = $this->get('/')->assertOk()->getContent();
        $seccion = substr($html, strpos($html, 'id="promociones"'), strpos($html, 'id="nosotros"') - strpos($html, 'id="promociones"'));

        $this->assertStringNotContainsString('target="_blank"', $seccion);
    }

    public function test_maximo_seis_promociones(): void
    {
        foreach (range(1, 8) as $n) {
            $this->anuncio(['tipo' => 'promocion', 'titulo' => "Promo número $n", 'created_at' => now()->addMinutes($n)]);
        }

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(6, substr_count($html, 'Promo número'));
        $this->assertStringContainsString('Promo número 8', $html);
        $this->assertStringNotContainsString('Promo número 2', $html);
    }

    public function test_aviso_y_promocion_conviven(): void
    {
        $this->anuncio(['titulo' => 'Aviso A']);
        $this->anuncio(['tipo' => 'promocion', 'titulo' => 'Promo B']);

        $this->get('/')->assertOk()
            ->assertSee('data-avisos', false)
            ->assertSee('data-promociones', false)
            ->assertSee('Aviso A')
            ->assertSee('Promo B');
    }

    // ---------- Seguridad del contenido ----------

    public function test_el_html_en_los_textos_se_muestra_escapado(): void
    {
        $this->anuncio(['titulo' => '<script>alert("a")</script>', 'descripcion' => '<img src=x onerror=alert(1)>', 'emoji' => '<b>']);
        $this->anuncio(['tipo' => 'promocion', 'titulo' => '<script>alert("p")</script>', 'descripcion' => '<a href="http://evil">x</a>']);

        $respuesta = $this->get('/')->assertOk();

        $respuesta->assertDontSee('<script>alert("a")</script>', false)
            ->assertDontSee('<script>alert("p")</script>', false)
            ->assertDontSee('<img src=x', false)
            ->assertDontSee('<a href="http://evil">', false)
            ->assertSee('&lt;script&gt;alert(&quot;a&quot;)&lt;/script&gt;', false);
    }

    public function test_una_comilla_en_el_enlace_no_puede_romper_el_atributo(): void
    {
        // Aunque el formulario lo rechaza, la vista escapa por si llegara a la base
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
