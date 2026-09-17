<?php

namespace Tests\Feature\Admin;

use App\Models\Expediente;
use App\Models\Odontograma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OdontogramaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    // 'role' no está en $fillable: se asigna explícitamente
    private function crearUsuario(string $role, array $datos = []): User
    {
        $user = User::factory()->make(array_merge(['apellidos' => 'Pérez', 'activo' => true], $datos));
        $user->role = $role;
        $user->save();

        return $user;
    }

    // Todos los dientes sanos, salvo los que se indiquen
    private function dientes(array $marcados = []): array
    {
        $dientes = [];
        foreach (Odontograma::numerosValidos() as $numero) {
            $dientes[$numero] = $marcados[$numero] ?? ['estado' => 'sano', 'nota' => ''];
        }

        return ['dientes' => $dientes];
    }

    public function test_guardar_marca_dientes_y_crea_el_expediente_si_no_existe(): void
    {
        $admin = $this->crearUsuario('admin', ['name' => 'Doctora', 'apellidos' => 'Ramírez']);
        $paciente = $this->crearUsuario('paciente');

        $this->actingAs($admin)->put(route('admin.pacientes.odontograma.update', $paciente->id), $this->dientes([
            16 => ['estado' => 'caries', 'nota' => 'Cara oclusal'],
            36 => ['estado' => 'obturado', 'nota' => ''],
            48 => ['estado' => 'ausente', 'nota' => 'Extraído en 2024'],
        ]))->assertRedirect(route('admin.pacientes.show', $paciente->id))
          ->assertSessionHasNoErrors();

        $expediente = $paciente->fresh()->expediente;
        $this->assertNotNull($expediente);

        // Solo se guardan los dientes marcados; los sanos no dejan registro
        $this->assertDatabaseCount('odontograma', 3);
        $this->assertDatabaseHas('odontograma', [
            'expediente_id' => $expediente->id,
            'numero_diente' => 16,
            'estado' => 'caries',
            'nota' => 'Cara oclusal',
            'actualizado_por' => $admin->id,
        ]);
        $this->assertDatabaseHas('odontograma', ['numero_diente' => 48, 'estado' => 'ausente']);
    }

    public function test_marcar_un_diente_como_sano_borra_su_registro(): void
    {
        $admin = $this->crearUsuario('admin');
        $paciente = $this->crearUsuario('paciente');
        $expediente = Expediente::create(['paciente_id' => $paciente->id]);
        $expediente->odontograma()->create(['numero_diente' => 16, 'estado' => 'caries', 'nota' => 'Vieja nota']);
        $expediente->odontograma()->create(['numero_diente' => 26, 'estado' => 'corona']);

        $this->actingAs($admin)->put(route('admin.pacientes.odontograma.update', $paciente->id), $this->dientes([
            26 => ['estado' => 'corona', 'nota' => ''],
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('odontograma', ['numero_diente' => 16]);
        $this->assertDatabaseHas('odontograma', ['numero_diente' => 26, 'estado' => 'corona']);
        $this->assertDatabaseCount('odontograma', 1);
    }

    public function test_guardar_dos_veces_el_mismo_diente_no_lo_duplica(): void
    {
        $admin = $this->crearUsuario('admin');
        $paciente = $this->crearUsuario('paciente');

        $this->actingAs($admin)->put(route('admin.pacientes.odontograma.update', $paciente->id), $this->dientes([
            21 => ['estado' => 'caries', 'nota' => 'Primera revisión'],
        ]));

        $this->actingAs($admin)->put(route('admin.pacientes.odontograma.update', $paciente->id), $this->dientes([
            21 => ['estado' => 'obturado', 'nota' => 'Ya tratado'],
        ]));

        $this->assertDatabaseCount('odontograma', 1);
        $this->assertDatabaseHas('odontograma', ['numero_diente' => 21, 'estado' => 'obturado', 'nota' => 'Ya tratado']);
    }

    public function test_rechaza_estado_invalido_numero_fuera_de_fdi_y_nota_larga(): void
    {
        $admin = $this->crearUsuario('admin');
        $paciente = $this->crearUsuario('paciente');

        // Estado que no existe
        $this->actingAs($admin)->put(route('admin.pacientes.odontograma.update', $paciente->id), $this->dientes([
            16 => ['estado' => 'astillado', 'nota' => ''],
        ]))->assertSessionHasErrorsIn('odontograma');

        // Número que no pertenece a la dentadura adulta (51 es temporal, fase 3)
        $this->actingAs($admin)->put(route('admin.pacientes.odontograma.update', $paciente->id), [
            'dientes' => [51 => ['estado' => 'caries', 'nota' => '']],
        ])->assertSessionHasErrorsIn('odontograma');

        // Nota demasiado larga
        $this->actingAs($admin)->put(route('admin.pacientes.odontograma.update', $paciente->id), $this->dientes([
            16 => ['estado' => 'caries', 'nota' => str_repeat('a', 501)],
        ]))->assertSessionHasErrorsIn('odontograma');

        $this->assertDatabaseCount('odontograma', 0);
    }

    public function test_la_pestana_muestra_el_diagrama_los_estados_y_la_leyenda(): void
    {
        $admin = $this->crearUsuario('admin', ['name' => 'Doctora', 'apellidos' => 'Ramírez']);
        $paciente = $this->crearUsuario('paciente');
        $expediente = Expediente::create(['paciente_id' => $paciente->id]);
        $expediente->odontograma()->create([
            'numero_diente' => 16,
            'estado' => 'caries',
            'nota' => 'Cara oclusal',
            'actualizado_por' => $admin->id,
        ]);

        $respuesta = $this->actingAs($admin)->get(route('admin.pacientes.show', $paciente->id))->assertOk();

        $respuesta->assertSee('Odontograma')
            ->assertSee('ARCADA SUPERIOR')
            ->assertSee('ARCADA INFERIOR')
            ->assertSee('Ausente / extraído (tachado)')
            ->assertSee('Cara oclusal')
            ->assertSee('por Doctora Ramírez');

        // Los 32 dientes tienen su campo oculto
        $html = $respuesta->getContent();
        foreach (Odontograma::numerosValidos() as $numero) {
            $this->assertStringContainsString('name="dientes[' . $numero . '][estado]"', $html);
        }

        // El estado guardado llega al diagrama: se decodifica lo que recibe Alpine
        $this->assertSame(1, preg_match("/dientes: JSON\.parse\('(.*?)'\)/", $html, $coincidencias));
        $dientes = json_decode(json_decode('"' . $coincidencias[1] . '"'), true);

        $this->assertSame('caries', $dientes[16]['estado']);
        $this->assertSame('Cara oclusal', $dientes[16]['nota']);
        $this->assertSame('sano', $dientes[17]['estado']);
        $this->assertCount(32, $dientes);
    }

    public function test_solo_la_doctora_puede_guardar_y_nunca_sobre_un_no_paciente(): void
    {
        $admin = $this->crearUsuario('admin');
        $asistente = $this->crearUsuario('asistente');
        $paciente = $this->crearUsuario('paciente');

        $this->actingAs($asistente)
            ->put(route('admin.pacientes.odontograma.update', $paciente->id), $this->dientes([16 => ['estado' => 'caries', 'nota' => '']]))
            ->assertForbidden();

        $this->actingAs($admin)
            ->put(route('admin.pacientes.odontograma.update', $asistente->id), $this->dientes([16 => ['estado' => 'caries', 'nota' => '']]))
            ->assertNotFound();

        $this->assertDatabaseCount('odontograma', 0);
    }

    public function test_borrar_el_expediente_borra_su_odontograma(): void
    {
        $paciente = $this->crearUsuario('paciente');
        $expediente = Expediente::create(['paciente_id' => $paciente->id]);
        $expediente->odontograma()->create(['numero_diente' => 16, 'estado' => 'caries']);

        $expediente->delete();

        $this->assertDatabaseCount('odontograma', 0);
    }
}
