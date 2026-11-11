<?php

namespace Tests\Feature\Testimonios;

use App\Models\Testimonio;

/** Moderación de la doctora: filtros, aprobar, ocultar, restaurar, destacar y eliminar */
class TestimoniosAdminTest extends TestimoniosTestCase
{
    private function doctora()
    {
        return $this->usuario('admin');
    }

    public function test_solo_la_doctora_entra_a_la_moderacion(): void
    {
        $this->actingAs($this->usuario('asistente'))->get(route('admin.testimonios.index'))->assertForbidden();
        $this->actingAs($this->doctora())->get(route('admin.testimonios.index'))->assertOk()->assertSee('Testimonios de pacientes');
    }

    public function test_sin_sesion_va_al_login(): void
    {
        $this->get(route('admin.testimonios.index'))->assertRedirect('/login');
    }

    public function test_la_lista_filtra_por_estado_y_empieza_en_pendientes(): void
    {
        $this->testimonio($this->paciente(['name' => 'Pendiente']), 'pendiente', ['texto' => 'Texto del testimonio pendiente de revisión.']);
        $this->testimonio($this->paciente(['name' => 'Aprobada']), 'aprobado', ['texto' => 'Texto del testimonio aprobado y publicado.']);
        $this->testimonio($this->paciente(['name' => 'Oculta']), 'oculto', ['texto' => 'Texto del testimonio oculto por la doctora.']);
        $admin = $this->doctora();

        $this->actingAs($admin)->get(route('admin.testimonios.index'))
            ->assertSee('Texto del testimonio pendiente')->assertDontSee('Texto del testimonio aprobado')->assertDontSee('Texto del testimonio oculto');
        $this->actingAs($admin)->get(route('admin.testimonios.index', ['estado' => 'aprobado']))
            ->assertSee('Texto del testimonio aprobado')->assertDontSee('Texto del testimonio pendiente');
        $this->actingAs($admin)->get(route('admin.testimonios.index', ['estado' => 'oculto']))
            ->assertSee('Texto del testimonio oculto')->assertDontSee('Texto del testimonio aprobado');
        // Un estado inventado cae en pendientes
        $this->actingAs($admin)->get(route('admin.testimonios.index', ['estado' => 'x; drop table']))
            ->assertOk()->assertSee('Texto del testimonio pendiente');
    }

    public function test_la_lista_muestra_el_nombre_completo_y_como_se_vera_en_la_pagina(): void
    {
        $this->testimonio($this->paciente(['name' => 'María', 'apellidos' => 'González López']));

        $this->actingAs($this->doctora())->get(route('admin.testimonios.index'))
            ->assertSee('María González López')->assertSee('Se verá como: María G.');
    }

    public function test_aprobar_publica_y_solo_funciona_desde_pendiente(): void
    {
        $t = $this->testimonio($this->paciente());
        $admin = $this->doctora();

        $this->actingAs($admin)->patch(route('admin.testimonios.aprobar', $t->id))->assertSessionHas('success');
        $t->refresh();
        $this->assertSame('aprobado', $t->estado);
        $this->assertNotNull($t->aprobado_en);

        $this->actingAs($admin)->patch(route('admin.testimonios.aprobar', $t->id))->assertSessionHas('error');

        $oculto = $this->testimonio($this->paciente(), 'oculto');
        $this->actingAs($admin)->patch(route('admin.testimonios.aprobar', $oculto->id))->assertSessionHas('error');
        $this->assertSame('oculto', $oculto->fresh()->estado);   // un oculto se restaura primero
    }

    public function test_ocultar_quita_lo_destacado_y_restaurar_lo_deja_pendiente(): void
    {
        $t = $this->testimonio($this->paciente(), 'aprobado', ['destacado' => true]);
        $admin = $this->doctora();

        $this->actingAs($admin)->patch(route('admin.testimonios.ocultar', $t->id))->assertSessionHas('success');
        $t->refresh();
        $this->assertSame('oculto', $t->estado);
        $this->assertFalse($t->destacado);
        $this->assertNull($t->aprobado_en);

        $this->actingAs($admin)->patch(route('admin.testimonios.ocultar', $t->id))->assertSessionHas('error');

        $this->actingAs($admin)->patch(route('admin.testimonios.restaurar', $t->id))->assertSessionHas('success');
        $this->assertSame('pendiente', $t->fresh()->estado);   // no se publica directo
        $this->actingAs($admin)->patch(route('admin.testimonios.restaurar', $t->id))->assertSessionHas('error');
    }

    public function test_se_puede_ocultar_un_pendiente(): void
    {
        $t = $this->testimonio($this->paciente());

        $this->actingAs($this->doctora())->patch(route('admin.testimonios.ocultar', $t->id))->assertSessionHas('success');

        $this->assertSame('oculto', $t->fresh()->estado);
    }

    public function test_destacar_solo_aprobados_y_hasta_el_maximo_configurable(): void
    {
        config(['testimonios.max_destacados' => 2]);
        $admin = $this->doctora();
        $a = $this->testimonio($this->paciente(), 'aprobado');
        $b = $this->testimonio($this->paciente(), 'aprobado');
        $c = $this->testimonio($this->paciente(), 'aprobado');
        $pendiente = $this->testimonio($this->paciente());

        $this->actingAs($admin)->patch(route('admin.testimonios.destacar', $a->id))->assertSessionHas('success');
        $this->actingAs($admin)->patch(route('admin.testimonios.destacar', $b->id))->assertSessionHas('success');
        $this->actingAs($admin)->patch(route('admin.testimonios.destacar', $c->id))->assertSessionHas('error');
        $this->assertSame(2, Testimonio::where('destacado', true)->count());
        $this->assertFalse($c->fresh()->destacado);

        $this->actingAs($admin)->patch(route('admin.testimonios.destacar', $pendiente->id))->assertSessionHas('error');
        $this->assertFalse($pendiente->fresh()->destacado);

        // Quitar uno libera el lugar
        $this->actingAs($admin)->patch(route('admin.testimonios.destacar', $a->id), ['quitar' => 1])->assertSessionHas('success');
        $this->actingAs($admin)->patch(route('admin.testimonios.destacar', $c->id))->assertSessionHas('success');
        $this->assertSame([$b->id, $c->id], Testimonio::where('destacado', true)->orderBy('id')->pluck('id')->all());
    }

    public function test_el_maximo_de_destacados_es_6_por_defecto(): void
    {
        $this->assertSame(6, config('testimonios.max_destacados'));
        $this->assertSame(12, config('testimonios.max_landing'));
        $this->assertSame(3, config('testimonios.promedio_minimo'));
    }

    public function test_eliminar_es_definitivo(): void
    {
        $paciente = $this->paciente();
        $t = $this->testimonio($paciente);

        $this->actingAs($this->doctora())->delete(route('admin.testimonios.destroy', $t->id))->assertSessionHas('success');

        $this->assertSame(0, Testimonio::count());
        $this->assertNotNull($paciente->fresh());
        $this->actingAs($this->doctora())->delete(route('admin.testimonios.destroy', $t->id))->assertNotFound();
    }

    public function test_un_testimonio_inexistente_da_404(): void
    {
        $this->actingAs($this->doctora())->patch(route('admin.testimonios.aprobar', 9999))->assertNotFound();
    }

    public function test_borrar_la_cuenta_del_paciente_borra_su_testimonio(): void
    {
        $paciente = $this->paciente();
        $this->testimonio($paciente);

        $paciente->citas()->delete();
        $paciente->delete();

        $this->assertSame(0, Testimonio::count());
    }
}
