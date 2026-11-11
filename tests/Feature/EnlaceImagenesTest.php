<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use App\Services\EnlaceImagenes;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** Enlace public/imagenes → IMAGENES_ROOT al arrancar. Carpetas temporales reales; nada de esto toca el proyecto */
class EnlaceImagenesTest extends TestCase
{
    private string $base;

    /** @var string[] */
    private array $logs = [];

    protected function setUp(): void
    {
        parent::setUp();
        EnlaceImagenes::reiniciar();
        $this->base = sys_get_temp_dir() . '/enlace-imagenes-' . bin2hex(random_bytes(6));
        mkdir($this->base . '/public', 0775, true);
        mkdir($this->base . '/data', 0775, true);

        Event::listen(MessageLogged::class, fn (MessageLogged $e) => $this->logs[] = $e->level . ' ' . $e->message . ' ' . json_encode($e->context));
    }

    protected function tearDown(): void
    {
        $this->borrar($this->base);
        EnlaceImagenes::reiniciar();
        parent::tearDown();
    }

    private function borrar(string $ruta): void
    {
        if (is_link($ruta) || is_file($ruta)) {
            @unlink($ruta);

            return;
        }

        if (is_dir($ruta)) {
            foreach (scandir($ruta) as $nombre) {
                if ($nombre !== '.' && $nombre !== '..') {
                    $this->borrar($ruta . '/' . $nombre);
                }
            }
            @rmdir($ruta);
        }
    }

    private function enlace(): string
    {
        return $this->base . '/public/imagenes';
    }

    private function destino(): string
    {
        return $this->base . '/data/imagenes';
    }

    /** Servicio que sí "corre": como en una petición web con Volume (en pruebas el real nunca corre) */
    private function servicioWeb(): EnlaceImagenes
    {
        return new class extends EnlaceImagenes {
            protected function debeCorrer(): bool
            {
                return $this->hayVolume();
            }
        };
    }

    private function configurarVolume(?string $raiz = null, string $disco = 'imagenes'): void
    {
        config([
            'filesystems.disks.imagenes.root' => $raiz ?? $this->destino(),
            'imagenes.disk' => $disco,
        ]);
        $this->app->usePublicPath($this->base . '/public');
    }

    private function logTodo(): string
    {
        return implode("\n", $this->logs);
    }

    // ---------- sincronizar ----------

    public function test_crea_el_enlace_y_la_carpeta_destino_cuando_faltan(): void
    {
        $this->assertDirectoryDoesNotExist($this->destino());

        $this->assertSame(EnlaceImagenes::CREADO, (new EnlaceImagenes)->sincronizar($this->enlace(), $this->destino()));

        $this->assertTrue(is_link($this->enlace()));
        $this->assertSame($this->destino(), readlink($this->enlace()));
        $this->assertDirectoryExists($this->destino());
    }

    public function test_lo_que_se_guarda_en_el_destino_se_ve_por_el_enlace(): void
    {
        (new EnlaceImagenes)->sincronizar($this->enlace(), $this->destino());
        file_put_contents($this->destino() . '/foto.txt', 'contenido ficticio');

        $this->assertSame('contenido ficticio', file_get_contents($this->enlace() . '/foto.txt'));
    }

    public function test_si_ya_apunta_bien_no_hace_nada(): void
    {
        $servicio = new EnlaceImagenes;
        $servicio->sincronizar($this->enlace(), $this->destino());
        $antes = lstat($this->enlace())['ino'];

        $this->assertSame(EnlaceImagenes::LISTO, $servicio->sincronizar($this->enlace(), $this->destino() . '/'));   // aunque venga con "/" final

        $this->assertSame($antes, lstat($this->enlace())['ino']);       // el mismo enlace: no se recreó
        $this->assertSame([], $this->logs);
    }

    public function test_corrige_un_enlace_que_apunta_a_otro_lugar(): void
    {
        mkdir($this->base . '/viejo', 0775, true);
        symlink($this->base . '/viejo', $this->enlace());

        $this->assertSame(EnlaceImagenes::CORREGIDO, (new EnlaceImagenes)->sincronizar($this->enlace(), $this->destino()));

        $this->assertSame($this->destino(), readlink($this->enlace()));
        $this->assertDirectoryExists($this->base . '/viejo');           // el destino anterior no se toca
    }

    public function test_corrige_un_enlace_roto(): void
    {
        symlink($this->base . '/ya-no-existe', $this->enlace());

        $this->assertSame(EnlaceImagenes::CORREGIDO, (new EnlaceImagenes)->sincronizar($this->enlace(), $this->destino()));

        $this->assertSame($this->destino(), readlink($this->enlace()));
    }

    public function test_una_carpeta_real_no_se_toca_y_solo_avisa_sin_rutas(): void
    {
        mkdir($this->enlace(), 0775, true);
        file_put_contents($this->enlace() . '/foto.txt', 'no debe borrarse');

        $this->assertSame(EnlaceImagenes::NO_ES_ENLACE, (new EnlaceImagenes)->sincronizar($this->enlace(), $this->destino()));

        $this->assertFalse(is_link($this->enlace()));
        $this->assertSame('no debe borrarse', file_get_contents($this->enlace() . '/foto.txt'));
        $this->assertStringContainsString('no es un enlace', $this->logTodo());
        $this->assertStringNotContainsString($this->base, $this->logTodo());
    }

    public function test_un_archivo_real_tampoco_se_toca(): void
    {
        file_put_contents($this->enlace(), 'archivo');

        $this->assertSame(EnlaceImagenes::NO_ES_ENLACE, (new EnlaceImagenes)->sincronizar($this->enlace(), $this->destino()));

        $this->assertSame('archivo', file_get_contents($this->enlace()));
    }

    public function test_si_el_destino_no_se_puede_crear_no_lanza_y_el_log_no_trae_rutas(): void
    {
        file_put_contents($this->base . '/archivo', 'x');                // un archivo donde debería haber carpeta

        $resultado = (new EnlaceImagenes)->sincronizar($this->enlace(), $this->base . '/archivo/imagenes');

        $this->assertSame(EnlaceImagenes::FALLO, $resultado);
        $this->assertFalse(file_exists($this->enlace()) || is_link($this->enlace()));
        $this->assertStringContainsString('mkdir', $this->logTodo());
        $this->assertStringContainsString('storage:link', $this->logTodo());
        $this->assertStringNotContainsString($this->base, $this->logTodo());
    }

    public function test_si_no_se_puede_crear_el_enlace_no_lanza_y_el_log_no_trae_rutas(): void
    {
        // public/ no existe: symlink falla (como con permisos insuficientes)
        $resultado = (new EnlaceImagenes)->sincronizar($this->base . '/no-existe/imagenes', $this->destino());

        $this->assertSame(EnlaceImagenes::FALLO, $resultado);
        $this->assertStringContainsString('symlink', $this->logTodo());
        $this->assertStringNotContainsString($this->base, $this->logTodo());
    }

    // ---------- asegurar: cuándo corre ----------

    public function test_en_pruebas_y_en_consola_asegurar_no_hace_nada(): void
    {
        $this->configurarVolume();

        (new EnlaceImagenes)->asegurar();

        $this->assertFalse(is_link($this->enlace()));
        $this->assertDirectoryDoesNotExist($this->destino());
    }

    public function test_con_volume_en_una_peticion_web_asegurar_crea_el_enlace(): void
    {
        $this->configurarVolume();

        $this->servicioWeb()->asegurar();

        $this->assertSame($this->destino(), readlink($this->enlace()));
    }

    public function test_solo_comprueba_una_vez_por_proceso(): void
    {
        $this->configurarVolume();
        $servicio = $this->servicioWeb();
        $servicio->asegurar();
        unlink($this->enlace());

        $servicio->asegurar();                                           // la segunda llamada ya no toca el disco
        $this->servicioWeb()->asegurar();                                // ni otra instancia en el mismo proceso

        $this->assertFalse(is_link($this->enlace()));
    }

    public function test_sin_volume_o_con_la_ruta_por_defecto_no_hace_nada(): void
    {
        $this->app->usePublicPath($this->base . '/public');

        foreach ([null, '', config('filesystems.imagenes_ruta_defecto'), config('filesystems.imagenes_ruta_defecto') . '/'] as $raiz) {
            EnlaceImagenes::reiniciar();
            config(['filesystems.disks.imagenes.root' => $raiz, 'imagenes.disk' => 'imagenes']);
            $this->servicioWeb()->asegurar();
            $this->assertFalse(is_link($this->enlace()), 'raíz: ' . var_export($raiz, true));
        }

        $this->assertSame([], $this->logs);                              // ni siquiera lo intentó (un intento fallido dejaría aviso)
    }

    public function test_con_otro_disco_como_s3_no_hace_nada(): void
    {
        $this->configurarVolume(disco: 's3');

        $this->servicioWeb()->asegurar();

        $this->assertFalse(is_link($this->enlace()));
        $this->assertDirectoryDoesNotExist($this->destino());
    }

    public function test_asegurar_nunca_lanza_aunque_falle_y_el_log_lleva_solo_la_clase(): void
    {
        $this->configurarVolume(raiz: $this->base . '/data/imagenes');
        $servicio = new class extends EnlaceImagenes {
            protected function debeCorrer(): bool
            {
                throw new \RuntimeException('Fallo secreto con /ruta/privada/del/volumen');
            }
        };

        $servicio->asegurar();

        $this->assertStringContainsString('RuntimeException', $this->logTodo());
        $this->assertStringNotContainsString('secreto', $this->logTodo());
        $this->assertStringNotContainsString('/ruta/privada', $this->logTodo());
    }

    // ---------- Integración y configuración ----------

    public function test_el_arranque_de_la_app_llama_a_asegurar_una_vez(): void
    {
        $llamadas = 0;
        $this->app->instance(EnlaceImagenes::class, new class($llamadas) extends EnlaceImagenes {
            public function __construct(private int &$llamadas) {}

            public function asegurar(): void
            {
                $this->llamadas++;
            }
        });

        (new AppServiceProvider($this->app))->boot();

        $this->assertSame(1, $llamadas);
    }

    public function test_el_disco_y_los_links_comparten_la_ruta_por_defecto(): void
    {
        $this->assertSame(storage_path('app/public/imagenes'), config('filesystems.imagenes_ruta_defecto'));
        $this->assertSame(config('filesystems.imagenes_ruta_defecto'), config('filesystems.disks.imagenes.root'));
        $this->assertSame(config('filesystems.imagenes_ruta_defecto'), config('filesystems.links')[public_path('imagenes')]);
    }
}
