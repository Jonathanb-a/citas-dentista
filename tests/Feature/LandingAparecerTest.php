<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Las secciones de abajo de la landing entran al hacer scroll (data-aparecer,
 * resources/js/aparecer.js). El hero no: se ve al cargar.
 */
class LandingAparecerTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_secciones_de_abajo_aparecen_al_hacer_scroll_y_el_hero_no(): void
    {
        $this->withoutVite();
        Storage::fake('imagenes');

        $html = $this->get('/')->assertOk()->getContent();

        foreach (['nosotros', 'galeria', 'certificados', 'contacto'] as $seccion) {
            $this->assertStringContainsString('<section id="' . $seccion . '" data-aparecer', $html);
        }

        $this->assertStringNotContainsString('<section id="inicio" data-aparecer', $html);
        // Vacío en el HTML: sin JavaScript nada se esconde
        $this->assertStringNotContainsString('data-aparecer="oculto"', $html);
    }
}
