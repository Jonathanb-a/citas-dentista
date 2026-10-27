<?php

namespace Tests\Feature;

use App\Models\Especialidad;
use App\Models\LandingContenido;
use App\Models\LandingItem;
use App\Models\Servicio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Orden de las secciones de la landing, franja para agendar, contacto y los enlaces
 * del navbar y del pie (solo a secciones que se muestran).
 */
class LandingEstructuraTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('imagenes');
    }

    private function conTratamientosYOpiniones(): void
    {
        $especialidad = Especialidad::create(['nombre' => 'Odontología General', 'activo' => true]);
        Servicio::create(['especialidad_id' => $especialidad->id, 'nombre' => 'Limpieza dental', 'duracion_minutos' => 45, 'activo' => true]);
        LandingItem::create(['coleccion' => 'testimonios', 'orden' => 1, 'datos' => [
            'nombre' => 'María G.', 'texto' => 'Muy buena atención.', 'calificacion' => 5, 'autorizado' => true,
        ]]);
    }

    // Anclas a las que apunta un bloque de HTML (navbar o pie)
    private function anclas(string $html): array
    {
        preg_match_all('/href="(#[a-z]+)"/', $html, $m);

        return array_values(array_unique($m[1]));
    }

    private function navbar(string $html): string
    {
        return substr($html, 0, strpos($html, '<section id="inicio"'));
    }

    private function pie(string $html): string
    {
        return substr($html, strpos($html, '<footer'));
    }

    public function test_las_secciones_van_en_orden(): void
    {
        $this->conTratamientosYOpiniones();

        $html = $this->get('/')->assertOk()->getContent();
        $orden = ['id="inicio"', 'id="tratamientos"', 'id="nosotros"', 'id="certificados"', 'id="galeria"', 'id="testimonios"', 'id="agendar"', 'id="contacto"', '<footer'];

        $posiciones = array_map(fn ($marca) => strpos($html, $marca), $orden);
        $this->assertNotContains(false, $posiciones);
        $ordenadas = $posiciones;
        sort($ordenadas);
        $this->assertSame($ordenadas, $posiciones);
    }

    public function test_la_franja_para_agendar_tiene_texto_editable_y_dos_botones(): void
    {
        LandingContenido::create(['clave' => 'agendar.titulo', 'valor' => "Agenda hoy\nsin filas"]);
        LandingContenido::create(['clave' => 'agendar.texto', 'valor' => 'Texto de prueba de la franja.']);

        $html = $this->get('/')->assertOk()->getContent();
        $inicio = strpos($html, '<section id="agendar"');
        $franja = substr($html, $inicio, strpos($html, '</section>', $inicio) - $inicio);

        $this->assertStringContainsString('Agenda hoy <br> sin filas', $franja);
        $this->assertStringContainsString('Texto de prueba de la franja.', $franja);
        $this->assertStringContainsString('href="' . route('paciente.citas.create') . '"', $franja);
        $this->assertStringContainsString('href="/register"', $franja);
    }

    public function test_contacto_usa_sus_campos(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $inicio = strpos($html, '<section id="contacto"');
        $contacto = substr($html, $inicio, strpos($html, '</section>', $inicio) - $inicio);

        $this->assertStringContainsString('href="tel:+529991231234"', $contacto);
        $this->assertStringContainsString('Llamar al 999 123 1234', $contacto);
        $this->assertStringContainsString('href="https://wa.me/529991231234"', $contacto);
        $this->assertStringContainsString('Horario: 10am - 7pm', $contacto);
        $this->assertStringContainsString('Plaza Marsella, Local 1', $contacto);
        $this->assertStringContainsString('title="Mapa de ubicación del consultorio"', $contacto);
    }

    public function test_sin_tratamientos_ni_opiniones_el_menu_no_enlaza_a_esas_secciones(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        foreach ([$this->navbar($html), $this->pie($html)] as $bloque) {
            $this->assertNotContains('#tratamientos', $this->anclas($bloque));
            $this->assertNotContains('#testimonios', $this->anclas($bloque));
            foreach (['#inicio', '#nosotros', '#galeria', '#contacto'] as $ancla) {
                $this->assertContains($ancla, $this->anclas($bloque));
            }
        }
    }

    public function test_con_tratamientos_y_opiniones_el_menu_las_incluye(): void
    {
        $this->conTratamientosYOpiniones();

        $html = $this->get('/')->assertOk()->getContent();

        foreach ([$this->navbar($html), $this->pie($html)] as $bloque) {
            $this->assertContains('#tratamientos', $this->anclas($bloque));
            $this->assertContains('#testimonios', $this->anclas($bloque));
        }
    }

    public function test_ningun_enlace_interno_de_la_landing_queda_roto(): void
    {
        // Navbar, hero, secciones, franja para agendar y pie; sin datos y con datos
        foreach ([false, true] as $conDatos) {
            if ($conDatos) {
                $this->conTratamientosYOpiniones();
            }

            $html = $this->get('/')->assertOk()->getContent();
            $anclas = $this->anclas($html);
            $this->assertNotEmpty($anclas);

            foreach ($anclas as $ancla) {
                $this->assertMatchesRegularExpression('/ id="' . substr($ancla, 1) . '"/', $html, "$ancla no lleva a ninguna sección");
            }
        }
    }

    public function test_cada_seccion_tiene_margen_para_el_navbar_fijo(): void
    {
        $this->conTratamientosYOpiniones();
        \App\Models\Anuncio::create(['titulo' => 'Aviso', 'tipo' => 'aviso', 'activo' => true, 'color' => 'rosa', 'orden' => 1]);

        $html = $this->get('/')->assertOk()->getContent();
        preg_match_all('/<section id="([a-z]+)"[^>]*class="([^"]*)"/', $html, $secciones, PREG_SET_ORDER);

        $this->assertCount(8, $secciones); // inicio, tratamientos, novedades, nosotros, galeria, testimonios, agendar, contacto
        foreach ($secciones as [, $id, $clases]) {
            $this->assertStringContainsString('scroll-mt-[82px] md:scroll-mt-[100px]', $clases, "#$id no tiene scroll-mt");
        }
    }

    public function test_el_desplazamiento_suave_respeta_reducir_movimiento(): void
    {
        $css = preg_replace('/\s+/', ' ', file_get_contents(resource_path('css/app.css')));

        $this->assertMatchesRegularExpression('/@media \(prefers-reduced-motion: no-preference\) \{ html \{ scroll-behavior: smooth; \} \}/', $css);
        $this->assertSame(1, substr_count($css, 'scroll-behavior'), 'scroll-behavior solo dentro del @media');
    }

    public function test_el_menu_movil_se_cierra_y_despues_se_desplaza(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $menu = substr($html, strpos($html, 'id="menu-movil"'));
        $menu = substr($menu, 0, strpos($menu, '</div>'));

        foreach ($this->anclas($menu) as $ancla) {
            $this->assertStringContainsString("@click.prevent=\"irA('$ancla')\"", $menu);
        }
        $this->assertStringContainsString('this.abierto = false;', $html);
        $this->assertStringContainsString('scrollIntoView', $html);
    }
}
