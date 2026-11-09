<?php

namespace Tests\Feature\IA;

use App\Services\IA\Datos\HerramientaIA;
use App\Services\IA\Datos\MensajeIA;
use App\Services\IA\Datos\RespuestaIA;
use App\Services\IA\GeminiProveedor;
use App\Services\IA\ProveedorIA;
use App\Services\IA\ProveedorNoDisponible;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Un proveedor de prueba: demuestra que agregar uno no toca el resto del código */
class ProveedorFalso implements ProveedorIA
{
    public function __construct(public array $config) {}

    public function responder(array $mensajes, array $herramientas = [], ?string $instrucciones = null): RespuestaIA
    {
        return RespuestaIA::conTexto('respuesta del proveedor falso con modelo ' . $this->config['modelo']);
    }
}

class NoEsUnProveedor
{
    public function __construct(public array $config) {}
}

class ProveedorIAFactoryTest extends TestCase
{
    /** @var string[] */
    private array $log = [];

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config(['logging.default' => 'null']);
        Event::listen(MessageLogged::class, fn (MessageLogged $e) => $this->log[] = $e->message);
    }

    public function test_por_defecto_usa_gemini(): void
    {
        $this->assertSame('gemini', config('ia.proveedor'));
        $this->assertInstanceOf(GeminiProveedor::class, app(ProveedorIA::class));
    }

    public function test_el_modelo_por_defecto_es_un_flash_y_se_cambia_con_la_config(): void
    {
        $this->assertSame('gemini-3.6-flash', config('ia.proveedores.gemini.modelo'));
    }

    public function test_un_proveedor_nuevo_se_agrega_solo_con_config(): void
    {
        config([
            'ia.proveedor' => 'falso',
            'ia.proveedores.falso' => ['clase' => ProveedorFalso::class, 'api_key' => 'k', 'modelo' => 'm1'],
        ]);

        $proveedor = app(ProveedorIA::class);

        $this->assertInstanceOf(ProveedorFalso::class, $proveedor);
        $this->assertSame('respuesta del proveedor falso con modelo m1', $proveedor->responder([MensajeIA::usuario('Hola')], [new HerramientaIA('x', 'y')])->texto);
    }

    public function test_el_nombre_del_proveedor_no_distingue_mayusculas_ni_espacios(): void
    {
        config(['ia.proveedor' => '  Gemini ']);

        $this->assertInstanceOf(GeminiProveedor::class, app(ProveedorIA::class));
    }

    public function test_un_proveedor_desconocido_responde_con_el_mensaje_amable_y_no_rompe(): void
    {
        config(['ia.proveedor' => 'openai']);

        $proveedor = app(ProveedorIA::class);
        $this->assertInstanceOf(ProveedorNoDisponible::class, $proveedor);

        $respuesta = $proveedor->responder([MensajeIA::usuario('Hola')]);

        $this->assertTrue($respuesta->falla);
        $this->assertSame(config('ia.mensaje_error'), $respuesta->texto);
        $this->assertStringContainsString('openai', implode("\n", $this->log));
        Http::assertNothingSent();
    }

    public function test_un_nombre_con_caracteres_raros_se_rechaza(): void
    {
        foreach (['', '../gemini', 'gemini.modelo', 'a b'] as $nombre) {
            config(['ia.proveedor' => $nombre]);

            $this->assertInstanceOf(ProveedorNoDisponible::class, app(ProveedorIA::class), "nombre: '$nombre'");
        }
    }

    public function test_una_clase_que_no_es_proveedor_se_rechaza(): void
    {
        config([
            'ia.proveedor' => 'raro',
            'ia.proveedores.raro' => ['clase' => NoEsUnProveedor::class],
        ]);

        $this->assertInstanceOf(ProveedorNoDisponible::class, app(ProveedorIA::class));

        config(['ia.proveedores.raro' => ['clase' => 'App\\Services\\IA\\NoExiste']]);

        $this->assertInstanceOf(ProveedorNoDisponible::class, app(ProveedorIA::class));
    }
}
