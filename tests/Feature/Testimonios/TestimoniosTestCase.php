<?php

namespace Tests\Feature\Testimonios;

use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Horario;
use App\Models\Servicio;
use App\Models\Testimonio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Base de las pruebas de testimonios. Todos los nombres y textos son ficticios */
abstract class TestimoniosTestCase extends TestCase
{
    use RefreshDatabase;

    protected const TEXTO = 'La atención fue muy amable y me explicaron todo con paciencia.';

    protected Servicio $servicio;
    protected Horario $horario;
    private int $contador = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('imagenes');

        // Inactiva: la landing no la lista y así sigue siendo idéntica a la original (solo se necesita para las citas)
        $especialidad = Especialidad::create(['nombre' => 'Odontología General', 'activo' => false]);
        $this->servicio = Servicio::create(['especialidad_id' => $especialidad->id, 'nombre' => 'Limpieza dental', 'duracion_minutos' => 30, 'activo' => true]);
        $this->horario = Horario::create(['dia_semana' => 'lunes', 'hora_inicio' => '09:00', 'hora_fin' => '14:00', 'disponible' => true]);
    }

    // 'role' no está en $fillable: se asigna explícitamente
    protected function usuario(string $role = 'paciente', array $datos = []): User
    {
        $this->contador++;
        $user = User::factory()->make(array_merge(['name' => 'María', 'apellidos' => 'González', 'activo' => true, 'email' => "persona{$this->contador}@ejemplo.test"], $datos));
        $user->role = $role;
        $user->save();

        return $user;
    }

    protected function cita(User $paciente, string $status = 'atendida'): Cita
    {
        // Una fecha distinta por cita: el espacio (fecha y hora) es único entre las citas no canceladas
        $this->contador++;

        return Cita::create([
            'paciente_id' => $paciente->id, 'servicio_id' => $this->servicio->id, 'horario_id' => $this->horario->id,
            'fecha' => now()->subDays(10 + $this->contador)->toDateString(), 'hora' => '09:00', 'status' => $status, 'confirmado_por_paciente' => true,
        ]);
    }

    /** Paciente con una cita atendida: puede opinar */
    protected function paciente(array $datos = []): User
    {
        $paciente = $this->usuario('paciente', $datos);
        $this->cita($paciente);

        return $paciente;
    }

    protected function testimonio(User $paciente, string $estado = 'pendiente', array $cambios = []): Testimonio
    {
        $t = new Testimonio(array_merge(['paciente_id' => $paciente->id, 'calificacion' => 5, 'texto' => self::TEXTO, 'autorizado' => true], $cambios));
        $t->estado = $estado;
        $t->destacado = $cambios['destacado'] ?? false;
        $t->aprobado_en = $estado === 'aprobado' ? now() : null;
        $t->save();

        return $t;
    }

    protected function datos(array $cambios = []): array
    {
        return array_merge(['calificacion' => 5, 'texto' => self::TEXTO, 'autorizo' => '1'], $cambios);
    }

    protected function normalizar(string $html): string
    {
        return trim(preg_replace('/\s+/', ' ', $html));
    }

    /** assertTrue y no assertSame: si fallara, PHPUnit tardaría minutos en calcular el diff de dos páginas enteras */
    protected function assertIgualALaLandingOriginal(string $html): void
    {
        $original = file_get_contents(base_path('tests/Fixtures/landing-sin-anuncios.html'));

        $this->assertTrue($this->normalizar($original) === $this->normalizar($html), 'La landing ya no es idéntica a la original.');
    }
}
