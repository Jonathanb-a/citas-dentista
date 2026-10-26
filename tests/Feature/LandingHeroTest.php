<?php

namespace Tests\Feature;

use App\Models\LandingContenido;
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

    public function test_tiene_los_dos_botones_con_su_destino(): void
    {
        $hero = $this->hero();

        $this->assertStringContainsString('href="' . route('paciente.citas.create') . '"', $hero);
        $this->assertStringContainsString('href="#tratamientos"', $hero);
        $this->assertStringContainsString('Ver tratamientos', $hero);
        // Texto blanco sobre los tonos AA del proyecto
        $this->assertSame(2, substr_count($hero, 'bg-[#c03a63]'));
        $this->assertSame(2, substr_count($hero, 'hover:bg-[#a13153]'));
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
}
