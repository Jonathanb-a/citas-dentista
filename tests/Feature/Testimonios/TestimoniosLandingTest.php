<?php

namespace Tests\Feature\Testimonios;

use App\Models\LandingItem;
use App\Models\Testimonio;
use Illuminate\Support\Facades\Schema;

/** Qué ve la landing: solo aprobados, nombre con inicial, promedio y orden. Sin aprobados, idéntica al fixture */
class TestimoniosLandingTest extends TestimoniosTestCase
{
    private function aprobado(array $usuario = [], array $cambios = []): Testimonio
    {
        return $this->testimonio($this->paciente($usuario), 'aprobado', $cambios);
    }

    private function seccion(string $html): string
    {
        $inicio = strpos($html, 'id="testimonios"');
        $this->assertNotFalse($inicio, 'La landing no tiene la sección de testimonios.');

        return substr($html, $inicio, strpos($html, '</section>', $inicio) - $inicio);
    }

    // ---------- Sin aprobados, la landing no cambia ----------

    public function test_sin_testimonios_la_landing_es_identica_a_la_original(): void
    {
        $this->assertIgualALaLandingOriginal($this->get('/')->assertOk()->getContent());
    }

    public function test_pendientes_y_ocultos_no_cambian_la_landing(): void
    {
        $this->testimonio($this->paciente(), 'pendiente', ['texto' => 'Opinión pendiente que nadie debe ver todavía.']);
        $this->testimonio($this->paciente(), 'oculto', ['texto' => 'Opinión oculta que nadie debe ver tampoco.']);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertIgualALaLandingOriginal($html);
        $this->assertStringNotContainsString('Opinión pendiente', $html);
    }

    public function test_si_la_consulta_falla_la_landing_se_ve_como_la_original(): void
    {
        Schema::drop('testimonios');

        $this->assertIgualALaLandingOriginal($this->get('/')->assertOk()->getContent());
    }

    // ---------- Con aprobados ----------

    public function test_muestra_solo_los_aprobados_con_nombre_de_pila_e_inicial(): void
    {
        $this->aprobado(['name' => 'María', 'apellidos' => 'González López'], ['texto' => 'Opinión aprobada y publicada en la página.']);
        $this->testimonio($this->paciente(), 'pendiente', ['texto' => 'Opinión pendiente que nadie debe ver todavía.']);
        $this->testimonio($this->paciente(), 'oculto', ['texto' => 'Opinión oculta que nadie debe ver tampoco.']);

        $html = $this->get('/')->assertOk()->getContent();
        $seccion = $this->seccion($html);

        $this->assertStringContainsString('Opinión aprobada y publicada', $seccion);
        $this->assertStringContainsString('María G.', $seccion);
        $this->assertStringNotContainsString('González', $html);      // el apellido completo nunca sale
        $this->assertStringNotContainsString('Opinión pendiente', $html);
        $this->assertStringNotContainsString('Opinión oculta', $html);
        $this->assertStringContainsString('href="#testimonios"', $html);   // el menú enlaza la sección
    }

    public function test_el_nombre_sin_apellido_sale_solo_con_el_nombre(): void
    {
        $this->aprobado(['name' => 'Luz', 'apellidos' => '']);

        $this->assertStringContainsString('>Luz<', $this->seccion($this->get('/')->getContent()));
    }

    public function test_el_promedio_aparece_desde_el_minimo_y_cuenta_solo_aprobados(): void
    {
        $this->aprobado([], ['calificacion' => 5]);
        $this->aprobado([], ['calificacion' => 4]);
        $this->testimonio($this->paciente(), 'pendiente', ['calificacion' => 1]);
        $this->testimonio($this->paciente(), 'oculto', ['calificacion' => 1]);

        // Con 2 aprobados no hay promedio
        $this->assertStringNotContainsString('Calificación promedio', $this->get('/')->getContent());

        $this->aprobado([], ['calificacion' => 3]);
        $seccion = $this->seccion($this->get('/')->getContent());

        $this->assertStringContainsString('Calificación promedio: 4,0 de 5, según 3 opiniones', $seccion);
        $this->assertStringContainsString('4,0</strong> de 5 · 3 opiniones', $seccion);
    }

    public function test_el_minimo_del_promedio_es_configurable(): void
    {
        config(['testimonios.promedio_minimo' => 1]);
        $this->aprobado([], ['calificacion' => 5]);

        $this->assertStringContainsString('Calificación promedio: 5,0 de 5, según 1 opinión', $this->seccion($this->get('/')->getContent()));
    }

    public function test_el_promedio_cuenta_todos_los_aprobados_aunque_no_quepan_en_la_pagina(): void
    {
        foreach (range(1, 14) as $i) {
            $this->aprobado([], ['calificacion' => $i <= 7 ? 5 : 3]);
        }

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('Calificación promedio: 4,0 de 5, según 14 opiniones', $html);
        $this->assertSame(12, substr_count($this->seccion($html), '<blockquote'));
    }

    public function test_los_destacados_salen_primero_y_hay_hasta_12_en_total(): void
    {
        $viejo = $this->aprobado(['name' => 'Destacada'], ['texto' => 'Opinión destacada de hace mucho tiempo.', 'destacado' => true]);
        $viejo->forceFill(['aprobado_en' => now()->subYear()])->save();
        foreach (range(1, 12) as $i) {
            $this->aprobado(['name' => "Persona$i"], ['texto' => "Opinión reciente número $i de la clínica."]);
        }

        $seccion = $this->seccion($this->get('/')->getContent());

        $this->assertSame(12, substr_count($seccion, '<blockquote'));
        $this->assertLessThan(strpos($seccion, 'Opinión reciente número 12 '), strpos($seccion, 'Opinión destacada'));
        $this->assertStringContainsString('Destacada G.', $seccion);
    }

    public function test_las_opiniones_manuales_conviven_y_no_cuentan_para_el_promedio(): void
    {
        LandingItem::create(['coleccion' => 'testimonios', 'orden' => 1, 'datos' => ['nombre' => 'Rosa P.', 'texto' => 'Opinión capturada a mano por la doctora.', 'calificacion' => 1, 'autorizado' => true]]);
        foreach ([5, 5, 5] as $c) {
            $this->aprobado([], ['calificacion' => $c]);
        }

        $seccion = $this->seccion($this->get('/')->getContent());

        $this->assertStringContainsString('Opinión capturada a mano', $seccion);
        $this->assertSame(4, substr_count($seccion, '<blockquote'));
        $this->assertStringContainsString('Calificación promedio: 5,0 de 5, según 3 opiniones', $seccion);   // la manual (1 estrella) no cuenta
    }

    public function test_el_tope_de_12_cuenta_las_manuales_y_las_de_pacientes_juntas(): void
    {
        foreach (range(1, 3) as $i) {
            LandingItem::create(['coleccion' => 'testimonios', 'orden' => $i, 'datos' => ['nombre' => "Manual $i", 'texto' => "Opinión manual número $i capturada por la doctora.", 'calificacion' => 5, 'autorizado' => true]]);
        }
        foreach (range(1, 12) as $i) {
            $this->aprobado(['name' => "Persona$i"], ['texto' => "Opinión reciente número $i de la clínica."]);
        }

        $this->assertSame(12, substr_count($this->seccion($this->get('/')->getContent()), '<blockquote'));
    }

    public function test_solo_manuales_no_muestran_promedio(): void
    {
        LandingItem::create(['coleccion' => 'testimonios', 'orden' => 1, 'datos' => ['nombre' => 'Rosa P.', 'texto' => 'Opinión capturada a mano por la doctora.', 'calificacion' => 5, 'autorizado' => true]]);

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('Opinión capturada a mano', $html);
        $this->assertStringNotContainsString('Calificación promedio', $html);
    }

    public function test_al_editar_un_aprobado_deja_de_verse_hasta_que_se_apruebe_de_nuevo(): void
    {
        $paciente = $this->paciente();
        $this->testimonio($paciente, 'aprobado', ['texto' => 'Opinión original que estaba publicada.']);
        $this->assertStringContainsString('Opinión original', $this->get('/')->getContent());

        $this->actingAs($paciente)->put(route('paciente.testimonio.update'), $this->datos(['texto' => 'Opinión editada que aún no revisa nadie.']));
        auth()->logout();

        $html = $this->get('/')->getContent();
        $this->assertStringNotContainsString('Opinión original', $html);
        $this->assertStringNotContainsString('Opinión editada', $html);
        $this->assertIgualALaLandingOriginal($html);
    }

    // ---------- XSS ----------

    public function test_todo_texto_se_escapa_en_landing_admin_y_panel_del_paciente(): void
    {
        $paciente = $this->paciente(['name' => '<img src=x onerror=alert(1)>', 'apellidos' => '<b>Ruiz']);
        // Directo a la base: aunque algo malicioso llegara guardado, se escapa al mostrarse
        $t = $this->testimonio($paciente, 'aprobado', ['texto' => '<script>alert("xss")</script> <b>negrita</b> &lt;i&gt; texto suficiente']);

        $landing = $this->get('/')->getContent();
        $this->assertStringNotContainsString('<script>alert', $landing);
        $this->assertStringNotContainsString('<b>negrita', $landing);
        $this->assertStringNotContainsString('<img src=x', $landing);
        $this->assertStringContainsString('&lt;script&gt;alert', $landing);

        $admin = $this->actingAs($this->usuario('admin'))->get(route('admin.testimonios.index', ['estado' => 'aprobado']))->getContent();
        $this->assertStringNotContainsString('<script>alert', $admin);
        $this->assertStringNotContainsString('<img src=x', $admin);
        $this->assertStringContainsString('&lt;img src=x', $admin);

        $propio = $this->actingAs($paciente)->get(route('paciente.testimonio.index'))->getContent();
        $this->assertStringNotContainsString('<script>alert', $propio);
        $this->assertStringNotContainsString('<b>negrita', $propio);
    }
}
