<?php

namespace Tests\Feature;

use App\Services\ImagenService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ImagenesDePrueba as Img;
use Tests\TestCase;

class ImagenServiceTest extends TestCase
{
    private ImagenService $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('imagenes');
        $this->servicio = app(ImagenService::class);
    }

    private function disco()
    {
        return Storage::disk('imagenes');
    }

    // ---------- Guardar ----------

    public function test_guarda_en_la_carpeta_con_nombre_aleatorio_y_extension_real(): void
    {
        $ruta = $this->servicio->guardar(Img::pngSubido('mi foto (1).png'), 'anuncios');

        $this->assertMatchesRegularExpression('#^anuncios/[a-z0-9]{26}\.png$#', $ruta);
        $this->disco()->assertExists($ruta);
        $this->assertStringNotContainsString('foto', $ruta);
    }

    public function test_dos_subidas_del_mismo_archivo_no_chocan(): void
    {
        $a = $this->servicio->guardar(Img::pngSubido(), 'anuncios');
        $b = $this->servicio->guardar(Img::pngSubido(), 'anuncios');

        $this->assertNotSame($a, $b);
        $this->disco()->assertExists($a);
        $this->disco()->assertExists($b);
    }

    #[DataProvider('formatosValidos')]
    public function test_acepta_jpg_png_y_webp(string $nombre, string $contenido, string $extension): void
    {
        $ruta = $this->servicio->guardar(Img::archivo($nombre, $contenido), 'galeria');

        $this->assertStringEndsWith('.' . $extension, $ruta);
        $this->disco()->assertExists($ruta);
    }

    public static function formatosValidos(): array
    {
        return [
            'jpg' => ['a.jpg', Img::jpg(), 'jpg'],
            'png' => ['a.png', Img::png(), 'png'],
            'webp' => ['a.webp', Img::webp(), 'webp'],
        ];
    }

    public function test_la_extension_sale_del_contenido_no_del_nombre(): void
    {
        // Un PNG real que se subió con nombre .jpg se guarda como .png
        $ruta = $this->servicio->guardar(Img::archivo('trampa.jpg', Img::png()), 'anuncios');

        $this->assertStringEndsWith('.png', $ruta);
    }

    public function test_acepta_subcarpetas_pero_rechaza_carpetas_raras(): void
    {
        $ruta = $this->servicio->guardar(Img::pngSubido(), 'landing/hero');
        $this->assertStringStartsWith('landing/hero/', $ruta);

        foreach (['../fuera', 'a b', 'Mayusculas', '/absoluta', ''] as $carpeta) {
            try {
                $this->servicio->guardar(Img::pngSubido(), $carpeta);
                $this->fail("Aceptó la carpeta '$carpeta'");
            } catch (\InvalidArgumentException $e) {
                $this->assertTrue(true);
            }
        }
    }

    public static function archivosInvalidos(): array
    {
        return [
            'texto renombrado a jpg' => ['falso.jpg', 'esto no es una imagen'],
            'svg' => ['logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'],
            'svg disfrazado de png' => ['logo.png', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'],
            'gif' => ['animado.gif', base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7')],
            'php disfrazado' => ['shell.jpg', '<?php system($_GET["c"]); ?>'],
            'html' => ['pagina.png', '<html><body>hola</body></html>'],
            'png truncado' => ['roto.png', "\x89PNG\r\n\x1a\n" . 'basura'],
            'vacio' => ['vacio.png', ''],
        ];
    }

    #[DataProvider('archivosInvalidos')]
    public function test_rechaza_lo_que_no_es_una_imagen_real(string $nombre, string $contenido): void
    {
        try {
            $this->servicio->guardar(Img::archivo($nombre, $contenido), 'anuncios');
            $this->fail('Aceptó un archivo que no es una imagen válida.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('imagen', $e->errors());
        }

        $this->assertSame([], $this->disco()->allFiles());
    }

    public function test_rechaza_imagenes_de_mas_de_cinco_megas(): void
    {
        $this->expectException(ValidationException::class);

        try {
            $this->servicio->guardar(Img::pngSubido('pesada.png', 1, 1, 5 * 1024 * 1024 + 2048), 'anuncios');
        } finally {
            $this->assertSame([], $this->disco()->allFiles());
        }
    }

    public function test_acepta_una_imagen_justo_debajo_del_limite(): void
    {
        $ruta = $this->servicio->guardar(Img::pngSubido('casi.png', 1, 1, 5 * 1024 * 1024 - 2048), 'anuncios');

        $this->disco()->assertExists($ruta);
    }

    public function test_rechaza_imagenes_con_demasiados_pixeles(): void
    {
        foreach ([[4097, 10], [10, 4097]] as [$ancho, $alto]) {
            try {
                $this->servicio->guardar(Img::pngSubido('enorme.png', $ancho, $alto), 'anuncios');
                $this->fail("Aceptó {$ancho}x{$alto}");
            } catch (ValidationException $e) {
                $this->assertStringContainsString('demasiado grande', $e->errors()['imagen'][0]);
            }
        }

        $this->disco()->assertExists($this->servicio->guardar(Img::pngSubido('limite.png', 4096, 4096), 'anuncios'));
    }

    public function test_el_limite_de_peso_se_puede_cambiar_por_llamada(): void
    {
        $this->expectException(ValidationException::class);

        $this->servicio->guardar(Img::pngSubido('a.png', 1, 1, 3000), 'anuncios', 1);
    }

    // ---------- Eliminar y reemplazar ----------

    public function test_elimina_el_archivo(): void
    {
        $ruta = $this->servicio->guardar(Img::pngSubido(), 'anuncios');

        $this->assertTrue($this->servicio->eliminar($ruta));
        $this->disco()->assertMissing($ruta);
    }

    public function test_eliminar_algo_que_no_existe_o_esta_vacio_no_truena(): void
    {
        $this->assertFalse($this->servicio->eliminar(null));
        $this->assertFalse($this->servicio->eliminar(''));
        $this->servicio->eliminar('anuncios/noexiste0000000000000000.png');
        $this->assertTrue(true);
    }

    public function test_no_borra_rutas_fuera_del_formato_esperado(): void
    {
        $this->disco()->put('anuncios/importante.txt', 'x');
        $this->disco()->put('secreto.png', 'x');
        Log::spy();

        foreach (['../.env', 'anuncios/../secreto.png', '/anuncios/a.png', 'anuncios/importante.txt', 'secreto.png', 'anuncios/a b.png'] as $ruta) {
            $this->assertFalse($this->servicio->eliminar($ruta), $ruta);
        }

        $this->disco()->assertExists('anuncios/importante.txt');
        $this->disco()->assertExists('secreto.png');
    }

    public function test_reemplazar_guarda_la_nueva_y_borra_la_anterior(): void
    {
        $anterior = $this->servicio->guardar(Img::pngSubido(), 'anuncios');

        $nueva = $this->servicio->reemplazar($anterior, Img::archivo('b.jpg', Img::jpg()), 'anuncios');

        $this->assertNotSame($anterior, $nueva);
        $this->disco()->assertMissing($anterior);
        $this->disco()->assertExists($nueva);
    }

    public function test_si_la_nueva_es_invalida_la_anterior_se_conserva(): void
    {
        $anterior = $this->servicio->guardar(Img::pngSubido(), 'anuncios');

        try {
            $this->servicio->reemplazar($anterior, Img::archivo('mala.jpg', 'no soy imagen'), 'anuncios');
            $this->fail('Aceptó una imagen inválida.');
        } catch (ValidationException $e) {
            // esperado
        }

        $this->disco()->assertExists($anterior);
    }

    // ---------- URL ----------

    public function test_url_publica_relativa_por_defecto(): void
    {
        $ruta = $this->servicio->guardar(Img::pngSubido(), 'anuncios');

        $this->assertNotNull($this->servicio->url($ruta));
        $this->assertStringEndsWith($ruta, $this->servicio->url($ruta));
    }

    public function test_url_de_nada_o_de_una_ruta_invalida_es_null(): void
    {
        $this->assertNull($this->servicio->url(null));
        $this->assertNull($this->servicio->url(''));
        $this->assertNull($this->servicio->url('../.env'));
    }

    public function test_el_disco_sale_de_la_configuracion(): void
    {
        Storage::fake('otro');
        config(['imagenes.disk' => 'otro']);

        $ruta = $this->servicio->guardar(Img::pngSubido(), 'anuncios');

        Storage::disk('otro')->assertExists($ruta);
        $this->disco()->assertMissing($ruta);
    }

    public function test_la_configuracion_por_defecto_apunta_al_disco_imagenes_y_a_una_ruta_relativa(): void
    {
        $this->assertSame('imagenes', config('imagenes.disk'));
        $this->assertSame('/imagenes', config('filesystems.disks.imagenes.url'));
        $this->assertSame(5120, config('imagenes.max_kb'));
        $this->assertSame(4096, config('imagenes.max_px'));
    }
}
