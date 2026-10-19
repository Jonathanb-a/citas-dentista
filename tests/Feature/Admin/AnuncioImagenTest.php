<?php

namespace Tests\Feature\Admin;

use App\Models\Anuncio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ImagenesDePrueba as Img;
use Tests\TestCase;

/** Imágenes y orden de los anuncios en el panel de admin */
class AnuncioImagenTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('imagenes');
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
        return array_merge(['titulo' => 'Promo', 'tipo' => 'promocion', 'color' => 'rosa', 'activo' => '1'], $cambios);
    }

    private function anuncio(array $cambios = []): Anuncio
    {
        return Anuncio::create(array_merge(['titulo' => 'Aviso', 'tipo' => 'aviso', 'activo' => true, 'color' => 'rosa', 'orden' => 1], $cambios));
    }

    private function archivos(): array
    {
        return Storage::disk('imagenes')->allFiles();
    }

    private function conImagen(string $titulo = 'Con imagen', int $orden = 1): Anuncio
    {
        $ruta = app(\App\Services\ImagenService::class)->guardar(Img::pngSubido(), 'anuncios');

        return $this->anuncio(['titulo' => $titulo, 'imagen' => $ruta, 'orden' => $orden]);
    }

    // ---------- Crear ----------

    public function test_crea_un_anuncio_con_imagen(): void
    {
        $this->actingAs($this->admin)->post(route('admin.anuncios.store'), $this->datos(['imagen' => Img::pngSubido('promo.png')]))
            ->assertRedirect(route('admin.anuncios.index'))
            ->assertSessionHasNoErrors();

        $anuncio = Anuncio::first();
        $this->assertMatchesRegularExpression('#^anuncios/[a-z0-9]{26}\.png$#', $anuncio->imagen);
        Storage::disk('imagenes')->assertExists($anuncio->imagen);
        $this->assertSame([$anuncio->imagen], $this->archivos());
    }

    public function test_crea_un_anuncio_sin_imagen(): void
    {
        $this->actingAs($this->admin)->post(route('admin.anuncios.store'), $this->datos())->assertSessionHasNoErrors();

        $this->assertNull(Anuncio::first()->imagen);
        $this->assertSame([], $this->archivos());
    }

    public function test_los_anuncios_nuevos_van_al_final_del_carrusel(): void
    {
        $this->actingAs($this->admin);
        $this->post(route('admin.anuncios.store'), $this->datos(['titulo' => 'A']));
        $this->post(route('admin.anuncios.store'), $this->datos(['titulo' => 'B']));
        $this->post(route('admin.anuncios.store'), $this->datos(['titulo' => 'C']));

        $this->assertSame(['A' => 1, 'B' => 2, 'C' => 3], Anuncio::ordenados()->pluck('orden', 'titulo')->all());
    }

    public function test_rechaza_archivos_que_no_son_imagenes_reales_y_no_guarda_nada(): void
    {
        $malos = [
            'texto' => Img::archivo('falso.jpg', 'no soy imagen'),
            'svg' => Img::archivo('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            'gif' => Img::archivo('a.gif', base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7')),
            'php' => Img::archivo('shell.png', '<?php echo 1; ?>'),
            'pdf' => Img::archivo('doc.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj"),
        ];

        foreach ($malos as $nombre => $archivo) {
            $this->actingAs($this->admin)->post(route('admin.anuncios.store'), $this->datos(['imagen' => $archivo]))
                ->assertSessionHasErrors('imagen');
        }

        $this->assertDatabaseCount('anuncios', 0);
        $this->assertSame([], $this->archivos());
    }

    public function test_rechaza_imagenes_de_mas_de_cinco_megas_con_mensaje_en_espanol(): void
    {
        $this->actingAs($this->admin)->post(route('admin.anuncios.store'), $this->datos([
            'imagen' => Img::pngSubido('pesada.png', 1, 1, 5 * 1024 * 1024 + 4096),
        ]))->assertSessionHasErrors(['imagen' => 'La imagen no puede pesar más de 5 MB.']);

        $this->assertDatabaseCount('anuncios', 0);
        $this->assertSame([], $this->archivos());
    }

    public function test_rechaza_imagenes_con_demasiados_pixeles(): void
    {
        $this->actingAs($this->admin)->post(route('admin.anuncios.store'), $this->datos([
            'imagen' => Img::pngSubido('enorme.png', 5000, 5000),
        ]))->assertSessionHasErrors('imagen');

        $this->assertSame([], $this->archivos());
    }

    public function test_si_falla_el_guardado_del_anuncio_se_borra_la_imagen_recien_subida(): void
    {
        Anuncio::creating(fn () => throw new \RuntimeException('base caída'));
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($this->admin)->post(route('admin.anuncios.store'), $this->datos(['imagen' => Img::pngSubido()]));
            $this->fail('Debió propagar el error.');
        } catch (\RuntimeException $e) {
            $this->assertSame('base caída', $e->getMessage());
        }

        $this->assertSame([], $this->archivos());
    }

    // ---------- Editar ----------

    public function test_editar_sin_tocar_la_imagen_la_conserva(): void
    {
        $anuncio = $this->conImagen();
        $ruta = $anuncio->imagen;

        $this->actingAs($this->admin)->put(route('admin.anuncios.update', $anuncio->id), $this->datos(['titulo' => 'Nuevo título']))
            ->assertSessionHasNoErrors();

        $this->assertSame($ruta, $anuncio->fresh()->imagen);
        $this->assertSame([$ruta], $this->archivos());
    }

    public function test_reemplazar_la_imagen_borra_la_anterior(): void
    {
        $anuncio = $this->conImagen();
        $anterior = $anuncio->imagen;

        $this->actingAs($this->admin)->put(route('admin.anuncios.update', $anuncio->id), $this->datos([
            'imagen' => Img::archivo('nueva.jpg', Img::jpg()),
        ]))->assertSessionHasNoErrors();

        $nueva = $anuncio->fresh()->imagen;
        $this->assertNotSame($anterior, $nueva);
        $this->assertStringEndsWith('.jpg', $nueva);
        $this->assertSame([$nueva], $this->archivos());
    }

    public function test_una_imagen_invalida_al_editar_no_toca_la_actual(): void
    {
        $anuncio = $this->conImagen();
        $anterior = $anuncio->imagen;

        $this->actingAs($this->admin)->put(route('admin.anuncios.update', $anuncio->id), $this->datos([
            'imagen' => Img::archivo('falso.png', 'no soy imagen'),
        ]))->assertSessionHasErrors('imagen');

        $this->assertSame($anterior, $anuncio->fresh()->imagen);
        $this->assertSame([$anterior], $this->archivos());
    }

    public function test_un_error_en_otro_campo_no_deja_la_imagen_nueva_huerfana_ni_pierde_la_actual(): void
    {
        $anuncio = $this->conImagen();
        $anterior = $anuncio->imagen;

        $this->actingAs($this->admin)->put(route('admin.anuncios.update', $anuncio->id), $this->datos([
            'titulo' => '',
            'imagen' => Img::pngSubido('nueva.png'),
        ]))->assertSessionHasErrors('titulo');

        $this->assertSame($anterior, $anuncio->fresh()->imagen);
        $this->assertSame([$anterior], $this->archivos());
    }

    public function test_quitar_la_imagen_la_borra_del_disco(): void
    {
        $anuncio = $this->conImagen();

        $this->actingAs($this->admin)->put(route('admin.anuncios.update', $anuncio->id), $this->datos(['quitar_imagen' => '1']))
            ->assertSessionHasNoErrors();

        $this->assertNull($anuncio->fresh()->imagen);
        $this->assertSame([], $this->archivos());
    }

    public function test_si_se_sube_una_nueva_y_se_marca_quitar_gana_la_nueva(): void
    {
        $anuncio = $this->conImagen();

        $this->actingAs($this->admin)->put(route('admin.anuncios.update', $anuncio->id), $this->datos([
            'quitar_imagen' => '1', 'imagen' => Img::pngSubido('nueva.png'),
        ]));

        $this->assertNotNull($anuncio->fresh()->imagen);
        $this->assertCount(1, $this->archivos());
    }

    public function test_la_pantalla_de_editar_muestra_la_imagen_actual(): void
    {
        $anuncio = $this->conImagen();

        $this->actingAs($this->admin)->get(route('admin.anuncios.edit', $anuncio->id))
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('src="' . $anuncio->imagenUrl() . '"', false)
            ->assertSee('Quitar esta imagen')
            ->assertSee('accept="image/jpeg,image/png,image/webp"', false);
    }

    public function test_el_formulario_de_crear_acepta_archivos(): void
    {
        $this->actingAs($this->admin)->get(route('admin.anuncios.create'))
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="imagen"', false)
            ->assertDontSee('Quitar esta imagen');
    }

    // ---------- Eliminar ----------

    public function test_eliminar_el_anuncio_borra_su_imagen(): void
    {
        $anuncio = $this->conImagen();
        $otro = $this->conImagen('Otro', 2);

        $this->actingAs($this->admin)->delete(route('admin.anuncios.destroy', $anuncio->id))->assertRedirect();

        $this->assertDatabaseCount('anuncios', 1);
        $this->assertSame([$otro->imagen], $this->archivos());
    }

    public function test_eliminar_un_anuncio_cuyo_archivo_ya_no_existe_no_truena(): void
    {
        $anuncio = $this->conImagen();
        Storage::disk('imagenes')->delete($anuncio->imagen);

        $this->actingAs($this->admin)->delete(route('admin.anuncios.destroy', $anuncio->id))->assertRedirect();

        $this->assertDatabaseCount('anuncios', 0);
    }

    public function test_la_lista_muestra_la_miniatura(): void
    {
        $anuncio = $this->conImagen();

        $this->actingAs($this->admin)->get(route('admin.anuncios.index'))
            ->assertOk()
            ->assertSee($anuncio->imagenUrl());
    }

    // ---------- Orden ----------

    private function titulosEnOrden(): array
    {
        return Anuncio::ordenados()->pluck('titulo')->all();
    }

    public function test_subir_y_bajar_cambian_la_posicion(): void
    {
        foreach (['A', 'B', 'C'] as $i => $t) {
            $this->anuncio(['titulo' => $t, 'orden' => $i + 1]);
        }
        $c = Anuncio::where('titulo', 'C')->first();
        $a = Anuncio::where('titulo', 'A')->first();

        $this->actingAs($this->admin)->patch(route('admin.anuncios.subir', $c->id))->assertRedirect(route('admin.anuncios.index'));
        $this->assertSame(['A', 'C', 'B'], $this->titulosEnOrden());

        $this->actingAs($this->admin)->patch(route('admin.anuncios.bajar', $a->id));
        $this->assertSame(['C', 'A', 'B'], $this->titulosEnOrden());
    }

    public function test_el_primero_no_sube_y_el_ultimo_no_baja(): void
    {
        foreach (['A', 'B'] as $i => $t) {
            $this->anuncio(['titulo' => $t, 'orden' => $i + 1]);
        }

        $this->actingAs($this->admin)->patch(route('admin.anuncios.subir', Anuncio::where('titulo', 'A')->first()->id));
        $this->actingAs($this->admin)->patch(route('admin.anuncios.bajar', Anuncio::where('titulo', 'B')->first()->id));

        $this->assertSame(['A', 'B'], $this->titulosEnOrden());
        $this->assertSame([1, 2], Anuncio::ordenados()->pluck('orden')->all());
    }

    public function test_mover_renumera_y_corrige_empates_y_huecos(): void
    {
        $this->anuncio(['titulo' => 'A', 'orden' => 0]);
        $this->anuncio(['titulo' => 'B', 'orden' => 0]);
        $this->anuncio(['titulo' => 'C', 'orden' => 7]);

        $this->actingAs($this->admin)->patch(route('admin.anuncios.bajar', Anuncio::where('titulo', 'A')->first()->id));

        $this->assertSame(['B', 'A', 'C'], $this->titulosEnOrden());
        $this->assertSame([1, 2, 3], Anuncio::ordenados()->pluck('orden')->all());
    }

    public function test_mover_un_anuncio_que_no_existe_da_404(): void
    {
        $this->actingAs($this->admin)->patch(route('admin.anuncios.subir', 999))->assertNotFound();
        $this->actingAs($this->admin)->patch(route('admin.anuncios.bajar', 999))->assertNotFound();
    }

    public function test_la_lista_del_panel_sigue_el_orden_del_carrusel(): void
    {
        $this->anuncio(['titulo' => 'Segundo lugar', 'orden' => 2]);
        $this->anuncio(['titulo' => 'Primer lugar', 'orden' => 1]);

        $html = $this->actingAs($this->admin)->get(route('admin.anuncios.index'))->assertOk()->getContent();

        $this->assertLessThan(strpos($html, 'Segundo lugar'), strpos($html, 'Primer lugar'));
        $this->assertStringContainsString(route('admin.anuncios.subir', Anuncio::where('titulo', 'Primer lugar')->first()->id), $html);
    }

    public function test_solo_el_admin_puede_reordenar(): void
    {
        $anuncio = $this->anuncio();

        $this->patch(route('admin.anuncios.subir', $anuncio->id))->assertRedirect('/login');
        foreach (['asistente', 'paciente'] as $rol) {
            $this->actingAs($this->crearUsuario($rol))->patch(route('admin.anuncios.subir', $anuncio->id))->assertForbidden();
            $this->actingAs($this->crearUsuario($rol))->patch(route('admin.anuncios.bajar', $anuncio->id))->assertForbidden();
        }
    }
}
