<?php

namespace Tests\Feature;

use App\Models\Anuncio;
use App\Models\Especialidad;
use App\Models\Servicio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Sección #tratamientos de la landing: servicios activos agrupados por
 * especialidad activa, en lugar de la cinta rosa fija.
 */
class LandingTratamientosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('imagenes');
    }

    private function especialidad(string $nombre, bool $activa = true, ?string $descripcion = null): Especialidad
    {
        return Especialidad::create(['nombre' => $nombre, 'descripcion' => $descripcion, 'activo' => $activa]);
    }

    private function servicio(Especialidad $especialidad, string $nombre, int $minutos = 45, bool $activo = true, ?string $descripcion = null): Servicio
    {
        return Servicio::create([
            'especialidad_id' => $especialidad->id,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'duracion_minutos' => $minutos,
            'activo' => $activo,
        ]);
    }

    // Solo el HTML de la sección
    private function seccion(): string
    {
        $html = $this->get('/')->assertOk()->getContent();
        $inicio = strpos($html, '<section id="tratamientos"');
        $this->assertNotFalse($inicio, 'La landing no tiene la sección de tratamientos.');

        return substr($html, $inicio, strpos($html, '</section>', $inicio) - $inicio);
    }

    public function test_sin_servicios_no_hay_seccion_ni_cinta_rosa(): void
    {
        $this->get('/')->assertOk()
            ->assertDontSee('id="tratamientos"', false)
            ->assertDontSee('animate-marquee', false)
            ->assertDontSee('CONSULTA GRATIS');
    }

    public function test_muestra_los_servicios_activos_agrupados_por_especialidad(): void
    {
        $general = $this->especialidad('Odontología General', descripcion: 'Tratamientos preventivos.');
        $ortodoncia = $this->especialidad('Ortodoncia');
        $this->servicio($general, 'Limpieza dental', 45, descripcion: 'Limpieza profunda y eliminación de sarro.');
        $this->servicio($general, 'Extracción', 60);
        $this->servicio($ortodoncia, 'Brackets', 90);

        $seccion = $this->seccion();

        foreach (['Odontología General', 'Tratamientos preventivos.', 'Ortodoncia', 'Limpieza dental',
                  'Limpieza profunda y eliminación de sarro.', 'Extracción', 'Brackets'] as $texto) {
            $this->assertStringContainsString(e($texto), $seccion);
        }

        $this->assertStringContainsString('Duración aprox.: 45 min', $seccion);
        $this->assertMatchesRegularExpression('/Duración aprox\.: 1 h\s*</', $seccion);
        $this->assertStringContainsString('Duración aprox.: 1 h 30 min', $seccion);

        // Cada servicio debajo de su especialidad
        $this->assertLessThan(strpos($seccion, 'Brackets'), strpos($seccion, 'Ortodoncia'));
        $this->assertGreaterThan(strpos($seccion, 'Odontología General'), strpos($seccion, 'Limpieza dental'));
        $this->assertLessThan(strpos($seccion, 'Ortodoncia'), strpos($seccion, 'Limpieza dental'));
    }

    public function test_no_muestra_servicios_ni_especialidades_inactivos(): void
    {
        $activa = $this->especialidad('Odontología General');
        $this->servicio($activa, 'Limpieza dental');
        $this->servicio($activa, 'Servicio pausado', activo: false);

        $inactiva = $this->especialidad('Especialidad cerrada', activa: false);
        $this->servicio($inactiva, 'Servicio de especialidad cerrada');

        $this->especialidad('Especialidad sin servicios activos');

        $seccion = $this->seccion();

        $this->assertStringContainsString('Limpieza dental', $seccion);
        $this->assertStringNotContainsString('Servicio pausado', $seccion);
        $this->assertStringNotContainsString('Especialidad cerrada', $seccion);
        $this->assertStringNotContainsString('Servicio de especialidad cerrada', $seccion);
        $this->assertStringNotContainsString('Especialidad sin servicios activos', $seccion);
    }

    public function test_si_solo_hay_servicios_inactivos_no_hay_seccion(): void
    {
        $this->servicio($this->especialidad('Odontología General'), 'Limpieza dental', activo: false);

        $this->get('/')->assertOk()->assertDontSee('id="tratamientos"', false);
    }

    public function test_no_muestra_precio_y_escapa_los_textos(): void
    {
        $this->servicio($this->especialidad('Odontología General'), '<script>alert(1)</script>', descripcion: '<b>negrita</b>');

        $seccion = $this->seccion();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $seccion);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $seccion);
        $this->assertStringContainsString('&lt;b&gt;negrita&lt;/b&gt;', $seccion);
        $this->assertStringNotContainsString('$', $seccion);
        $this->assertStringNotContainsStringIgnoringCase('precio', $seccion);
    }

    public function test_tarjetas_con_icono_aparicion_escalonada_y_hover_solo_con_transform(): void
    {
        $general = $this->especialidad('Odontología General');
        foreach (['Uno', 'Dos', 'Tres', 'Cuatro'] as $nombre) {
            $this->servicio($general, $nombre);
        }

        $seccion = $this->seccion();

        $this->assertSame(4, substr_count($seccion, '<li data-aparecer'));
        foreach (['0ms', '100ms', '200ms'] as $retraso) {
            $this->assertStringContainsString('--retraso: ' . $retraso, $seccion);
        }
        $this->assertSame(4, substr_count($seccion, 'motion-safe:hover:-translate-y-1'));
        $this->assertStringNotContainsString('hover:shadow', $seccion);
        $this->assertGreaterThanOrEqual(4, substr_count($seccion, '<svg'));
    }

    public function test_va_donde_estaba_la_cinta_despues_del_hero_y_antes_del_carrusel(): void
    {
        $this->servicio($this->especialidad('Odontología General'), 'Limpieza dental');
        Anuncio::create(['titulo' => 'Aviso de prueba', 'tipo' => 'aviso', 'activo' => true, 'color' => 'rosa', 'orden' => 1]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertLessThan(strpos($html, 'id="tratamientos"'), strpos($html, 'id="inicio"'));
        $this->assertLessThan(strpos($html, 'data-carrusel'), strpos($html, 'id="tratamientos"'));
        $this->assertLessThan(strpos($html, 'id="nosotros"'), strpos($html, 'data-carrusel'));
    }

    public function test_si_falla_la_consulta_la_landing_se_ve_sin_la_seccion_y_sin_500(): void
    {
        $this->servicio($this->especialidad('Odontología General'), 'Limpieza dental');
        Schema::drop('servicios');

        $this->get('/')->assertOk()
            ->assertDontSee('id="tratamientos"', false)
            ->assertSee('id="inicio"', false)
            ->assertSee('id="contacto"', false);
    }
}
