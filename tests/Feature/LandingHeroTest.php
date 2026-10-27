<?php

namespace Tests\Feature;

use App\Models\Especialidad;
use App\Models\LandingContenido;
use App\Models\Servicio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LandingHeroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('imagenes');
    }

    // Solo el HTML del hero
    private function hero(): string
    {
        $html = $this->get('/')->assertOk()->getContent();
        $inicio = strpos($html, '<section id="inicio"');
        $this->assertNotFalse($inicio, 'La landing no tiene hero.');

        return substr($html, $inicio, strpos($html, '</section>', $inicio) - $inicio);
    }

    public function test_con_servicios_tiene_los_dos_botones_con_su_destino(): void
    {
        $especialidad = Especialidad::create(['nombre' => 'Odontología General', 'activo' => true]);
        Servicio::create(['especialidad_id' => $especialidad->id, 'nombre' => 'Limpieza dental', 'duracion_minutos' => 45, 'activo' => true]);

        $hero = $this->hero();

        $this->assertStringContainsString('href="' . route('paciente.citas.create') . '"', $hero);
        $this->assertStringContainsString('href="#tratamientos"', $hero);
        $this->assertStringContainsString('Ver tratamientos', $hero);
        // Texto blanco sobre los tonos AA del proyecto
        $this->assertSame(2, substr_count($hero, 'bg-[#c03a63]'));
        $this->assertSame(2, substr_count($hero, 'hover:bg-[#a13153]'));
    }

    public function test_sin_servicios_no_muestra_ver_tratamientos_para_no_dejar_un_enlace_roto(): void
    {
        $especialidad = Especialidad::create(['nombre' => 'Odontología General', 'activo' => true]);
        Servicio::create(['especialidad_id' => $especialidad->id, 'nombre' => 'Pausado', 'duracion_minutos' => 45, 'activo' => false]);

        $hero = $this->hero();

        $this->assertStringNotContainsString('href="#tratamientos"', $hero);
        $this->assertStringNotContainsString('Ver tratamientos', $hero);
        $this->assertStringContainsString('href="' . route('paciente.citas.create') . '"', $hero);
        $this->assertSame(1, substr_count($hero, 'bg-[#c03a63]'));
    }

    public function test_usa_los_campos_editables_del_hero(): void
    {
        foreach ([
            'hero.etiqueta' => 'FRASE DE PRUEBA',
            'hero.titulo' => "Primera línea\nSegunda línea",
            'hero.descripcion' => 'Descripción de prueba.',
            'hero.boton' => 'RESERVAR',
        ] as $clave => $valor) {
            LandingContenido::create(['clave' => $clave, 'valor' => $valor]);
        }

        $hero = $this->hero();

        $this->assertStringContainsString('FRASE DE PRUEBA', $hero);
        $this->assertStringContainsString('Primera línea <br> Segunda línea', $hero);
        $this->assertStringContainsString('Descripción de prueba.', $hero);
        $this->assertStringContainsString('RESERVAR', $hero);
    }

    public function test_muestra_la_imagen_y_tiene_la_ilustracion_de_respaldo(): void
    {
        $hero = $this->hero();

        $this->assertStringContainsString('src="' . asset('img/image 1.png') . '"', $hero);
        $this->assertStringContainsString('x-on:error="rota = true"', $hero);
        // La ilustración empieza oculta: solo aparece si la imagen no carga
        $this->assertMatchesRegularExpression('/x-show="rota" style="display: none"[^>]*>\s*<svg class="hero-flotar/', $hero);
    }

    public function test_la_imagen_del_hero_carga_primero_sin_mover_el_contenido(): void
    {
        $hero = $this->hero();
        preg_match('/<img[^>]*x-ref="imagen"[^>]*>/', $hero, $img);
        $this->assertNotEmpty($img);

        $this->assertStringContainsString('fetchpriority="high"', $img[0]);
        $this->assertStringContainsString('width="579" height="423"', $img[0]);
        $this->assertStringContainsString('aspect-[579/423]', $img[0]);
        $this->assertStringContainsString('bg-[#fde7ed]', $img[0]); // fondo mientras llega
        $this->assertStringNotContainsString('loading="lazy"', $img[0]);
    }

    public function test_el_hero_entra_con_fade_up_que_respeta_reducir_movimiento(): void
    {
        $hero = $this->hero();
        $this->assertSame(5, substr_count($hero, 'class="registro-entrada '));
        $this->assertStringNotContainsString('registro-entrada-lateral', $hero);

        // La animación solo existe dentro de prefers-reduced-motion: no-preference
        $css = preg_replace('/\s+/', ' ', file_get_contents(resource_path('css/app.css')));
        $this->assertMatchesRegularExpression('/@media \(prefers-reduced-motion: no-preference\) \{ \.registro-entrada \{ animation: registro-entrada/', $css);
        $this->assertSame(1, preg_match_all('/\.registro-entrada \{/', $css));
    }
}
