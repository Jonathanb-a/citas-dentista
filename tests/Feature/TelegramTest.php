<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Horario;
use App\Models\Servicio;
use App\Models\User;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TelegramTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = '123456:SECRETO';

    private Servicio $servicio;
    private Horario $horario;
    private Carbon $lunes;
    private User $paciente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $especialidad = Especialidad::create(['nombre' => 'Odontología General']);
        $this->servicio = Servicio::create(['especialidad_id' => $especialidad->id, 'nombre' => 'Limpieza dental', 'duracion_minutos' => 30, 'activo' => true]);
        $this->horario = Horario::create(['dia_semana' => 'lunes', 'hora_inicio' => '09:00', 'hora_fin' => '14:00', 'disponible' => true]);
        $this->lunes = Carbon::now()->next(Carbon::MONDAY);
        $this->paciente = $this->crearUsuario('paciente', ['name' => 'Ana', 'apellidos' => 'Pérez', 'telefono' => '7531112233']);
    }

    private function activarTelegram(string $chats = '111,222'): void
    {
        config(['services.telegram.token' => self::TOKEN, 'services.telegram.chat_ids' => $chats]);
    }

    // 'role' no está en $fillable: se asigna explícitamente
    private function crearUsuario(string $role, array $datos = []): User
    {
        $user = User::factory()->make(array_merge(['apellidos' => 'Pérez', 'activo' => true], $datos));
        $user->role = $role;
        $user->save();

        return $user;
    }

    private function crearCita(array $datos = []): Cita
    {
        return Cita::create(array_merge([
            'paciente_id' => $this->paciente->id,
            'servicio_id' => $this->servicio->id,
            'horario_id' => $this->horario->id,
            'fecha' => $this->lunes->toDateString(),
            'hora' => '10:00',
            'status' => 'pendiente',
            'motivo' => 'Dolor fuerte de muela',
        ], $datos));
    }

    private function datosAgendar(): array
    {
        return [
            'servicio_id' => $this->servicio->id,
            'horario_id' => $this->horario->id,
            'fecha' => $this->lunes->toDateString(),
            'hora' => '10:00',
            'motivo' => 'Dolor fuerte de muela',
        ];
    }

    private function mensajes(): array
    {
        return Http::recorded()->map(fn ($par) => $par[0]['text'])->all();
    }

    // ---------- TelegramService ----------

    public function test_manda_el_mensaje_a_todos_los_chat_ids(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $this->activarTelegram(' 111 , -222,,  333 ');

        app(TelegramService::class)->avisar('Hola');

        Http::assertSentCount(3);
        foreach (['111', '-222', '333'] as $chat) {
            Http::assertSent(fn (Request $r) => $r->url() === 'https://api.telegram.org/bot' . self::TOKEN . '/sendMessage'
                && $r['chat_id'] === $chat
                && $r['text'] === 'Hola');
        }
    }

    public function test_sin_token_no_hace_nada(): void
    {
        Http::fake();
        config(['services.telegram.token' => null, 'services.telegram.chat_ids' => '111']);

        app(TelegramService::class)->avisar('Hola');
        app(TelegramService::class)->citaAgendada($this->crearCita());

        Http::assertNothingSent();
    }

    public function test_sin_chat_ids_no_hace_nada(): void
    {
        Http::fake();
        config(['services.telegram.token' => self::TOKEN, 'services.telegram.chat_ids' => ' , ']);

        app(TelegramService::class)->avisar('Hola');

        Http::assertNothingSent();
    }

    public function test_si_telegram_responde_error_no_truena_y_sigue_con_los_demas_chats(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::sequence()
                ->push(['ok' => false, 'description' => 'Forbidden: bot was blocked'], 403)
                ->push(['ok' => true]),
        ]);
        $this->activarTelegram('111,222');
        Log::spy();

        app(TelegramService::class)->avisar('Hola');

        Http::assertSentCount(2);
        Log::shouldHaveReceived('warning')->once()->withArgs(fn ($m) => str_contains($m, '111') && str_contains($m, '403'));
    }

    public function test_si_la_conexion_falla_no_truena_y_el_token_no_llega_al_log(): void
    {
        Http::fake(fn () => throw new ConnectionException(
            'cURL error 28: Operation timed out for https://api.telegram.org/bot' . self::TOKEN . '/sendMessage'
        ));
        $this->activarTelegram('111,222');
        Log::spy();

        app(TelegramService::class)->avisar('Hola');

        Log::shouldHaveReceived('warning')->twice()->withArgs(
            fn ($m) => str_contains($m, '***') && !str_contains($m, 'SECRETO')
        );
    }

    public function test_el_mensaje_se_manda_como_texto_plano(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $this->activarTelegram('111');

        app(TelegramService::class)->avisar('Paciente: Ana_*[Pérez]');

        Http::assertSent(fn (Request $r) => !isset($r['parse_mode']));
    }

    public function test_los_mensajes_dicen_evento_paciente_servicio_fecha_y_hora_sin_datos_clinicos(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $this->activarTelegram('111');
        $cita = $this->crearCita();
        $telegram = app(TelegramService::class);

        $telegram->citaAgendada($cita);
        $telegram->citaCancelada($cita);
        $telegram->citaReagendada($cita, $this->lunes->copy()->subDays(7)->toDateString() . ' 09:30');
        $telegram->citaLiberada($cita);

        $textos = $this->mensajes();
        $this->assertCount(4, $textos);
        $this->assertStringContainsString('Nueva cita agendada', $textos[0]);
        $this->assertStringContainsString('cancelada por el paciente', $textos[1]);
        $this->assertStringContainsString('Cita reagendada', $textos[2]);
        $this->assertStringContainsString('Antes: ', $textos[2]);
        $this->assertStringContainsString('09:30 AM', $textos[2]);
        $this->assertStringContainsString('no confirmó', $textos[3]);

        foreach ($textos as $texto) {
            $this->assertStringContainsString('Paciente: Ana Pérez', $texto);
            $this->assertStringContainsString('Servicio: Limpieza dental', $texto);
            $this->assertStringContainsString($this->lunes->format('d/m/Y') . ', 10:00 AM', $texto);
            $this->assertStringNotContainsString('7531112233', $texto);
            $this->assertStringNotContainsString('Dolor fuerte', $texto);
            $this->assertStringNotContainsString($this->paciente->email, $texto);
        }
    }

    public function test_el_aviso_no_truena_si_falta_una_relacion(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $this->activarTelegram('111');
        $cita = $this->crearCita();
        $cita->setRelation('paciente', null);

        app(TelegramService::class)->citaAgendada($cita);

        Http::assertNothingSent();
    }

    // ---------- Eventos ----------

    public function test_paciente_agenda_y_la_clinica_recibe_el_aviso_con_la_cita_ya_guardada(): void
    {
        $citasAlEnviar = [];
        Http::fake(function () use (&$citasAlEnviar) {
            $citasAlEnviar[] = Cita::count();

            return Http::response(['ok' => true]);
        });
        $this->activarTelegram('111,222');

        $this->actingAs($this->paciente)->post(route('paciente.citas.store'), $this->datosAgendar())
            ->assertRedirect(route('paciente.citas.index'));

        $this->assertSame([1, 1], $citasAlEnviar);
        $this->assertStringContainsString('Nueva cita agendada', $this->mensajes()[0]);
    }

    public function test_si_telegram_esta_caido_la_cita_se_agenda_igual(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response('Bad gateway', 502)]);
        $this->activarTelegram();

        $this->actingAs($this->paciente)->post(route('paciente.citas.store'), $this->datosAgendar())
            ->assertRedirect(route('paciente.citas.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('citas', ['paciente_id' => $this->paciente->id, 'hora' => '10:00']);
    }

    public function test_si_telegram_lanza_excepcion_la_cita_se_agenda_igual(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));
        $this->activarTelegram();

        $this->actingAs($this->paciente)->post(route('paciente.citas.store'), $this->datosAgendar())
            ->assertRedirect(route('paciente.citas.index'));

        $this->assertDatabaseCount('citas', 1);
    }

    public function test_agendar_sin_token_funciona_y_no_llama_a_telegram(): void
    {
        Http::fake();

        $this->actingAs($this->paciente)->post(route('paciente.citas.store'), $this->datosAgendar())
            ->assertRedirect(route('paciente.citas.index'));

        $this->assertDatabaseCount('citas', 1);
        Http::assertNothingSent();
    }

    public function test_si_no_se_pudo_agendar_no_hay_aviso(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $this->activarTelegram();
        $this->crearCita(); // el horario ya está ocupado

        $this->actingAs($this->paciente)->post(route('paciente.citas.store'), $this->datosAgendar())
            ->assertSessionHasErrors('hora');

        Http::assertNothingSent();
    }

    public function test_paciente_cancela_y_la_clinica_recibe_el_aviso(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $this->activarTelegram('111');
        $cita = $this->crearCita();

        $this->actingAs($this->paciente)->patch(route('paciente.citas.cancelar', $cita->id))
            ->assertRedirect(route('paciente.citas.index'));

        $this->assertSame('cancelada', $cita->fresh()->status);
        Http::assertSentCount(1);
        $this->assertStringContainsString('cancelada por el paciente', $this->mensajes()[0]);
    }

    public function test_cancelar_una_cita_atendida_no_avisa(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $this->activarTelegram();
        $cita = $this->crearCita(['status' => 'atendida']);

        $this->actingAs($this->paciente)->patch(route('paciente.citas.cancelar', $cita->id))
            ->assertSessionHas('error');

        Http::assertNothingSent();
    }

    public static function roles(): array
    {
        return ['admin' => ['admin'], 'asistente' => ['asistente']];
    }

    #[DataProvider('roles')]
    public function test_reagendar_avisa_a_la_clinica_con_la_fecha_anterior(string $rol): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $this->activarTelegram('111');
        $cita = $this->crearCita();
        $nuevoLunes = $this->lunes->copy()->addWeek();

        $this->actingAs($this->crearUsuario($rol))->patch(route("$rol.citas.reagendar", $cita->id), [
            'fecha' => $nuevoLunes->toDateString(),
            'hora' => '11:00',
            'horario_id' => $this->horario->id,
        ])->assertRedirect(route("$rol.citas.index"));

        Http::assertSentCount(1);
        $texto = $this->mensajes()[0];
        $this->assertStringContainsString('Cita reagendada', $texto);
        $this->assertStringContainsString($nuevoLunes->format('d/m/Y') . ', 11:00 AM', $texto);
        $this->assertStringContainsString('Antes: ', $texto);
        $this->assertStringContainsString($this->lunes->format('d/m/Y') . ', 10:00 AM', $texto);
    }

    public function test_reagendar_a_un_horario_no_disponible_no_avisa(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $this->activarTelegram();
        $cita = $this->crearCita();
        $this->crearCita(['hora' => '11:00', 'paciente_id' => $this->crearUsuario('paciente')->id]);

        $this->actingAs($this->crearUsuario('admin'))->patch(route('admin.citas.reagendar', $cita->id), [
            'fecha' => $this->lunes->toDateString(),
            'hora' => '11:00',
            'horario_id' => $this->horario->id,
        ])->assertSessionHas('error');

        Http::assertNothingSent();
    }

    public function test_reagendar_con_telegram_caido_reagenda_igual(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));
        $this->activarTelegram();
        $cita = $this->crearCita();

        $this->actingAs($this->crearUsuario('admin'))->patch(route('admin.citas.reagendar', $cita->id), [
            'fecha' => $this->lunes->toDateString(),
            'hora' => '12:00',
            'horario_id' => $this->horario->id,
        ])->assertRedirect(route('admin.citas.index'));

        $this->assertSame('12:00', Carbon::parse($cita->fresh()->hora)->format('H:i'));
    }

    public function test_el_comando_avisa_por_cada_cita_liberada(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $this->activarTelegram('111');

        $vencida = $this->crearCita(['status' => 'confirmada', 'confirmacion_limite' => now()->subHour()]);
        $this->crearCita([
            'hora' => '11:00',
            'status' => 'confirmada',
            'confirmacion_limite' => now()->addHours(5), // aún en plazo
        ]);

        $this->artisan('citas:liberar-no-confirmadas')->assertSuccessful();

        $this->assertSame('cancelada', $vencida->fresh()->status);
        Http::assertSentCount(1);
        $this->assertStringContainsString('no confirmó', $this->mensajes()[0]);
    }

    public function test_el_comando_libera_las_citas_aunque_telegram_falle(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));
        $this->activarTelegram();
        $cita = $this->crearCita(['status' => 'confirmada', 'confirmacion_limite' => now()->subHour()]);

        $this->artisan('citas:liberar-no-confirmadas')->assertSuccessful();

        $this->assertSame('cancelada', $cita->fresh()->status);
    }
}
