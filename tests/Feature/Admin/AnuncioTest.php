<?php

namespace Tests\Feature\Admin;

use App\Models\Anuncio;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AnuncioTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = $this->crearUsuario('admin');
    }

    // 'role' no está en $fillable: se asigna explícitamente
    private function crearUsuario(string $role): User
    {
        $user = User::factory()->make(['apellidos' => 'Pérez', 'activo' => true]);
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function datos(array $cambios = []): array
    {
        return array_merge([
            'titulo' => 'Limpieza con 20% de descuento',
            'descripcion' => 'Durante todo noviembre',
            'tipo' => 'promocion',
            'color' => 'rosa',
            'activo' => '1',
        ], $cambios);
    }

    private function anuncio(array $cambios = []): Anuncio
    {
        return Anuncio::create(array_merge([
            'titulo' => 'Aviso de prueba',
            'tipo' => 'aviso',
            'activo' => true,
            'color' => 'rosa',
        ], $cambios));
    }

    // ---------- Acceso ----------

    public function test_solo_el_admin_entra(): void
    {
        $anuncio = $this->anuncio();
        $rutas = [
            ['get', route('admin.anuncios.index')],
            ['get', route('admin.anuncios.create')],
            ['post', route('admin.anuncios.store')],
            ['get', route('admin.anuncios.edit', $anuncio->id)],
            ['put', route('admin.anuncios.update', $anuncio->id)],
            ['patch', route('admin.anuncios.toggle', $anuncio->id)],
            ['delete', route('admin.anuncios.destroy', $anuncio->id)],
        ];

        foreach ($rutas as [$metodo, $url]) {
            $this->{$metodo}($url)->assertRedirect('/login');
        }

        foreach (['asistente', 'paciente'] as $rol) {
            $usuario = $this->crearUsuario($rol);
            foreach ($rutas as [$metodo, $url]) {
                $this->actingAs($usuario)->{$metodo}($url, $this->datos())->assertForbidden();
            }
        }

        $this->assertDatabaseCount('anuncios', 1);
        $this->assertSame('Aviso de prueba', $anuncio->fresh()->titulo);
    }

    public function test_el_menu_apunta_a_la_seccion(): void
    {
        $this->actingAs($this->admin)->get(route('admin.anuncios.index'))
            ->assertOk()
            ->assertSee('href="' . route('admin.anuncios.index') . '"', false)
            ->assertSee('Landing Page');
    }

    // ---------- CRUD ----------

    public function test_lista_vacia_explica_que_la_landing_no_cambia(): void
    {
        $this->actingAs($this->admin)->get(route('admin.anuncios.index'))
            ->assertOk()
            ->assertSee('Aún no hay anuncios');
    }

    public function test_la_lista_muestra_el_estado_de_cada_anuncio(): void
    {
        $this->anuncio(['titulo' => 'Uno vigente']);
        $this->anuncio(['titulo' => 'Uno apagado', 'activo' => false]);
        $this->anuncio(['titulo' => 'Uno vencido', 'fecha_fin' => Carbon::now()->subDays(3)->toDateString()]);
        $this->anuncio(['titulo' => 'Uno programado', 'fecha_inicio' => Carbon::now()->addDays(3)->toDateString()]);

        $this->actingAs($this->admin)->get(route('admin.anuncios.index'))
            ->assertOk()
            ->assertSee('Uno vigente')
            ->assertSee('Vigente')
            ->assertSee('Inactivo')
            ->assertSee('Vencido')
            ->assertSee('Programado');
    }

    public function test_crea_un_anuncio_completo(): void
    {
        $this->actingAs($this->admin)->post(route('admin.anuncios.store'), $this->datos([
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-30',
            'boton_texto' => 'Quiero mi descuento',
            'boton_enlace' => 'https://wa.me/527531234567',
            'emoji' => '🦷',
            'color' => 'verde',
        ]))->assertRedirect(route('admin.anuncios.index'))->assertSessionHasNoErrors();

        $anuncio = Anuncio::first();
        $this->assertSame('Limpieza con 20% de descuento', $anuncio->titulo);
        $this->assertSame('promocion', $anuncio->tipo);
        $this->assertSame('2026-11-01', $anuncio->fecha_inicio->toDateString());
        $this->assertSame('2026-11-30', $anuncio->fecha_fin->toDateString());
        $this->assertSame('Quiero mi descuento', $anuncio->boton_texto);
        $this->assertSame('🦷', $anuncio->emoji);
        $this->assertSame('verde', $anuncio->color);
        $this->assertTrue($anuncio->activo);
    }

    public function test_crea_un_anuncio_minimo_y_sin_marcar_activo_queda_inactivo(): void
    {
        $this->actingAs($this->admin)->post(route('admin.anuncios.store'), [
            'titulo' => 'Cerrado el lunes', 'tipo' => 'aviso', 'color' => 'gris', 'activo' => '0',
        ])->assertSessionHasNoErrors();

        $anuncio = Anuncio::first();
        $this->assertFalse($anuncio->activo);
        $this->assertNull($anuncio->fecha_inicio);
        $this->assertNull($anuncio->boton_enlace);
    }

    public function test_edita_un_anuncio(): void
    {
        $anuncio = $this->anuncio();

        $this->actingAs($this->admin)->get(route('admin.anuncios.edit', $anuncio->id))
            ->assertOk()
            ->assertSee('value="Aviso de prueba"', false);

        $this->actingAs($this->admin)->put(route('admin.anuncios.update', $anuncio->id), $this->datos(['titulo' => 'Título nuevo']))
            ->assertRedirect(route('admin.anuncios.index'));

        $this->assertSame('Título nuevo', $anuncio->fresh()->titulo);
        $this->assertSame('promocion', $anuncio->fresh()->tipo);
    }

    public function test_activa_y_desactiva(): void
    {
        $anuncio = $this->anuncio();

        $this->actingAs($this->admin)->patch(route('admin.anuncios.toggle', $anuncio->id))->assertRedirect();
        $this->assertFalse($anuncio->fresh()->activo);

        $this->actingAs($this->admin)->patch(route('admin.anuncios.toggle', $anuncio->id))->assertRedirect();
        $this->assertTrue($anuncio->fresh()->activo);
    }

    public function test_elimina(): void
    {
        $anuncio = $this->anuncio();

        $this->actingAs($this->admin)->delete(route('admin.anuncios.destroy', $anuncio->id))
            ->assertRedirect(route('admin.anuncios.index'));

        $this->assertDatabaseCount('anuncios', 0);
    }

    public function test_un_error_de_validacion_conserva_lo_escrito(): void
    {
        $this->actingAs($this->admin)->from(route('admin.anuncios.create'))->followingRedirects()
            ->post(route('admin.anuncios.store'), $this->datos(['titulo' => 'Mi promo', 'boton_enlace' => 'javascript:alert(1)']))
            ->assertOk()
            ->assertSee('El enlace debe ser una dirección web')
            ->assertSee('value="Mi promo"', false);

        $this->assertDatabaseCount('anuncios', 0);
    }

    // ---------- Validación ----------

    public function test_validaciones_basicas(): void
    {
        $this->actingAs($this->admin);
        $url = route('admin.anuncios.store');

        $this->post($url, $this->datos(['titulo' => null]))->assertSessionHasErrors(['titulo' => 'El título es obligatorio.']);
        $this->post($url, $this->datos(['titulo' => str_repeat('a', 121)]))->assertSessionHasErrors('titulo');
        $this->post($url, $this->datos(['descripcion' => str_repeat('a', 301)]))->assertSessionHasErrors('descripcion');
        $this->post($url, $this->datos(['tipo' => 'otro']))->assertSessionHasErrors('tipo');
        $this->post($url, $this->datos(['tipo' => null]))->assertSessionHasErrors('tipo');
        $this->post($url, $this->datos(['color' => '#ff0000']))->assertSessionHasErrors('color');
        $this->post($url, $this->datos(['color' => 'red; background:url(x)']))->assertSessionHasErrors('color');
        $this->post($url, $this->datos(['fecha_inicio' => 'mañana']))->assertSessionHasErrors('fecha_inicio');
        $this->post($url, $this->datos(['fecha_inicio' => '2026-11-10', 'fecha_fin' => '2026-11-09']))
            ->assertSessionHasErrors(['fecha_fin' => 'La fecha final no puede ser anterior a la inicial.']);
        $this->post($url, $this->datos(['boton_texto' => str_repeat('a', 41)]))->assertSessionHasErrors('boton_texto');

        $this->assertDatabaseCount('anuncios', 0);
    }

    public function test_fecha_fin_igual_a_la_inicial_es_valida(): void
    {
        $this->actingAs($this->admin)->post(route('admin.anuncios.store'), $this->datos([
            'fecha_inicio' => '2026-11-10', 'fecha_fin' => '2026-11-10',
        ]))->assertSessionHasNoErrors();
    }

    public static function enlaces(): array
    {
        return [
            'https' => ['https://dentissa.mx/promo?a=1#x', true],
            'http' => ['http://dentissa.mx', true],
            'whatsapp' => ['https://wa.me/527531234567', true],
            'ruta interna' => ['/login', true],
            'raiz' => ['/', true],
            'ancla' => ['#contacto', true],
            'telefono' => ['tel:+527531234567', true],
            'vacio' => ['', true],
            'javascript' => ['javascript:alert(1)', false],
            'javascript mayusculas' => ['JavaScript:alert(1)', false],
            'javascript con espacio' => [' javascript:alert(1)', false],
            'data' => ['data:text/html,<script>alert(1)</script>', false],
            'vbscript' => ['vbscript:msgbox(1)', false],
            'protocolo relativo' => ['//evil.com', false],
            'barra invertida' => ['/\\evil.com', false],
            'ftp' => ['ftp://dentissa.mx/archivo', false],
            'mailto' => ['mailto:a@b.mx', false],
            'espacio en url' => ['https://dentissa .mx/x', false],
            'salto de linea' => ["https://dentissa.mx/\nx", false],
            'ancla con espacio' => ['#a b', false],
            'sin esquema' => ['dentissa.mx', false],
            'https sin host' => ['https://', false],
        ];
    }

    #[DataProvider('enlaces')]
    public function test_validacion_de_enlaces(string $enlace, bool $valido): void
    {
        $this->assertSame($valido, Anuncio::enlaceValido($enlace));
    }

    public function test_un_enlace_malo_se_rechaza_en_el_formulario(): void
    {
        $this->actingAs($this->admin)->post(route('admin.anuncios.store'), $this->datos(['boton_enlace' => 'javascript:alert(1)']))
            ->assertSessionHasErrors('boton_enlace');
        $this->actingAs($this->admin)->post(route('admin.anuncios.store'), $this->datos(['boton_enlace' => '//evil.com']))
            ->assertSessionHasErrors('boton_enlace');

        $this->assertDatabaseCount('anuncios', 0);
    }

    public static function emojis(): array
    {
        return [
            'diente' => ['🦷', true],
            'destellos' => ['✨', true],
            'con variante' => ['❤️', true],
            'secuencia con zwj' => ['👨‍⚕️', true],
            'bandera' => ['🇲🇽', true],
            'varios' => ['🦷✨', true],
            'texto' => ['hola', false],
            'etiqueta html' => ['<b>', false],
            'numero' => ['1', false],
            'texto con emoji' => ['a🦷', false],
            'demasiados' => ['🦷🦷🦷🦷🦷🦷🦷🦷🦷🦷🦷', false],
        ];
    }

    #[DataProvider('emojis')]
    public function test_validacion_de_emoji(string $emoji, bool $valido): void
    {
        $this->actingAs($this->admin)->post(route('admin.anuncios.store'), $this->datos(['emoji' => $emoji]));

        $this->assertDatabaseCount('anuncios', $valido ? 1 : 0);
    }

    // ---------- Vigencia ----------

    public function test_vigencia_incluye_los_extremos_y_respeta_activo(): void
    {
        Carbon::setTestNow('2026-10-20 18:00:00', 'America/Mexico_City');
        $hoy = '2026-10-20';

        $sinFechas = $this->anuncio(['titulo' => 'sin fechas']);
        $empiezaHoy = $this->anuncio(['titulo' => 'empieza hoy', 'fecha_inicio' => $hoy]);
        $terminaHoy = $this->anuncio(['titulo' => 'termina hoy', 'fecha_fin' => $hoy]);
        $dentroRango = $this->anuncio(['titulo' => 'rango', 'fecha_inicio' => '2026-10-01', 'fecha_fin' => '2026-10-31']);
        $vencido = $this->anuncio(['titulo' => 'vencido', 'fecha_fin' => '2026-10-19']);
        $programado = $this->anuncio(['titulo' => 'programado', 'fecha_inicio' => '2026-10-21']);
        $apagado = $this->anuncio(['titulo' => 'apagado', 'activo' => false]);

        $vigentes = Anuncio::vigentes()->pluck('titulo')->all();

        $this->assertEqualsCanonicalizing(['sin fechas', 'empieza hoy', 'termina hoy', 'rango'], $vigentes);
        $this->assertSame('vigente', $terminaHoy->estado());
        $this->assertSame('vencido', $vencido->estado());
        $this->assertSame('programado', $programado->estado());
        $this->assertSame('inactivo', $apagado->estado());

        Carbon::setTestNow();
    }

    public function test_hoy_se_calcula_en_la_zona_del_consultorio_no_en_utc(): void
    {
        // 03:00 UTC del 21 = 21:00 del 20 en México: una promoción "hasta el 20" sigue vigente
        Carbon::setTestNow(Carbon::parse('2026-10-21 03:00:00', 'UTC'));

        $this->anuncio(['titulo' => 'hasta el veinte', 'fecha_fin' => '2026-10-20']);

        $this->assertSame('2026-10-20', Anuncio::hoy());
        $this->assertCount(1, Anuncio::vigentes()->get());

        Carbon::setTestNow();
    }
}
