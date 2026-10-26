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

    public function test_cada_enlace_del_menu_y_del_pie_tiene_su_seccion(): void
    {
        foreach ([false, true] as $conDatos) {
            if ($conDatos) {
                $this->conTratamientosYOpiniones();
            }

            $html = $this->get('/')->assertOk()->getContent();

            foreach (array_merge($this->anclas($this->navbar($html)), $this->anclas($this->pie($html))) as $ancla) {
                $this->assertMatchesRegularExpression('/ id="' . substr($ancla, 1) . '"/', $html, "$ancla no lleva a ninguna sección");
            }
        }
    }
}
