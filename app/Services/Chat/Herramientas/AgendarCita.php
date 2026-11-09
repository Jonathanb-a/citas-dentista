<?php

namespace App\Services\Chat\Herramientas;

use App\Models\Cita;
use App\Models\Expediente;
use App\Models\HistorialCita;
use App\Models\User;
use App\Services\Chat\ConfirmacionAgendado;
use App\Services\Chat\ContextoChat;
use App\Services\Chat\DatosCita;
use App\Services\Chat\LimiteCitasChat;
use App\Services\Chat\TextoPlano;
use App\Services\DisponibilidadService;
use App\Services\IA\Datos\HerramientaIA;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Agenda una cita desde el chat. Reglas del agendado normal (Paciente\CitaController::store):
 * disponibilidad por DisponibilidadService, 3 citas activas por paciente, choque con el índice
 * único, historial, Telegram y estado "pendiente" con límite de confirmación de 24 horas.
 *
 * - La primera llamada válida NO agenda: el servidor escribe el resumen y emite una propuesta
 *   firmada; solo se agenda con esa propuesta, un "sí" claro y los mismos datos (ver
 *   ConfirmacionAgendado)
 * - El horario_id lo deriva el servidor del espacio libre; el modelo nunca lo aporta
 * - El paciente del chat no tiene cuenta usable: se crea uno interno (correo .invalid,
 *   contraseña aleatoria). Si el teléfono es de un paciente real se rechaza con un mensaje
 *   genérico (no se revela si existe ni se agenda a su nombre)
 */
class AgendarCita implements HerramientaChat
{
    public function __construct(
        private DatosCita $datos,
        private DisponibilidadService $disponibilidad,
        private ConfirmacionAgendado $confirmacion,
        private LimiteCitasChat $limites,
    ) {}

    public function definicion(): HerramientaIA
    {
        return new HerramientaIA(
            'agendar_cita',
            'Agenda una cita. Llámala cuando tengas servicio, fecha, hora, nombre, apellidos y teléfono que el paciente te dio. '
            . 'La primera vez NO agenda: el sistema le muestra el resumen al paciente y le pide confirmar. '
            . 'Cuando el paciente responda que sí, llámala otra vez con EXACTAMENTE los mismos datos.',
            [
                'type' => 'object',
                'properties' => [
                    'servicio_id' => ['type' => 'integer', 'description' => 'Id del servicio'],
                    'fecha' => ['type' => 'string', 'description' => 'Fecha AAAA-MM-DD (desde mañana)'],
                    'hora' => ['type' => 'string', 'description' => 'Hora de inicio HH:MM en 24 horas, de ver_horarios_libres'],
                    'nombre' => ['type' => 'string', 'description' => 'Nombre(s) del paciente'],
                    'apellidos' => ['type' => 'string', 'description' => 'Apellidos del paciente'],
                    'telefono' => ['type' => 'string', 'description' => 'Teléfono de 10 dígitos'],
                ],
                'required' => ['servicio_id', 'fecha', 'hora', 'nombre', 'apellidos', 'telefono'],
            ],
        );
    }

    public function ejecutar(array $argumentos, ContextoChat $contexto): array
    {
        // 1. Validación de todo lo que pidió el modelo
        $errores = [];

        $servicio = $this->datos->servicio($argumentos['servicio_id'] ?? null);
        $servicio ?: $errores['servicio_id'] = 'no existe o no está disponible; usa un id de listar_servicios';

        $fecha = $this->datos->fecha($argumentos['fecha'] ?? null);
        if (isset($fecha['error'])) {
            $errores['fecha'] = match ($fecha['error']) {
                'formato' => 'debe ser AAAA-MM-DD y existir',
                'pasada' => 'solo se agenda desde mañana',
                default => 'máximo 3 meses de anticipación',
            };
        }

        $hora = $this->datos->hora($argumentos['hora'] ?? null);
        $hora ?: $errores['hora'] = 'debe ser HH:MM en 24 horas';

        $nombre = $this->datos->nombre($argumentos['nombre'] ?? null);
        $nombre ?: $errores['nombre'] = 'solo letras y espacios, de 2 a 60 caracteres';

        $apellidos = $this->datos->nombre($argumentos['apellidos'] ?? null);
        $apellidos ?: $errores['apellidos'] = 'solo letras y espacios, de 2 a 60 caracteres; pídelos al paciente';

        $telefono = $this->datos->telefono($argumentos['telefono'] ?? null);
        $telefono ?: $errores['telefono'] = 'debe ser un teléfono de 10 dígitos (México); pídeselo de nuevo al paciente';

        if ($errores !== []) {
            return ['error' => 'datos_invalidos', 'mensaje' => 'Corrige estos datos con el paciente y vuelve a intentar.', 'campos' => $errores];
        }

        // Una sola llamada válida por mensaje del visitante
        if ($contexto->agendarProcesado) {
            return ['error' => 'una_cita_por_mensaje', 'mensaje' => 'Ya se procesó una cita en este mensaje. Responde al paciente.'];
        }
        $contexto->agendarProcesado = true;

        // 2. El espacio tiene que estar libre según DisponibilidadService
        $espacios = $this->disponibilidad->espaciosDisponibles($fecha['fecha'], $servicio->id);
        $espacio = collect($espacios)->firstWhere('hora', $hora);

        if (!$espacio) {
            return $this->ocupado($espacios);
        }

        // 3. Confirmación explícita, verificada por el servidor
        $datos = [
            'servicio_id' => $servicio->id,
            'fecha' => $fecha['fecha'],
            'hora' => $hora,
            'nombre' => $nombre,
            'apellidos' => $apellidos,
            'telefono' => $telefono,
        ];

        if (!($this->confirmacion->coincide($contexto->propuestaVigente, $datos) && $this->confirmacion->esAfirmativo($contexto->mensaje))) {
            $resumen = $this->texto('propuesta', $servicio->nombre, $datos);
            $contexto->propuestaNueva = $this->confirmacion->emitir($datos, $resumen);
            $contexto->respuestaDirecta = $resumen;

            return ['estado' => 'falta_confirmacion', 'mensaje' => 'Se le mostró el resumen al paciente y se espera su confirmación.'];
        }

        // 4. Paciente: nuevo, o uno del chat que ya agendó antes. Nunca un paciente real
        $existente = $this->buscarPorTelefono($telefono);

        if ($existente && !($existente->esPaciente() && $existente->esDeChat() && $existente->activo)) {
            return $this->noDisponible();
        }

        if ($existente) {
            $activas = Cita::where('paciente_id', $existente->id)
                ->whereIn('status', ['pendiente', 'confirmada'])
                ->whereDate('fecha', '>=', $this->datos->hoy()->toDateString())
                ->count();

            if ($activas >= (int) config('chat.agenda.citas_activas_max')) {
                return ['error' => 'limite_citas', 'mensaje' => 'Este teléfono ya tiene el máximo de citas activas. Indica al paciente que se comunique con el consultorio.'];
            }
        }

        // 5. Tope de citas por chat al día (por IP y total)
        if ($this->limites->reservar($contexto->ip) !== null) {
            return $this->noDisponible();
        }

        // 6. Crear: si algo falla, nada queda a medias (ni paciente huérfano)
        try {
            $cita = DB::transaction(function () use ($existente, $datos, $espacio, $nombre, $apellidos, $telefono) {
                $paciente = $existente ?? $this->crearPaciente($nombre, $apellidos, $telefono);

                $cita = Cita::create([
                    'paciente_id' => $paciente->id,
                    'servicio_id' => $datos['servicio_id'],
                    'horario_id' => $espacio['horario_id'],
                    'fecha' => $datos['fecha'],
                    'hora' => $datos['hora'],
                    'status' => 'pendiente',
                    'confirmado_por_paciente' => false,
                    'confirmacion_limite' => Carbon::now()->addHours(24),
                    'notas_asistente' => 'Agendada desde el chat web. Nombre indicado en el chat: ' . $nombre . ' ' . $apellidos . '.',
                ]);

                HistorialCita::create([
                    'cita_id' => $cita->id,
                    'user_id' => $paciente->id,
                    'accion' => 'creada',
                    'descripcion' => 'El paciente agendó la cita desde el chat web.',
                ]);

                return $cita;
            });
        } catch (\Throwable $e) {
            $this->limites->liberar($contexto->ip);
            Log::warning('Chat: no se pudo crear la cita (' . class_basename($e) . ')');

            // Lo más probable: alguien tomó el espacio justo ahora (índice único)
            $libres = $this->disponibilidad->espaciosDisponibles($datos['fecha'], $servicio->id);

            return collect($libres)->contains('hora', $hora) ? $this->noDisponible() : $this->ocupado($libres);
        }

        // Aviso a la clínica, ya con la cita guardada y después de responder
        app(TelegramService::class)->enSegundoPlano(fn ($telegram) => $telegram->citaAgendada(
            $cita,
            'chat web',
            'El paciente no tiene cuenta: confirmar por teléfono.',
        ));

        $contexto->agendada = true;
        $contexto->respuestaDirecta = $this->texto('agendada', $servicio->nombre, $datos);

        return ['estado' => 'agendada'];
    }

    // ---------- Auxiliares ----------

    private function ocupado(array $espacios): array
    {
        return [
            'error' => 'horario_no_disponible',
            'mensaje' => 'Ese horario ya no está libre. Ofrece al paciente otro de las alternativas.',
            'alternativas' => array_map(
                fn (array $e) => ['hora' => $e['hora'], 'hora_formato' => $e['hora_formato']],
                array_slice($espacios, 0, 3),
            ),
        ];
    }

    // Mismo mensaje para cualquier causa (paciente real, tope diario…): no se revela si un teléfono existe
    private function noDisponible(): array
    {
        return ['error' => 'no_disponible_por_chat', 'mensaje' => (string) config('chat.mensajes.no_disponible')];
    }

    /** Compara por los últimos 10 dígitos: hay teléfonos guardados con espacios o guiones */
    private function buscarPorTelefono(string $telefono): ?User
    {
        return User::whereRaw(
            "SUBSTR(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(telefono, ' ', ''), '-', ''), '(', ''), ')', ''), '+', ''), -10) = ?",
            [$telefono],
        )->first();
    }

    private function crearPaciente(string $nombre, string $apellidos, string $telefono): User
    {
        $paciente = new User();
        $paciente->name = $nombre;
        $paciente->apellidos = $apellidos;
        $paciente->telefono = $telefono;
        $paciente->email = 'chat-' . Str::lower(Str::random(20)) . '@' . config('chat.agenda.correo_dominio');
        $paciente->password = Hash::make(Str::random(40));   // nadie la conoce: la clínica puede restablecerla
        $paciente->role = 'paciente';                         // asignado explícitamente, no por fillable
        $paciente->activo = true;
        $paciente->save();

        Expediente::create(['paciente_id' => $paciente->id]);

        return $paciente;
    }

    private function texto(string $plantilla, string $servicio, array $datos): string
    {
        return trim(strtr((string) config('chat.mensajes.' . $plantilla), [
            '{servicio}' => mb_substr((string) TextoPlano::limpiar($servicio), 0, 80),
            '{fecha}' => $this->datos->fechaLegible($datos['fecha']),
            '{hora}' => $this->datos->horaLegible($datos['hora']),
            '{nombre}' => $datos['nombre'] . ' ' . $datos['apellidos'],
            '{telefono}' => $this->datos->telefonoLegible($datos['telefono']),
        ]));
    }
}
