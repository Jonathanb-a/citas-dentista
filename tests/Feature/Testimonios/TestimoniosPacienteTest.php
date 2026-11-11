<?php

namespace Tests\Feature\Testimonios;

use App\Models\Testimonio;
use App\Services\TestimonioService;
use Illuminate\Support\Facades\Gate;

/** Elegibilidad, un testimonio por paciente, validación, estados y autorización del paciente */
class TestimoniosPacienteTest extends TestimoniosTestCase
{
    // ---------- Elegibilidad ----------

    public function test_un_paciente_sin_citas_no_puede_opinar_y_se_le_explica_por_que(): void
    {
        $paciente = $this->usuario();

        $this->assertFalse(app(TestimonioService::class)->puedeOpinar($paciente));
        $this->actingAs($paciente)->get(route('paciente.testimonio.index'))
            ->assertOk()->assertSee('después de tu primera cita atendida')->assertDontSee('Enviar mi opinión');
        $this->actingAs($paciente)->post(route('paciente.testimonio.store'), $this->datos())->assertForbidden();
        $this->assertSame(0, Testimonio::count());
    }

    public function test_solo_una_cita_atendida_habilita_opinar(): void
    {
        foreach (['pendiente', 'confirmada', 'cancelada'] as $status) {
            $paciente = $this->usuario();
            $this->cita($paciente, $status);
            $this->assertFalse(app(TestimonioService::class)->puedeOpinar($paciente), "cita $status");
        }

        $this->assertTrue(app(TestimonioService::class)->puedeOpinar($this->paciente()));
    }

    public function test_un_paciente_del_chat_no_participa_aunque_tenga_cita_atendida(): void
    {
        $delChat = $this->paciente(['email' => 'chat-abc@' . config('chat.agenda.correo_dominio')]);

        $this->assertFalse(app(TestimonioService::class)->puedeOpinar($delChat));
        $this->actingAs($delChat)->post(route('paciente.testimonio.store'), $this->datos())->assertForbidden();
        $this->assertSame(0, Testimonio::count());
    }

    public function test_una_cuenta_inactiva_no_puede_opinar(): void
    {
        $inactivo = $this->paciente(['activo' => false]);

        $this->assertFalse(app(TestimonioService::class)->puedeOpinar($inactivo));
    }

    public function test_admin_y_asistente_no_entran_a_mi_opinion(): void
    {
        foreach (['admin', 'asistente'] as $rol) {
            $usuario = $this->usuario($rol);
            $this->cita($usuario);
            $this->actingAs($usuario)->get(route('paciente.testimonio.index'))->assertForbidden();
            $this->actingAs($usuario)->post(route('paciente.testimonio.store'), $this->datos())->assertForbidden();
        }
        $this->assertSame(0, Testimonio::count());
    }

    public function test_un_visitante_sin_sesion_va_al_login(): void
    {
        $this->get(route('paciente.testimonio.index'))->assertRedirect('/login');
        $this->post(route('paciente.testimonio.store'), $this->datos())->assertRedirect('/login');
    }

    // ---------- Crear ----------

    public function test_crear_deja_el_testimonio_pendiente_y_no_destacado(): void
    {
        $paciente = $this->paciente();

        $this->actingAs($paciente)->post(route('paciente.testimonio.store'), $this->datos(['calificacion' => 4]))
            ->assertRedirect(route('paciente.testimonio.index'))->assertSessionHas('success');

        $t = Testimonio::firstOrFail();
        $this->assertSame($paciente->id, $t->paciente_id);
        $this->assertSame(4, $t->calificacion);
        $this->assertSame(self::TEXTO, $t->texto);
        $this->assertTrue($t->autorizado);
        $this->assertSame('pendiente', $t->estado);
        $this->assertFalse($t->destacado);
        $this->assertNull($t->aprobado_en);
    }

    public function test_el_paciente_no_puede_fijar_estado_destacado_ni_paciente_desde_la_peticion(): void
    {
        $paciente = $this->paciente();
        $otro = $this->paciente();

        $this->actingAs($paciente)->post(route('paciente.testimonio.store'), $this->datos([
            'estado' => 'aprobado', 'destacado' => 1, 'paciente_id' => $otro->id, 'aprobado_en' => now()->toDateTimeString(),
        ]))->assertRedirect();

        $t = Testimonio::firstOrFail();
        $this->assertSame($paciente->id, $t->paciente_id);
        $this->assertSame('pendiente', $t->estado);
        $this->assertFalse($t->destacado);
        $this->assertNull($t->aprobado_en);
    }

    public function test_solo_un_testimonio_por_paciente(): void
    {
        $paciente = $this->paciente();

        $this->actingAs($paciente)->post(route('paciente.testimonio.store'), $this->datos())->assertSessionHas('success');
        $this->actingAs($paciente)->post(route('paciente.testimonio.store'), $this->datos(['texto' => 'Otro texto distinto que también es largo.']))->assertForbidden();

        $this->assertSame(1, Testimonio::count());
        $this->assertSame(self::TEXTO, Testimonio::first()->texto);
        // Ante una carrera, el índice único lo rechaza y el servicio lo resuelve sin error
        $this->assertNull(app(TestimonioService::class)->crear($paciente, 5, self::TEXTO));
        $this->assertSame(1, Testimonio::count());
    }

    // ---------- Validación ----------

    public static function entradasInvalidas(): array
    {
        $texto = 'La atención fue muy amable y me explicaron todo.';

        return [
            'texto de 19 caracteres' => [['texto' => str_repeat('a', 19)], 'texto'],
            'texto de 301 caracteres' => [['texto' => str_repeat('a', 301)], 'texto'],
            'texto vacío' => [['texto' => ''], 'texto'],
            'texto arreglo' => [['texto' => [$texto]], 'texto'],
            'solo enlace' => [['texto' => 'https://sitio-ejemplo.test/promocion-increible-hoy'], 'texto'],
            'texto corto más un enlace largo' => [['texto' => 'Muy bien. https://sitio-ejemplo.test/promocion-increible-hoy'], 'texto'],
            'calificación 0' => [['calificacion' => 0], 'calificacion'],
            'calificación 6' => [['calificacion' => 6], 'calificacion'],
            'calificación con texto' => [['calificacion' => 'cinco'], 'calificacion'],
            'calificación decimal' => [['calificacion' => '4.5'], 'calificacion'],
            'sin calificación' => [['calificacion' => null], 'calificacion'],
            'sin autorización' => [['autorizo' => null], 'autorizo'],
            'autorización en 0' => [['autorizo' => '0'], 'autorizo'],
        ];
    }

    /** @dataProvider entradasInvalidas */
    #[\PHPUnit\Framework\Attributes\DataProvider('entradasInvalidas')]
    public function test_rechaza_entradas_invalidas(array $cambios, string $campo): void
    {
        $this->actingAs($this->paciente())->post(route('paciente.testimonio.store'), $this->datos($cambios))->assertSessionHasErrors($campo);

        $this->assertSame(0, Testimonio::count());
    }

    public function test_los_limites_de_longitud_son_inclusivos(): void
    {
        foreach ([20, 300] as $largo) {
            $paciente = $this->paciente();
            $this->actingAs($paciente)->post(route('paciente.testimonio.store'), $this->datos(['texto' => str_repeat('a', $largo)]))->assertSessionHasNoErrors();
            $this->assertSame($largo, mb_strlen($paciente->testimonio->texto));
        }
    }

    public function test_el_texto_se_limpia_de_invisibles_enlaces_y_marcado_antes_de_guardarse(): void
    {
        $paciente = $this->paciente();
        $sucio = "Excelente\u{200B} atención <b>muy</b> amable,\u{202E} visita https://sitio-ejemplo.test/x o www.otro-ejemplo.test {oferta} `código` [enlace] para todos";

        $this->actingAs($paciente)->post(route('paciente.testimonio.store'), $this->datos(['texto' => $sucio]))->assertSessionHasNoErrors();

        $guardado = $paciente->testimonio->texto;
        foreach (["\u{200B}", "\u{202E}", '<', '>', 'https', 'www.', '{', '}', '`', '[', ']'] as $malo) {
            $this->assertStringNotContainsString($malo, $guardado, $malo);
        }
        $this->assertStringContainsString('Excelente atención', $guardado);
    }

    public function test_el_minimo_se_cuenta_despues_de_limpiar(): void
    {
        // 25 caracteres, pero 15 son enlace: queda de 10
        $this->actingAs($this->paciente())->post(route('paciente.testimonio.store'), $this->datos(['texto' => 'Muy bien https://a.test/bb']))
            ->assertSessionHasErrors('texto');
    }

    // ---------- Editar y borrar ----------

    public function test_editar_un_testimonio_aprobado_lo_devuelve_a_pendiente_sin_destacar(): void
    {
        $paciente = $this->paciente();
        $this->testimonio($paciente, 'aprobado', ['destacado' => true]);

        $this->actingAs($paciente)->put(route('paciente.testimonio.update'), $this->datos(['calificacion' => 3, 'texto' => 'Cambié de opinión: el tiempo de espera fue largo.']))
            ->assertRedirect(route('paciente.testimonio.index'))->assertSessionHas('success');

        $t = $paciente->testimonio->fresh();
        $this->assertSame('pendiente', $t->estado);
        $this->assertFalse($t->destacado);
        $this->assertNull($t->aprobado_en);
        $this->assertSame(3, $t->calificacion);
        $this->assertSame('Cambié de opinión: el tiempo de espera fue largo.', $t->texto);
    }

    public function test_editar_un_testimonio_oculto_lo_deja_en_revision(): void
    {
        $paciente = $this->paciente();
        $this->testimonio($paciente, 'oculto');

        $this->actingAs($paciente)->put(route('paciente.testimonio.update'), $this->datos())->assertSessionHas('success');

        $this->assertSame('pendiente', $paciente->testimonio->fresh()->estado);
    }

    public function test_editar_valida_igual_que_crear(): void
    {
        $paciente = $this->paciente();
        $this->testimonio($paciente, 'aprobado');

        $this->actingAs($paciente)->put(route('paciente.testimonio.update'), $this->datos(['texto' => 'corto']))->assertSessionHasErrors('texto');

        $this->assertSame('aprobado', $paciente->testimonio->fresh()->estado);   // nada cambió
    }

    public function test_sin_testimonio_no_hay_nada_que_editar_ni_borrar(): void
    {
        $paciente = $this->paciente();

        $this->actingAs($paciente)->put(route('paciente.testimonio.update'), $this->datos())->assertNotFound();
        $this->actingAs($paciente)->delete(route('paciente.testimonio.destroy'))->assertNotFound();
    }

    public function test_el_paciente_borra_el_suyo_de_forma_definitiva_y_no_el_de_otro(): void
    {
        $paciente = $this->paciente();
        $otro = $this->paciente();
        $this->testimonio($paciente);
        $ajeno = $this->testimonio($otro);

        $this->actingAs($paciente)->delete(route('paciente.testimonio.destroy'))->assertRedirect()->assertSessionHas('success');

        $this->assertNull(Testimonio::where('paciente_id', $paciente->id)->first());
        $this->assertNotNull($ajeno->fresh());
        // Después de borrar puede volver a opinar
        $this->actingAs($paciente)->post(route('paciente.testimonio.store'), $this->datos())->assertSessionHas('success');
    }

    // ---------- Autorización por política ----------

    public function test_la_politica_solo_deja_tocar_el_propio(): void
    {
        $dueno = $this->paciente();
        $otro = $this->paciente();
        $t = $this->testimonio($dueno);

        $this->assertTrue(Gate::forUser($dueno)->allows('update', $t));
        $this->assertTrue(Gate::forUser($dueno)->allows('delete', $t));
        $this->assertTrue(Gate::forUser($otro)->denies('update', $t));
        $this->assertTrue(Gate::forUser($otro)->denies('delete', $t));
        $this->assertTrue(Gate::forUser($this->usuario('admin'))->denies('update', $t));
        // Crear: elegible y sin testimonio previo
        $this->assertTrue(Gate::forUser($otro)->allows('create', Testimonio::class));
        $this->assertTrue(Gate::forUser($dueno)->denies('create', Testimonio::class));
    }

    public function test_el_paciente_no_entra_a_la_moderacion(): void
    {
        $paciente = $this->paciente();
        $t = $this->testimonio($paciente);

        $this->actingAs($paciente)->get(route('admin.testimonios.index'))->assertForbidden();
        $this->actingAs($paciente)->patch(route('admin.testimonios.aprobar', $t->id))->assertForbidden();
        $this->actingAs($paciente)->patch(route('admin.testimonios.destacar', $t->id))->assertForbidden();
        $this->actingAs($paciente)->delete(route('admin.testimonios.destroy', $t->id))->assertForbidden();
        $this->assertSame('pendiente', $t->fresh()->estado);
    }

    // ---------- Límite de envíos ----------

    public function test_limite_de_envios_por_usuario_y_por_hora(): void
    {
        config(['testimonios.limite_hora' => 3]);
        $paciente = $this->paciente();
        $otro = $this->paciente();

        $this->actingAs($paciente)->post(route('paciente.testimonio.store'), $this->datos())->assertRedirect();
        $this->actingAs($paciente)->put(route('paciente.testimonio.update'), $this->datos())->assertRedirect();
        $this->actingAs($paciente)->put(route('paciente.testimonio.update'), $this->datos())->assertRedirect();
        $this->actingAs($paciente)->put(route('paciente.testimonio.update'), $this->datos())->assertStatus(429);
        // El límite es por usuario: otro paciente no se ve afectado
        $this->actingAs($otro)->post(route('paciente.testimonio.store'), $this->datos())->assertRedirect();
    }

    // ---------- Lo que ve el paciente ----------

    public function test_el_paciente_ve_su_estado_y_un_oculto_se_le_muestra_como_no_publicado(): void
    {
        foreach (['pendiente' => 'En revisión', 'aprobado' => 'Publicado', 'oculto' => 'No publicado'] as $estado => $etiqueta) {
            $paciente = $this->paciente();
            $this->testimonio($paciente, $estado);

            $respuesta = $this->actingAs($paciente)->get(route('paciente.testimonio.index'))->assertOk()->assertSee($etiqueta);
            if ($estado === 'oculto') {
                $respuesta->assertDontSee('oculto')->assertDontSee('En revisión');
            }
        }
    }

    public function test_el_formulario_trae_la_advertencia_y_la_casilla_obligatoria(): void
    {
        $this->actingAs($this->paciente())->get(route('paciente.testimonio.index'))->assertOk()
            ->assertSee('No escribas datos médicos ni de contacto')
            ->assertSee('Autorizo que se muestre mi nombre y mi opinión en la página.')
            ->assertSee('name="autorizo"', false)
            ;
    }
}
