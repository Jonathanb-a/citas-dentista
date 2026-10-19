<?php

namespace Tests\Feature;

use App\Services\ImagenService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ImagenesDePrueba as Img;
use Tests\TestCase;

/**
 * Redimensionado al guardar. Las pruebas "con GD" se omiten solas si la extensión no está
 * cargada (para correrlas en XAMPP: php -d extension=gd artisan test). Las pruebas de
 * los caminos sin GD corren siempre.
 */
class ImagenRedimensionadoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('imagenes');
    }

    private function exigirGd(string $formato = 'png'): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('La extensión GD no está cargada.');
        }
        if ($formato === 'webp' && !function_exists('imagewebp')) {
            $this->markTestSkipped('GD no tiene soporte WebP.');
        }
    }

    private function guardar(string $nombre, string $contenido, ?ImagenService $servicio = null): string
    {
        return ($servicio ?? app(ImagenService::class))->guardar(Img::archivo($nombre, $contenido), 'anuncios');
    }

    private function bytes(string $ruta): string
    {
        return Storage::disk('imagenes')->get($ruta);
    }

    /** @return array{0:int,1:int,2:int} ancho, alto, tipo */
    private function dimensiones(string $ruta): array
    {
        $info = getimagesizefromstring($this->bytes($ruta));
        $this->assertNotFalse($info, 'La imagen guardada no se puede leer.');

        return [$info[0], $info[1], $info[2]];
    }

    private function pixel(string $ruta, int $x, int $y): array
    {
        $imagen = imagecreatefromstring($this->bytes($ruta));
        $color = imagecolorat($imagen, $x, $y);

        return ['r' => ($color >> 16) & 0xFF, 'g' => ($color >> 8) & 0xFF, 'b' => $color & 0xFF, 'alfa' => ($color >> 24) & 0x7F];
    }

    private function servicioSinGd(): ImagenService
    {
        return new class extends ImagenService {
            protected function gdDisponible(): bool
            {
                return false;
            }
        };
    }

    // ================== Con GD ==================

    public function test_reduce_un_png_ancho_a_1600_y_conserva_la_proporcion(): void
    {
        $this->exigirGd();

        $ruta = $this->guardar('grande.png', Img::conGd(2400, 1200));

        $this->assertStringEndsWith('.png', $ruta);
        $this->assertSame([1600, 800, IMAGETYPE_PNG], $this->dimensiones($ruta));
    }

    public function test_reduce_un_jpg_y_conserva_el_formato(): void
    {
        $this->exigirGd();

        $ruta = $this->guardar('foto.jpg', Img::conGd(4000, 3000, 'jpg'));

        $this->assertStringEndsWith('.jpg', $ruta);
        $this->assertSame([1600, 1200, IMAGETYPE_JPEG], $this->dimensiones($ruta));
    }

    public function test_reduce_un_webp_y_conserva_el_formato(): void
    {
        $this->exigirGd('webp');

        $ruta = $this->guardar('foto.webp', Img::conGd(2000, 1000, 'webp'));

        $this->assertStringEndsWith('.webp', $ruta);
        $this->assertSame([1600, 800, IMAGETYPE_WEBP], $this->dimensiones($ruta));
    }

    public function test_nunca_amplia_una_imagen_chica(): void
    {
        $this->exigirGd();

        $this->assertSame([800, 400, IMAGETYPE_PNG], $this->dimensiones($this->guardar('chica.png', Img::conGd(800, 400))));
    }

    public function test_una_imagen_de_exactamente_1600_no_cambia_de_tamano(): void
    {
        $this->exigirGd();

        $this->assertSame([1600, 900, IMAGETYPE_PNG], $this->dimensiones($this->guardar('justa.png', Img::conGd(1600, 900))));
    }

    public function test_una_imagen_alta_se_limita_por_ancho_no_por_alto(): void
    {
        $this->exigirGd();

        // 1000 de ancho y 4000 de alto: no pasa de 1600 de ancho, así que no se toca el tamaño
        $this->assertSame([1000, 4000, IMAGETYPE_PNG], $this->dimensiones($this->guardar('alta.png', Img::conGd(1000, 4000))));
    }

    public function test_conserva_la_transparencia_de_un_png_al_reducirlo(): void
    {
        $this->exigirGd();

        $contenido = Img::conGd(2000, 1000, 'png', function ($imagen) {
            imagealphablending($imagen, false);
            imagefilledrectangle($imagen, 800, 400, 1200, 600, imagecolorallocatealpha($imagen, 255, 0, 0, 0));
        }, true);

        $ruta = $this->guardar('transparente.png', $contenido);

        $this->assertSame(127, $this->pixel($ruta, 5, 5)['alfa'], 'La esquina debe seguir transparente.');
        $centro = $this->pixel($ruta, 800, 400); // el cuadro rojo, ya reducido
        $this->assertSame(0, $centro['alfa']);
        $this->assertGreaterThan(200, $centro['r']);
    }

    public function test_conserva_la_transparencia_de_un_webp_al_reducirlo(): void
    {
        $this->exigirGd('webp');

        $contenido = Img::conGd(2000, 1000, 'webp', function ($imagen) {
            imagealphablending($imagen, false);
            imagefilledrectangle($imagen, 800, 400, 1200, 600, imagecolorallocatealpha($imagen, 255, 0, 0, 0));
        }, true);

        $ruta = $this->guardar('transparente.webp', $contenido);

        $this->assertSame(127, $this->pixel($ruta, 5, 5)['alfa']);
        $this->assertSame(0, $this->pixel($ruta, 800, 400)['alfa']);
    }

    public function test_conserva_la_transparencia_de_un_png_chico_que_no_se_reduce(): void
    {
        $this->exigirGd();

        $ruta = $this->guardar('chico.png', Img::conGd(100, 100, 'png', null, true));

        $this->assertSame(127, $this->pixel($ruta, 10, 10)['alfa']);
    }

    public function test_quita_los_metadatos_exif_y_los_comentarios(): void
    {
        $this->exigirGd();

        $original = Img::conExif(Img::conGd(300, 200, 'jpg'), 1, 'ubicacion-secreta-19.4,-102.0');
        $this->assertStringContainsString('Exif', $original);
        $this->assertStringContainsString('ubicacion-secreta', $original);

        $guardado = $this->bytes($this->guardar('celular.jpg', $original));

        $this->assertStringNotContainsString('Exif', $guardado);
        $this->assertStringNotContainsString('ubicacion-secreta', $guardado);
    }

    public function test_corrige_la_rotacion_exif_de_las_fotos_de_celular(): void
    {
        $this->exigirGd();
        if (!function_exists('exif_read_data')) {
            $this->markTestSkipped('La extensión exif no está cargada.');
        }

        // Foto horizontal (izquierda roja, derecha azul) marcada "girar 90° a la derecha"
        $ruta = $this->guardar('girada.jpg', Img::conExif(Img::rojoYAzul(400, 200), 6));

        [$ancho, $alto] = $this->dimensiones($ruta);
        $this->assertSame([200, 400], [$ancho, $alto], 'Debe quedar vertical.');

        // Al girar 90° a la derecha, lo que estaba a la izquierda queda arriba
        $arriba = $this->pixel($ruta, 100, 20);
        $abajo = $this->pixel($ruta, 100, 380);
        $this->assertGreaterThan(200, $arriba['r']);
        $this->assertLessThan(60, $arriba['b']);
        $this->assertGreaterThan(200, $abajo['b']);
        $this->assertLessThan(60, $abajo['r']);
    }

    public function test_la_orientacion_180_invierte_la_imagen(): void
    {
        $this->exigirGd();
        if (!function_exists('exif_read_data')) {
            $this->markTestSkipped('La extensión exif no está cargada.');
        }

        $ruta = $this->guardar('boca-abajo.jpg', Img::conExif(Img::rojoYAzul(400, 200), 3));

        $this->assertSame([400, 200], array_slice($this->dimensiones($ruta), 0, 2));
        $this->assertGreaterThan(200, $this->pixel($ruta, 50, 100)['b']);  // el azul pasó a la izquierda
        $this->assertGreaterThan(200, $this->pixel($ruta, 350, 100)['r']);
    }

    public function test_la_orientacion_espejo_voltea_horizontalmente(): void
    {
        $this->exigirGd();
        if (!function_exists('exif_read_data')) {
            $this->markTestSkipped('La extensión exif no está cargada.');
        }

        $ruta = $this->guardar('espejo.jpg', Img::conExif(Img::rojoYAzul(400, 200), 2));

        $this->assertSame([400, 200], array_slice($this->dimensiones($ruta), 0, 2));
        $this->assertGreaterThan(200, $this->pixel($ruta, 50, 100)['b']);
        $this->assertGreaterThan(200, $this->pixel($ruta, 350, 100)['r']);
    }

    public function test_la_rotacion_se_aplica_antes_de_limitar_el_ancho(): void
    {
        $this->exigirGd();
        if (!function_exists('exif_read_data')) {
            $this->markTestSkipped('La extensión exif no está cargada.');
        }

        // 2000 x 3000 con "girar 90°" se ve de 3000 x 2000: debe reducirse a 1600 x 1067
        $this->assertSame([1600, 1067], array_slice($this->dimensiones(
            $this->guardar('girada-grande.jpg', Img::conExif(Img::conGd(2000, 3000, 'jpg'), 6))
        ), 0, 2));
    }

    public function test_si_el_redimensionado_falla_guarda_el_original_y_lo_registra(): void
    {
        $this->exigirGd();
        Log::spy();

        // Pasa la validación (la cabecera es válida) pero GD no puede decodificarla
        $original = Img::png(2000, 10);

        $ruta = $this->guardar('danada.png', $original);

        $this->assertSame($original, $this->bytes($ruta));
        Log::shouldHaveReceived('warning')->once()->withArgs(
            fn ($mensaje) => str_contains($mensaje, 'no se pudo redimensionar') && str_contains($mensaje, 'se guardó el original')
        );
    }

    public function test_sin_memoria_suficiente_guarda_el_original_sin_intentar_decodificar(): void
    {
        $this->exigirGd();
        Log::spy();

        $original = Img::png(4096, 4096);
        $limiteAnterior = ini_get('memory_limit');
        ini_set('memory_limit', '160M'); // 4096x4096x4x2.5 = 160 MB: no alcanza

        try {
            $ruta = $this->guardar('enorme.png', $original);
        } finally {
            ini_set('memory_limit', $limiteAnterior);
        }

        $this->assertSame($original, $this->bytes($ruta));
        Log::shouldHaveReceived('info')->once()->withArgs(fn ($m) => str_contains($m, 'no hay memoria'));
        Log::shouldNotHaveReceived('warning');
    }

    public function test_se_puede_desactivar_con_la_configuracion(): void
    {
        $this->exigirGd();
        config(['imagenes.redimensionar' => false]);

        $original = Img::conGd(2400, 1200);

        $this->assertSame($original, $this->bytes($this->guardar('grande.png', $original)));
    }

    public function test_el_ancho_maximo_sale_de_la_configuracion(): void
    {
        $this->exigirGd();
        config(['imagenes.ancho_max' => 1000]);

        $this->assertSame([1000, 500], array_slice($this->dimensiones($this->guardar('grande.png', Img::conGd(2400, 1200))), 0, 2));
    }

    public function test_el_resultado_pasa_la_validacion_y_cumple_el_patron_de_ruta(): void
    {
        $this->exigirGd();

        $ruta = $this->guardar('grande.png', Img::conGd(2400, 1200));

        $this->assertTrue(app(ImagenService::class)->rutaValida($ruta));
        $this->assertNotNull(app(ImagenService::class)->url($ruta));
    }

    // ================== Sin GD (corren siempre) ==================

    public function test_sin_gd_guarda_la_imagen_tal_cual_y_sin_error(): void
    {
        Log::spy();
        $original = Img::png(2400, 1200);

        $ruta = $this->guardar('grande.png', $original, $this->servicioSinGd());

        $this->assertSame($original, $this->bytes($ruta));
        $this->assertSame([2400, 1200, IMAGETYPE_PNG], $this->dimensiones($ruta));
        Log::shouldNotHaveReceived('warning');
        Log::shouldNotHaveReceived('info');
    }

    public function test_sin_gd_funcionan_los_tres_formatos(): void
    {
        $servicio = $this->servicioSinGd();

        foreach (['a.png' => Img::png(), 'b.jpg' => Img::jpg(), 'c.webp' => Img::webp()] as $nombre => $contenido) {
            $ruta = $this->guardar($nombre, $contenido, $servicio);

            $this->assertSame($contenido, $this->bytes($ruta), $nombre);
        }
    }

    public function test_un_webp_animado_se_guarda_sin_tocar(): void
    {
        $original = Img::webpAnimado(2000, 1000);

        $ruta = $this->guardar('animado.webp', $original);

        $this->assertStringEndsWith('.webp', $ruta);
        $this->assertSame($original, $this->bytes($ruta));
    }

    public function test_un_webp_animado_no_se_toca_aunque_haya_gd(): void
    {
        $this->exigirGd('webp');
        Log::spy();
        $original = Img::webpAnimado(2000, 1000);

        $ruta = $this->guardar('animado.webp', $original);

        $this->assertSame($original, $this->bytes($ruta));
        Log::shouldNotHaveReceived('warning'); // se omite a propósito, no es un fallo
    }

    public function test_desactivado_por_configuracion_guarda_el_original(): void
    {
        config(['imagenes.redimensionar' => false]);
        $original = Img::png(2400, 1200);

        $this->assertSame($original, $this->bytes($this->guardar('grande.png', $original)));
    }

    public function test_la_configuracion_por_defecto(): void
    {
        $this->assertTrue(config('imagenes.redimensionar'));
        $this->assertSame(1600, config('imagenes.ancho_max'));
        $this->assertSame(85, config('imagenes.calidad'));
    }
}
