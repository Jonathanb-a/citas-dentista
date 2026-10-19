<?php

namespace App\Services;

use App\Models\Cita;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Avisos internos a la clínica por Telegram. Nunca a los pacientes.
 *
 * Un fallo de Telegram jamás debe afectar una cita: ningún método lanza
 * excepciones y, sin token o sin chats, no hace nada.
 */
class TelegramService
{
    const TIMEOUT_SEGUNDOS = 5;
    const CONEXION_SEGUNDOS = 3;

    public function configurado(): bool
    {
        return !empty(config('services.telegram.token')) && !empty($this->chatIds());
    }

    /**
     * Ejecuta el aviso después de enviar la respuesta al usuario, para que
     * el timeout de Telegram nunca retrase una reserva.
     * Solo para peticiones web; en comandos se llama al método directo.
     */
    public function enSegundoPlano(callable $aviso): void
    {
        if (!$this->configurado()) {
            return;
        }

        dispatch(fn () => $aviso($this))->afterResponse();
    }

    // ---------- Avisos por evento (texto corto, sin datos clínicos) ----------

    public function citaAgendada(Cita $cita): void
    {
        $this->seguro(fn () => $this->avisar("🆕 Nueva cita agendada\n" . $this->detalle($cita)));
    }

    public function citaCancelada(Cita $cita): void
    {
        $this->seguro(fn () => $this->avisar("❌ Cita cancelada por el paciente\n" . $this->detalle($cita)));
    }

    /** $antes: fecha y hora anteriores, formato 'Y-m-d H:i' */
    public function citaReagendada(Cita $cita, string $antes): void
    {
        $this->seguro(fn () => $this->avisar(
            "🔁 Cita reagendada\n" . $this->detalle($cita) . "\nAntes: " . $this->formatoFecha($antes)
        ));
    }

    public function citaLiberada(Cita $cita): void
    {
        $this->seguro(fn () => $this->avisar("⏰ Cita liberada: el paciente no confirmó a tiempo\n" . $this->detalle($cita)));
    }

    // ---------- Envío ----------

    /** Manda el mensaje a cada chat configurado. Nunca lanza excepciones. */
    public function avisar(string $mensaje): void
    {
        $token = config('services.telegram.token');
        $chats = $this->chatIds();

        if (empty($token) || empty($chats)) {
            return;
        }

        foreach ($chats as $chat) {
            try {
                $respuesta = Http::timeout(self::TIMEOUT_SEGUNDOS)
                    ->connectTimeout(self::CONEXION_SEGUNDOS)
                    ->asForm()
                    ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                        'chat_id' => $chat,
                        'text' => $mensaje,
                        'disable_web_page_preview' => true,
                    ]);

                if ($respuesta->failed()) {
                    Log::warning('Telegram: no se pudo avisar al chat ' . $chat
                        . ' (HTTP ' . $respuesta->status() . '): '
                        . $this->sinToken((string) $respuesta->json('description', 'sin descripción'), $token));
                }
            } catch (\Throwable $e) {
                Log::warning('Telegram: error al avisar al chat ' . $chat . ': '
                    . $this->sinToken($e->getMessage(), $token));
            }
        }
    }

    // ---------- Auxiliares ----------

    // Armar el mensaje también puede fallar (relación faltante): tampoco debe romper nada
    private function seguro(callable $accion): void
    {
        try {
            $accion();
        } catch (\Throwable $e) {
            Log::warning('Telegram: no se pudo preparar el aviso: ' . $e->getMessage());
        }
    }

    private function chatIds(): array
    {
        return array_values(array_filter(
            array_map('trim', explode(',', (string) config('services.telegram.chat_ids')))
        ));
    }

    // Telegram lleva el token en la URL y los errores de conexión la incluyen: no debe llegar al log
    private function sinToken(string $texto, string $token): string
    {
        return str_replace($token, '***', $texto);
    }

    private function detalle(Cita $cita): string
    {
        $cita->loadMissing(['paciente', 'servicio']);

        return 'Paciente: ' . trim($cita->paciente->name . ' ' . $cita->paciente->apellidos) . "\n"
            . 'Servicio: ' . $cita->servicio->nombre . "\n"
            . 'Fecha: ' . $this->formatoFecha($cita->fecha->toDateString() . ' ' . $cita->hora);
    }

    private function formatoFecha(string $fechaHora): string
    {
        $fecha = Carbon::parse($fechaHora);

        // La hora va con AM/PM como en el resto del sistema ("a. m." de es no se lee bien en un aviso corto)
        return $fecha->copy()->locale('es')->translatedFormat('l d/m/Y') . ', ' . $fecha->format('h:i A');
    }
}
