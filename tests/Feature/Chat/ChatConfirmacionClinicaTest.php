<?php

namespace Tests\Feature\Chat;

use App\Models\Cita;
use App\Models\HistorialCita;
use App\Models\User;
use App\Services\CitaManualService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Un paciente del chat no tiene cuenta para confirmar su asistencia: la aceptación de la clínica
 * (que le llama) cuenta como su confirmación. Para los demás pacientes nada cambia.
 */
class ChatConfirmacionClinicaTest extends ChatTestCase
{
    private function pacienteDelChat(): User
    {
        return $this->crearUsuario('paciente', ['telefono' => '5550100041', 'email' => 'chat-abcdefghij1234567890@chat.dentissa.invalid']);
    }

    private function pacienteNormal(): User
    {
        return $this->crearUsuario('paciente', ['telefono' => '5550100042', 'email' => 'paciente.normal@ejemplo.test']);
    }

    private function pendiente(User $paciente, string $hora = '09:00'): Cita
    {
        $cita = $this->crearCita($paciente, $this->servicio ??= $this->crearServicio(), $this->horario ??= $this->crearHorarioLunes(), $hora);
        $cita->update(['confirmacion_limite' => now()->addHours(24)]);

        return $cita;
    }

    private ?\App\Models\Servicio $servicio = null;
    private ?\App\Models\Horario $horario = null;

    public static function roles(): array
    {
        return ['admin' => ['admin'], 'asistente' => ['asistente']];
    }

    #[DataProvider('roles')]
    public function test_la_aceptacion_de_la_clinica_cuenta_como_confirmacion_del_paciente_del_chat(string $rol): void
    {
        $cita = $this->pendiente($this->pacienteDelChat());

        $this->actingAs($this->crearUsuario($rol))
            ->patch(route("$rol.citas.confirmar", $cita->id))
            ->assertSessionHas('success');

        $cita->refresh();
        $this->assertSame('confirmada', $cita->status);
        $this->assertTrue($cita->confirmado_por_paciente);
        $this->assertTrue($cita->confirmacion_limite->equalTo(now()->addHours(24)));
        $this->assertStringContainsString('Paciente del chat', HistorialCita::where('cita_id', $cita->id)->where('accion', 'confirmada')->sole()->descripcion);
    }

    #[DataProvider('roles')]
    public function test_para_un_paciente_normal_la_confirmacion_de_la_clinica_no_cambia(string $rol): void
    {
        $cita = $this->pendiente($this->pacienteNormal());

        $this->actingAs($this->crearUsuario($rol))->patch(route("$rol.citas.confirmar", $cita->id))->assertSessionHas('success');

        $cita->refresh();
        $this->assertSame('confirmada', $cita->status);
        $this->assertFalse($cita->confirmado_por_paciente);   // sigue necesitando que el paciente confirme
        $this->assertStringNotContainsString('Paciente del chat', HistorialCita::where('cita_id', $cita->id)->where('accion', 'confirmada')->sole()->descripcion);
    }

    public function test_la_liberacion_automatica_no_cancela_la_cita_confirmada_de_un_paciente_del_chat_pero_si_la_de_uno_normal(): void
    {
        $delChat = $this->pendiente($this->pacienteDelChat(), '09:00');
        $normal = $this->pendiente($this->pacienteNormal(), '09:45');
        $admin = $this->crearUsuario('admin');

        foreach ([$delChat, $normal] as $cita) {
            $this->actingAs($admin)->patch(route('admin.citas.confirmar', $cita->id));
        }

        Carbon::setTestNow(now()->addHours(25));
        Artisan::call('citas:liberar-no-confirmadas');

        $this->assertSame('confirmada', $delChat->fresh()->status);
        $this->assertSame('cancelada', $normal->fresh()->status);
    }

    public function test_reagendar_una_cita_de_un_paciente_del_chat_conserva_su_confirmacion(): void
    {
        $admin = $this->crearUsuario('admin');
        $delChat = $this->pendiente($this->pacienteDelChat(), '09:00');
        $normal = $this->pendiente($this->pacienteNormal(), '09:45');
        $this->actingAs($admin);

        foreach ([[$delChat, true], [$normal, false]] as [$cita, $esperado]) {
            $resultado = app(CitaManualService::class)->reagendar(
                $cita->id,
                ['fecha' => '2026-11-23', 'hora' => $cita->hora === '09:00' ? '10:30' : '11:15', 'horario_id' => $this->horario->id],
                $admin->id,
            );

            $this->assertArrayNotHasKey('error', $resultado);
            $this->assertSame('confirmada', $cita->fresh()->status);
            $this->assertSame($esperado, $cita->fresh()->confirmado_por_paciente);
        }
    }

    public function test_crear_manualmente_una_cita_confirmada_para_un_paciente_del_chat_cuenta_como_confirmada_por_el(): void
    {
        $admin = $this->crearUsuario('admin');
        $this->pendiente($this->pacienteDelChat(), '09:00');   // solo para crear el servicio y el horario
        $this->actingAs($admin);

        $crear = fn (User $paciente, string $hora, string $status) => app(CitaManualService::class)->crear([
            'tipo_paciente' => 'existente',
            'paciente_id' => $paciente->id,
            'servicio_id' => $this->servicio->id,
            'horario_id' => $this->horario->id,
            'fecha' => self::FECHA,
            'hora' => $hora,
            'status' => $status,
        ], $admin->id);

        $this->assertTrue($crear(User::where('telefono', '5550100041')->first(), '09:45', 'confirmada')['cita']->confirmado_por_paciente);
        $this->assertFalse($crear(User::where('telefono', '5550100041')->first(), '10:30', 'pendiente')['cita']->confirmado_por_paciente);
        $this->assertFalse($crear($this->pacienteNormal(), '11:15', 'confirmada')['cita']->confirmado_por_paciente);
    }

    public static function correos(): array
    {
        return [
            'marcador del chat' => ['chat-abc@chat.dentissa.invalid', true],
            'marcador en mayúsculas' => ['CHAT-ABC@CHAT.DENTISSA.INVALID', true],
            'correo real' => ['paciente@ejemplo.test', false],
            'dominio parecido (sufijo)' => ['x@chat.dentissa.invalid.com', false],
            'dominio parecido (prefijo)' => ['x@evil-chat.dentissa.invalid', false],
            'otro subdominio' => ['x@otro.dentissa.invalid', false],
            'sin arroba' => ['chat.dentissa.invalid', false],
        ];
    }

    #[DataProvider('correos')]
    public function test_solo_el_correo_marcador_identifica_a_un_paciente_del_chat(string $correo, bool $esperado): void
    {
        $usuario = new User();
        $usuario->email = $correo;

        $this->assertSame($esperado, $usuario->esDeChat());
    }
}
