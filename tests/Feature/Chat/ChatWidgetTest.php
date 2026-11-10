<?php

namespace Tests\Feature\Chat;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * El widget de la landing: solo con el chat activo y key, solo en la landing, y sin cambiar
 * nada más de la landing (el fixture sigue siendo la regla de oro).
 */
class ChatWidgetTest extends ChatTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Igual que las demás pruebas que comparan la landing con el fixture
        $this->withoutVite();
        \Illuminate\Support\Facades\Storage::fake('imagenes');
    }

    private function activarWidget(): void
    {
        config(['chat.activo' => true, 'ia.proveedor' => 'groq', 'ia.proveedores.groq.api_key' => 'gsk_clave-de-prueba']);
    }

    private function landing(): string
    {
        return $this->get('/')->assertOk()->getContent();
    }

    private function normalizar(string $html): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $html));
    }

    private function fixture(): string
    {
        return $this->normalizar((string) file_get_contents(base_path('tests/Fixtures/landing-sin-anuncios.html')));
    }

    // ---------- Cuándo aparece ----------

    public function test_con_el_chat_activo_la_landing_incluye_el_widget(): void
    {
        $this->activarWidget();

        $html = $this->landing();

        $this->assertStringContainsString('data-chat-widget', $html);
        $this->assertStringContainsString('x-data="chatWidget"', $html);
        $this->assertStringContainsString('data-url="' . route('chat.enviar') . '"', $html);
        $this->assertStringContainsString('data-url-token="' . route('chat.token') . '"', $html);
        $this->assertStringContainsString('data-max="500"', $html);
        $this->assertStringContainsString('Agenda tu cita', $html);
        $this->assertStringContainsString('Agendar una cita', $html);
        $this->assertStringContainsString('Ver horarios', $html);
        $this->assertStringContainsString('Ver servicios', $html);
    }

    public function test_los_botones_de_opciones_se_pintan_como_texto_y_miden_44_px(): void
    {
        $this->activarWidget();

        $html = $this->landing();

        $this->assertStringContainsString('x-text="o.texto"', $html);
        $this->assertStringContainsString('role="group" aria-label="Opciones para continuar"', $html);
        $this->assertMatchesRegularExpression('/:disabled="!opcionesActivas\(m\)"[^>]*@click="elegir\(m, o\)"[^>]*min-h-11/s', $html);
        $js = (string) file_get_contents(base_path('resources/js/chat-widget.js'));
        $this->assertStringContainsString('OPCIONES_MAXIMAS = 8', $js);
        $this->assertStringContainsString('this.enviar(opcion.mensaje)', $js);   // un botón es un mensaje normal
    }

    public function test_el_widget_lleva_el_token_csrf_de_la_sesion(): void
    {
        $this->activarWidget();

        $html = $this->landing();

        $this->assertMatchesRegularExpression('/data-token="([A-Za-z0-9]{40})"/', $html);
        preg_match('/data-token="([^"]+)"/', $html, $m);
        $this->assertSame(session()->token(), $m[1]);
    }

    public function test_la_bienvenida_lleva_el_aviso_de_no_compartir_informacion_medica(): void
    {
        $this->activarWidget();
        config(['consultorio.nombre' => 'Dra. Ficticia <b>Prueba</b>']);

        $html = $this->landing();

        $this->assertStringContainsString('Soy la asistente virtual del consultorio de la Dra. Dra. Ficticia &lt;b&gt;Prueba&lt;/b&gt;', $html);   // escapado
        $this->assertStringNotContainsString('<b>Prueba</b>', $html);
        $this->assertStringContainsString('Por favor no compartas información médica ni síntomas por aquí; para eso contacta al consultorio.', $html);
    }

    public function test_el_widget_trae_los_roles_y_etiquetas_de_accesibilidad(): void
    {
        $this->activarWidget();

        $html = $this->landing();

        foreach ([
            'role="dialog"', 'aria-labelledby="chat-titulo"', ':aria-modal=', 'aria-controls="chat-ventana"', ':aria-expanded=',
            'role="log"', 'aria-live="polite"', 'role="status"', ':role="m.rol ===', 'aria-label="Abrir el chat para agendar tu cita"',
            'aria-label="Cerrar el chat"', 'aria-label="Reiniciar la conversación"', 'aria-label="Enviar mensaje"',
            'for="chat-mensaje"', 'aria-describedby="chat-contador"', 'maxlength', '@keydown.escape.window', 'motion-safe:transition',
            'print:hidden',
        ] as $esperado) {
            $this->assertStringContainsString($esperado, $html, $esperado);
        }
    }

    public static function chatsInactivos(): array
    {
        return [
            'sin key' => [['ia.proveedores.groq.api_key' => '']],
            'key solo con espacios' => [['ia.proveedores.groq.api_key' => '   ']],
            'apagado con CHAT_ACTIVO=false' => [['chat.activo' => false]],
            'proveedor desconocido' => [['ia.proveedor' => 'openai']],
            'proveedor con nombre inválido' => [['ia.proveedor' => '../groq']],
            'proveedor sin clase válida' => [['ia.proveedores.groq.clase' => \stdClass::class]],
        ];
    }

    #[DataProvider('chatsInactivos')]
    public function test_sin_el_chat_activo_la_landing_es_identica_a_la_original(array $config): void
    {
        $this->activarWidget();
        config($config);

        $html = $this->landing();

        $this->assertStringNotContainsString('data-chat-widget', $html);
        $this->assertStringNotContainsString('chat-widget:inicio', $html);
        // assertTrue(a === b) y no assertSame: si falla, PHPUnit tarda minutos en calcular el diff de dos páginas
        $this->assertTrue($this->normalizar($html) === $this->fixture(), 'La landing sin el chat activo ya no es idéntica a tests/Fixtures/landing-sin-anuncios.html.');
    }

    public function test_con_la_configuracion_de_las_pruebas_sin_key_no_hay_widget_y_el_fixture_sigue_verde(): void
    {
        // phpunit.xml deja GROQ_API_KEY vacío; la base de las pruebas del chat pone una key falsa, aquí se quita
        $this->assertSame('', (string) env('GROQ_API_KEY'));
        config(['ia.proveedores.groq.api_key' => env('GROQ_API_KEY')]);

        $this->assertTrue($this->normalizar($this->landing()) === $this->fixture());
    }

    public function test_con_el_chat_activo_lo_unico_que_cambia_es_el_bloque_del_widget(): void
    {
        $this->activarWidget();

        $html = $this->landing();
        $sinWidget = preg_replace('/<!-- chat-widget:inicio -->.*<!-- chat-widget:fin -->/s', '', $html, -1, $cuantos);

        $this->assertSame(1, $cuantos);
        // Quitado el bloque, la landing es idéntica al fixture: el chat no movió nada más
        $this->assertTrue($this->normalizar((string) $sinWidget) === $this->fixture(), 'Con el chat activo cambió algo de la landing fuera del bloque del widget.');
    }

    public function test_si_decidir_si_el_chat_esta_activo_falla_la_landing_se_ve_sin_chat(): void
    {
        $this->activarWidget();
        $this->app->bind(\App\Services\Chat\ChatActivo::class, fn () => throw new \RuntimeException('falla'));

        $html = $this->landing();

        $this->assertStringNotContainsString('data-chat-widget', $html);
        $this->assertStringContainsString('id="inicio"', $html);
    }

    // ---------- Solo en la landing ----------

    public static function otrasVistas(): array
    {
        return [
            'login' => ['/login', null],
            'registro' => ['/register', null],
            'olvidé mi contraseña' => ['/forgot-password', null],
            'panel de la doctora' => ['/admin/dashboard', 'admin'],
            'citas de la doctora' => ['/admin/citas', 'admin'],
            'panel de la asistente' => ['/asistente/dashboard', 'asistente'],
            'panel del paciente' => ['/paciente/dashboard', 'paciente'],
            'agendar del paciente' => ['/paciente/citas/agendar', 'paciente'],
        ];
    }

    #[DataProvider('otrasVistas')]
    public function test_las_demas_vistas_no_incluyen_el_widget_aunque_el_chat_este_activo(string $ruta, ?string $rol): void
    {
        $this->activarWidget();

        $respuesta = $rol
            ? $this->actingAs($this->crearUsuario($rol))->get($ruta)
            : $this->get($ruta);

        $respuesta->assertOk();
        $this->assertStringNotContainsString('data-chat-widget', $respuesta->getContent());
        $this->assertStringNotContainsString('chatWidget', $respuesta->getContent());
    }

    public function test_el_widget_se_incluye_desde_la_landing_y_no_desde_el_layout_compartido(): void
    {
        $this->assertStringNotContainsString('landing-chat', (string) file_get_contents(resource_path('views/components/layout.blade.php')));
        $this->assertStringNotContainsString('landing-chat', (string) file_get_contents(resource_path('views/components/navbar.blade.php')));
        $this->assertStringContainsString("@include('partials.landing-chat')", (string) file_get_contents(resource_path('views/welcome.blade.php')));
    }

    // ---------- Texto, nunca HTML; nada en el navegador ----------

    public function test_el_widget_pinta_todo_como_texto_y_no_usa_html_ni_almacenamiento(): void
    {
        $js = (string) file_get_contents(resource_path('js/chat-widget.js'));
        $partial = (string) file_get_contents(resource_path('views/partials/landing-chat.blade.php'));

        // Ni el JS ni la plantilla pintan HTML
        foreach (['x-html', 'v-html', 'innerHTML', 'outerHTML', 'insertAdjacentHTML', 'document.write', 'eval(', 'new Function', 'createContextualFragment', 'DOMParser'] as $peligro) {
            $this->assertStringNotContainsString($peligro, $js, "chat-widget.js usa $peligro");
            $this->assertStringNotContainsString($peligro, $partial, "landing-chat usa $peligro");
        }
        $this->assertStringNotContainsString('{!!', $partial);
        $this->assertStringContainsString('x-text="m.texto"', $partial);

        // La conversación vive solo en la memoria de la página
        foreach (['localStorage', 'sessionStorage', 'indexedDB', 'document.cookie', 'caches.', 'serviceWorker', 'BroadcastChannel', 'navigator.sendBeacon'] as $almacen) {
            $this->assertStringNotContainsString($almacen, $js, "chat-widget.js usa $almacen");
        }
    }

    public function test_el_componente_esta_registrado_en_el_bundle_principal(): void
    {
        $app = (string) file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString("import chatWidget from './chat-widget'", $app);
        $this->assertStringContainsString("Alpine.data('chatWidget', chatWidget)", $app);
    }

    public function test_el_widget_respeta_reducir_movimiento(): void
    {
        $css = (string) file_get_contents(resource_path('css/app.css'));
        $js = (string) file_get_contents(resource_path('js/chat-widget.js'));

        // Los puntos solo se animan sin "reducir movimiento" y el scroll salta sin animación
        $this->assertMatchesRegularExpression('/@media \(prefers-reduced-motion: no-preference\)\s*\{[^}]*\.chat-punto\s*\{[^}]*animation:/s', $css);
        $this->assertStringContainsString("(prefers-reduced-motion: reduce)", $js);
        $this->assertStringContainsString("behavior: reducir ? 'auto' : 'smooth'", $js);
    }

    // ---------- GET /chat/token ----------

    public function test_el_token_nuevo_es_el_de_la_sesion_y_no_se_guarda_en_cache(): void
    {
        $respuesta = $this->get('/chat/token')->assertOk();

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{40}$/', $respuesta->json('token'));
        $this->assertSame(session()->token(), $respuesta->json('token'));
        $this->assertStringContainsString('no-store', $respuesta->headers->get('Cache-Control'));
        $this->assertSame(['token'], array_keys($respuesta->json()));
    }

    public function test_el_token_solo_se_pide_con_get(): void
    {
        $this->post('/chat/token')->assertStatus(405);
        $this->put('/chat/token')->assertStatus(405);
        $this->delete('/chat/token')->assertStatus(405);
    }

    public function test_el_token_tiene_su_propio_limite_por_ip(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.50'])->get('/chat/token')->assertOk();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.50'])->get('/chat/token')->assertStatus(429);
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.51'])->get('/chat/token')->assertOk();
    }

    public function test_con_el_token_nuevo_se_puede_chatear_aunque_la_sesion_haya_caducado(): void
    {
        // Laravel salta el CSRF en pruebas; aquí se vuelve a exigir
        $this->app->bind(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, fn ($app) => new class ($app, $app['encrypter']) extends \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken {
            protected function runningUnitTests()
            {
                return false;
            }
        });
        $this->fingirIA('Hola, ¿en qué te ayudo?');

        // Sin token válido: 419; con el token que entrega /chat/token: 200
        $this->postJson('/chat', ['mensaje' => 'Hola'], ['X-CSRF-TOKEN' => 'token-vencido'])->assertStatus(419);

        $token = $this->get('/chat/token')->json('token');
        $this->postJson('/chat', ['mensaje' => 'Hola'], ['X-CSRF-TOKEN' => $token])->assertOk()->assertJson(['ok' => true]);
    }

    // ---------- Presupuesto de tiempo del servidor ----------

    private function iaQueTarda(int $segundos, array $respuesta): void
    {
        Http::fake(['api.groq.com/*' => function () use ($segundos, $respuesta) {
            Carbon::setTestNow(now()->addSeconds($segundos));

            return Http::response($respuesta);
        }]);
    }

    public function test_no_inicia_otra_vuelta_si_no_cabe_antes_del_tiempo_maximo(): void
    {
        config(['chat.max_segundos' => 20, 'ia.timeout' => 15]);
        $this->iaQueTarda(10, $this->iaLlamada('listar_servicios', []));

        $this->chat(['mensaje' => 'Hola'])
            ->assertOk()
            ->assertExactJson(['ok' => false, 'respuesta' => config('chat.mensajes.tiempo')]);

        // La primera siempre se hace; tras 10 s ya no cabe otra de hasta 15 s dentro de 20
        $this->assertSame(1, $this->llamadasIA());
    }

    public function test_si_cabe_sigue_el_bucle_normalmente(): void
    {
        config(['chat.max_segundos' => 60, 'ia.timeout' => 15]);
        $this->iaQueTarda(10, $this->iaLlamada('listar_servicios', []));

        // Hay tiempo de sobra: el bucle llega a su tope de vueltas, no al del tiempo
        $this->chat(['mensaje' => 'Hola'])->assertOk()->assertExactJson(['ok' => false, 'respuesta' => config('chat.mensajes.vueltas')]);

        // 0 s: vuelta 1; 10 s: cabe (25 <= 60); 20 s: cabe (35 <= 60); 30 s: cabe (45 <= 60); y se acaban las vueltas
        $this->assertSame(4, $this->llamadasIA());
    }

    public function test_la_primera_llamada_siempre_se_hace_aunque_el_tope_sea_menor_que_el_timeout(): void
    {
        config(['chat.max_segundos' => 5, 'ia.timeout' => 15]);
        $this->fingirIA('Hola');

        $this->chat(['mensaje' => 'Hola'])->assertOk()->assertJson(['ok' => true, 'respuesta' => 'Hola']);

        $this->assertSame(1, $this->llamadasIA());
    }

    public function test_con_tope_cero_no_hay_presupuesto(): void
    {
        config(['chat.max_segundos' => 0, 'ia.timeout' => 15]);
        $this->iaQueTarda(100, $this->iaLlamada('listar_servicios', []));

        $this->chat(['mensaje' => 'Hola'])->assertOk()->assertExactJson(['ok' => false, 'respuesta' => config('chat.mensajes.vueltas')]);

        $this->assertSame(4, $this->llamadasIA());
    }

    public function test_el_tiempo_maximo_por_defecto_coincide_con_el_php_ini_del_proyecto(): void
    {
        $this->assertSame(30, config('chat.max_segundos'));
        $this->assertMatchesRegularExpression('/^max_execution_time\s*=\s*30\s*$/m', (string) file_get_contents(base_path('php.ini')));
    }
}
