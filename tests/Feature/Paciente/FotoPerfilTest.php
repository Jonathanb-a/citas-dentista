<?php

namespace Tests\Feature\Paciente;

use App\Models\User;
use App\Services\ImagenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ImagenesDePrueba as Img;
use Tests\TestCase;

/** Foto de perfil del paciente: subir, cambiar, quitar, validación, autorización, límite y avatar. Todo ficticio */
class FotoPerfilTest extends TestCase
{
    use RefreshDatabase;

    /** @var string[] */
    private array $logs = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('imagenes');
        Event::listen(MessageLogged::class, fn (MessageLogged $e) => $this->logs[] = $e->level . ' ' . $e->message . ' ' . json_encode($e->context));
    }

    // 'role' no está en $fillable: se asigna explícitamente
    private function usuario(string $role = 'paciente', array $datos = []): User
    {
        $user = User::factory()->make(array_merge(['name' => 'María', 'apellidos' => 'González', 'activo' => true], $datos));
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function png(string $nombre = 'foto.png'): UploadedFile
    {
        return Img::pngSubido($nombre);
    }

    private function subir(User $usuario, ?UploadedFile $archivo = null)
    {
        return $this->actingAs($usuario)->post(route('paciente.perfil.foto'), ['foto' => $archivo ?? $this->png()]);
    }

    private function existe(?string $ruta): bool
    {
        return $ruta !== null && Storage::disk('imagenes')->exists($ruta);
    }

    private function exigirGd(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('La extensión GD no está cargada.');
        }
    }

    // ---------- Subir, cambiar y quitar ----------

    public function test_el_paciente_sube_su_foto_y_queda_guardada_con_nombre_aleatorio(): void
    {
        $paciente = $this->usuario();

        $this->subir($paciente)->assertRedirect(route('paciente.perfil.index'))->assertSessionHas('success');

        $ruta = $paciente->fresh()->foto;
        $this->assertMatchesRegularExpression('#^perfiles/[a-z0-9]+\.png$#', $ruta);
        $this->assertStringNotContainsString('foto', $ruta);          // no conserva el nombre original
        $this->assertTrue($this->existe($ruta));
        $this->assertSame(app(ImagenService::class)->url($ruta), $paciente->fresh()->fotoUrl());
    }

    public function test_cambiar_la_foto_borra_la_anterior(): void
    {
        $paciente = $this->usuario();
        $this->subir($paciente);
        $vieja = $paciente->fresh()->foto;

        $this->subir($paciente, $this->png('otra.png'))->assertSessionHas('success');

        $nueva = $paciente->fresh()->foto;
        $this->assertNotSame($vieja, $nueva);
        $this->assertTrue($this->existe($nueva));
        $this->assertFalse($this->existe($vieja), 'La foto anterior debió borrarse');
        $this->assertCount(1, Storage::disk('imagenes')->allFiles('perfiles'));
    }

    public function test_quitar_la_foto_borra_el_archivo_y_la_columna(): void
    {
        $paciente = $this->usuario();
        $this->subir($paciente);
        $ruta = $paciente->fresh()->foto;

        $this->actingAs($paciente)->delete(route('paciente.perfil.foto.quitar'))
            ->assertRedirect(route('paciente.perfil.index'))->assertSessionHas('success');

        $this->assertNull($paciente->fresh()->foto);
        $this->assertFalse($this->existe($ruta));
        $this->assertSame([], Storage::disk('imagenes')->allFiles('perfiles'));
    }

    public function test_quitar_sin_foto_no_falla(): void
    {
        $this->actingAs($this->usuario())->delete(route('paciente.perfil.foto.quitar'))->assertRedirect()->assertSessionHas('success');
    }

    public function test_los_tres_formatos_validos_se_aceptan_con_su_extension_real(): void
    {
        foreach ([['f.png', Img::png(), 'png'], ['f.jpg', Img::jpg(), 'jpg'], ['f.webp', Img::webp(), 'webp']] as [$nombre, $bytes, $ext]) {
            $paciente = $this->usuario();
            $this->subir($paciente, Img::archivo($nombre, $bytes))->assertSessionHasNoErrors();
            $this->assertStringEndsWith(".$ext", $paciente->fresh()->foto);
        }
    }

    // ---------- Recorte cuadrado de 512 px (GD) ----------

    public function test_una_foto_grande_y_rectangular_se_recorta_a_cuadrado_de_512(): void
    {
        $this->exigirGd();
        $paciente = $this->usuario();
        $this->subir($paciente, Img::archivo('ancha.png', Img::conGd(1600, 900)));

        [$ancho, $alto] = getimagesizefromstring(Storage::disk('imagenes')->get($paciente->fresh()->foto));

        $this->assertSame([512, 512], [$ancho, $alto]);
    }

    public function test_una_foto_chica_se_recorta_a_cuadrado_sin_ampliarla(): void
    {
        $this->exigirGd();
        $paciente = $this->usuario();
        $this->subir($paciente, Img::archivo('chica.png', Img::conGd(300, 200)));

        [$ancho, $alto] = getimagesizefromstring(Storage::disk('imagenes')->get($paciente->fresh()->foto));

        $this->assertSame([200, 200], [$ancho, $alto]);
    }

    public function test_el_recorte_conserva_el_centro_y_quita_los_metadatos(): void
    {
        $this->exigirGd();
        $paciente = $this->usuario();
        // Izquierda roja, derecha azul: el centro del cuadrado queda en el límite
        $this->subir($paciente, Img::archivo('rojo-azul.jpg', Img::conExif(Img::rojoYAzul(1000, 500), 1, 'UBICACION-SECRETA')));

        $bytes = Storage::disk('imagenes')->get($paciente->fresh()->foto);

        $this->assertStringNotContainsString('UBICACION-SECRETA', $bytes);
        $imagen = imagecreatefromstring($bytes);
        $this->assertSame([500, 500], [imagesx($imagen), imagesy($imagen)]);       // cuadrado de 500 px (la altura)
        $izquierda = imagecolorat($imagen, 5, 250);
        $derecha = imagecolorat($imagen, 494, 250);
        $this->assertGreaterThan(200, ($izquierda >> 16) & 0xFF);                  // el centro queda entre el rojo...
        $this->assertLessThan(60, $izquierda & 0xFF);
        $this->assertGreaterThan(200, $derecha & 0xFF);                            // ...y el azul
        $this->assertLessThan(60, ($derecha >> 16) & 0xFF);
    }

    // ---------- Validación ----------

    public static function archivosInvalidos(): array
    {
        return [
            'texto con extensión jpg' => ['falso.jpg', 'Esto no es una imagen, es texto.'],
            'html con extensión png' => ['falso.png', '<html><script>alert(1)</script></html>'],
            'php disfrazado' => ['falso.webp', '<?php echo "x";'],
            'gif' => ['animada.gif', base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7')],
            'svg' => ['dibujo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'],
            'pdf' => ['doc.pdf', "%PDF-1.4\n%%EOF"],
        ];
    }

    /** @dataProvider archivosInvalidos */
    #[\PHPUnit\Framework\Attributes\DataProvider('archivosInvalidos')]
    public function test_rechaza_archivos_que_no_son_jpg_png_o_webp_reales(string $nombre, string $contenido): void
    {
        $paciente = $this->usuario();

        $this->subir($paciente, Img::archivo($nombre, $contenido))->assertSessionHasErrors('foto', errorBag: 'foto');

        $this->assertNull($paciente->fresh()->foto);
        $this->assertSame([], Storage::disk('imagenes')->allFiles());
    }

    public function test_una_imagen_real_con_extension_enganosa_se_guarda_con_su_extension_real(): void
    {
        $paciente = $this->usuario();

        $this->subir($paciente, Img::archivo('foto.jpg', Img::png()));   // es un PNG llamado .jpg

        $this->assertStringEndsWith('.png', (string) $paciente->fresh()->foto);
    }

    public function test_rechaza_mas_de_5_mb(): void
    {
        $paciente = $this->usuario();

        $this->subir($paciente, Img::archivo('pesada.png', Img::png(1, 1, 5 * 1024 * 1024 + 10)))
            ->assertSessionHasErrors('foto', errorBag: 'foto');

        $this->assertNull($paciente->fresh()->foto);
    }

    public function test_sin_archivo_da_error(): void
    {
        $this->actingAs($this->usuario())->post(route('paciente.perfil.foto'), [])->assertSessionHasErrors('foto', errorBag: 'foto');
    }

    public function test_un_error_de_validacion_no_borra_la_foto_actual(): void
    {
        $paciente = $this->usuario();
        $this->subir($paciente);
        $ruta = $paciente->fresh()->foto;

        $this->subir($paciente, Img::archivo('falso.jpg', 'texto'))->assertSessionHasErrors('foto', errorBag: 'foto');

        $this->assertSame($ruta, $paciente->fresh()->foto);
        $this->assertTrue($this->existe($ruta));
    }

    // ---------- Autorización ----------

    public function test_cada_paciente_solo_toca_su_foto_y_no_hay_id_en_la_ruta(): void
    {
        $yo = $this->usuario(datos: ['email' => 'yo@ejemplo.test']);
        $otro = $this->usuario(datos: ['email' => 'otro@ejemplo.test']);
        $this->subir($otro);
        $fotoOtro = $otro->fresh()->foto;

        $this->subir($yo);
        $this->actingAs($yo)->delete(route('paciente.perfil.foto.quitar'));

        $this->assertNull($yo->fresh()->foto);
        $this->assertSame($fotoOtro, $otro->fresh()->foto);                        // la del otro intacta
        $this->assertTrue($this->existe($fotoOtro));
        $this->assertStringNotContainsString('{', route('paciente.perfil.foto'));
        // Intentar mandar un id ajeno no cambia nada
        $this->actingAs($yo)->post(route('paciente.perfil.foto'), ['foto' => $this->png(), 'user_id' => $otro->id, 'id' => $otro->id]);
        $this->assertSame($fotoOtro, $otro->fresh()->foto);
    }

    public function test_solo_los_pacientes_con_sesion_pueden_usar_las_rutas(): void
    {
        $this->post(route('paciente.perfil.foto'), ['foto' => $this->png()])->assertRedirect('/login');
        $this->delete(route('paciente.perfil.foto.quitar'))->assertRedirect('/login');

        foreach (['admin', 'asistente'] as $rol) {
            $usuario = $this->usuario($rol);
            $this->subir($usuario)->assertForbidden();
            $this->actingAs($usuario)->delete(route('paciente.perfil.foto.quitar'))->assertForbidden();
            $this->assertNull($usuario->fresh()->foto);
        }
        $this->assertSame([], Storage::disk('imagenes')->allFiles());
    }

    public function test_los_pacientes_del_chat_no_participan(): void
    {
        $delChat = $this->usuario(datos: ['email' => 'chat-abc@' . config('chat.agenda.correo_dominio')]);

        $this->subir($delChat)->assertForbidden();
        $this->actingAs($delChat)->delete(route('paciente.perfil.foto.quitar'))->assertForbidden();

        $this->assertNull($delChat->fresh()->foto);
        $this->assertSame([], Storage::disk('imagenes')->allFiles());
    }

    public function test_foto_no_es_asignable_en_masa(): void
    {
        $paciente = $this->usuario();
        $paciente->update(['foto' => 'perfiles/intruso.png']);

        $this->assertNull($paciente->fresh()->foto);

        // Ni por el formulario de datos del perfil
        $this->actingAs($paciente)->put(route('paciente.perfil.datos'), [
            'name' => 'María', 'apellidos' => 'González', 'email' => $paciente->email, 'foto' => 'perfiles/intruso.png',
        ]);
        $this->assertNull($paciente->fresh()->foto);
    }

    // ---------- Límite de subidas ----------

    public function test_limite_de_5_operaciones_por_hora_por_usuario(): void
    {
        $this->assertSame(5, config('imagenes.foto_perfil_limite_hora'));
        config(['imagenes.foto_perfil_limite_hora' => 3]);
        $paciente = $this->usuario(datos: ['email' => 'uno@ejemplo.test']);
        $otro = $this->usuario(datos: ['email' => 'dos@ejemplo.test']);

        $this->subir($paciente)->assertRedirect(route('paciente.perfil.index'));
        $this->subir($paciente)->assertRedirect(route('paciente.perfil.index'));
        $this->actingAs($paciente)->delete(route('paciente.perfil.foto.quitar'))->assertRedirect(route('paciente.perfil.index'));
        $this->subir($paciente)->assertStatus(429);
        $this->actingAs($paciente)->delete(route('paciente.perfil.foto.quitar'))->assertStatus(429);

        $this->subir($otro)->assertRedirect(route('paciente.perfil.index'));   // el límite es por usuario
    }

    // ---------- Disco no disponible ----------

    public function test_si_el_disco_falla_avisa_con_amabilidad_el_perfil_sigue_y_el_log_no_trae_rutas(): void
    {
        $paciente = $this->usuario();
        $this->subir($paciente);
        $ruta = $paciente->fresh()->foto;

        // Un disco cuyo guardado siempre falla (como un Volume sin montar o sin permisos): su raíz cuelga de un archivo
        $archivo = tempnam(sys_get_temp_dir(), 'roto');
        config([
            'filesystems.disks.imagenes_roto' => ['driver' => 'local', 'root' => $archivo . '/data/imagenes/secreto', 'url' => '/imagenes', 'throw' => false],
            'imagenes.disk' => 'imagenes_roto',
        ]);

        $this->subir($paciente, $this->png('nueva.png'))
            ->assertRedirect()->assertSessionHas('error', fn ($m) => str_contains($m, 'tu perfil sigue funcionando'));

        $this->assertSame($ruta, $paciente->fresh()->foto);             // la anterior queda
        // El perfil y el Inicio siguen funcionando, con las iniciales en lugar de la foto
        $this->actingAs($paciente)->get(route('paciente.perfil.index'))->assertOk()->assertSee('Mis datos')->assertSee('MG');
        $this->get(route('paciente.dashboard'))->assertOk()->assertSee('MG');
        $todo = implode("\n", $this->logs);
        $this->assertStringContainsString('Foto de perfil: no se pudo guardar', $todo);
        $this->assertStringNotContainsString('/data/imagenes', $todo);
        $this->assertStringNotContainsString('secreto', $todo);
        @unlink($archivo);
    }

    // ---------- Avatar ----------

    public function test_sin_foto_el_perfil_y_el_menu_muestran_las_iniciales(): void
    {
        $paciente = $this->usuario(datos: ['name' => 'María', 'apellidos' => 'González']);

        $this->actingAs($paciente)->get(route('paciente.perfil.index'))->assertOk()
            ->assertSee('MG')->assertDontSee('/imagenes/perfiles', false)->assertSee('Subir foto');
        $this->get(route('paciente.dashboard'))->assertOk()->assertSee('MG');
    }

    public function test_con_foto_el_avatar_es_una_imagen_en_el_menu_el_perfil_y_la_ficha_de_la_doctora(): void
    {
        $paciente = $this->usuario();
        $this->subir($paciente);
        $url = $paciente->fresh()->fotoUrl();

        $this->actingAs($paciente)->get(route('paciente.perfil.index'))->assertOk()
            ->assertSee('<img src="' . $url . '"', false)->assertSee('Cambiar foto')->assertSee('Quitar foto');
        $this->get(route('paciente.dashboard'))->assertSee('<img src="' . $url . '"', false);

        $this->actingAs($this->usuario('admin'))->get(route('admin.pacientes.show', $paciente->id))->assertOk()
            ->assertSee('<img src="' . $url . '"', false);
    }

    public function test_las_iniciales_y_el_nombre_se_escapan(): void
    {
        $paciente = $this->usuario(datos: ['name' => '<script>alert(1)</script>Ana', 'apellidos' => '<b>Ruiz']);

        $respuesta = $this->actingAs($paciente)->get(route('paciente.perfil.index'))->assertOk();

        $respuesta->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('<b>Ruiz', false);
        $this->assertSame('SB', $paciente->iniciales());                    // solo letras: la primera de cada campo
    }

    public function test_las_iniciales_nunca_quedan_vacias(): void
    {
        $this->assertSame('M', $this->usuario(datos: ['name' => 'María', 'apellidos' => ''])->iniciales());
        $this->assertSame('ÁÑ', $this->usuario(datos: ['name' => 'álvaro', 'apellidos' => 'ñandú'])->iniciales());
        $this->assertSame('?', $this->usuario(datos: ['name' => '123', 'apellidos' => '!!'])->iniciales());
    }

    public function test_una_ruta_que_no_es_nuestra_no_se_convierte_en_url(): void
    {
        $paciente = $this->usuario();
        $paciente->foto = 'http://sitio-ajeno.test/foto.png';
        $paciente->save();

        $this->assertNull($paciente->fresh()->fotoUrl());
    }

    // ---------- Logs de ImagenService ----------

    public function test_los_logs_de_imagenservice_ya_no_llevan_rutas_ni_mensajes(): void
    {
        app(ImagenService::class)->eliminar('../../etc/passwd');      // ruta inválida

        $todo = implode("\n", $this->logs);
        $this->assertStringContainsString('ruta inválida', $todo);
        $this->assertStringNotContainsString('passwd', $todo);
    }
}
