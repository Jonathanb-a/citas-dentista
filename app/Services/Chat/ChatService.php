<?php

namespace App\Services\Chat;

use App\Services\IA\Datos\MensajeIA;
use App\Services\IA\ProveedorIA;
use Illuminate\Support\Facades\Log;

/**
 * Orquesta una vuelta del chat: tope global, instrucciones, historial y llamada a la IA.
 * Nunca lanza excepciones ni registra el contenido de los mensajes.
 *
 * Se asume entrada ya validada por ChatRequest (roles, longitudes y firmas).
 */
class ChatService
{
    public function __construct(
        private ProveedorIA $ia,
        private InstruccionesChat $instrucciones,
        private FirmaHistorial $firmas,
        private LimiteGlobalIA $limiteGlobal,
    ) {}

    /**
     * @param array<int, array{rol: string, texto: string}> $historial
     * @return array{ok: bool, respuesta: string, firma?: string}
     */
    public function responder(string $mensaje, array $historial): array
    {
        // Al alcanzar el tope diario no se llama a la IA
        if (!$this->limiteGlobal->reservar()) {
            return $this->fallo((string) config('chat.mensajes.limite_global'));
        }

        try {
            $mensajes = [];

            foreach ($historial as $turno) {
                $mensajes[] = $turno['rol'] === 'assistant'
                    ? MensajeIA::asistente($turno['texto'])
                    : MensajeIA::usuario($turno['texto']);
            }

            $mensajes[] = MensajeIA::usuario($mensaje);

            // Las instrucciones van aparte (mensaje system); el texto del visitante solo entra como mensaje de usuario
            $respuesta = $this->ia->responder($mensajes, [], $this->instrucciones->texto());
        } catch (\Throwable $e) {
            Log::warning('Chat: error inesperado (' . class_basename($e) . ')');

            return $this->fallo((string) config('ia.mensaje_error'));
        }

        if ($respuesta->falla || trim((string) $respuesta->texto) === '') {
            return $this->fallo($respuesta->falla ? (string) $respuesta->texto : (string) config('ia.mensaje_error'));
        }

        return [
            'ok' => true,
            'respuesta' => $respuesta->texto,
            'firma' => $this->firmas->firmar($respuesta->texto),
        ];
    }

    private function fallo(string $mensaje): array
    {
        return ['ok' => false, 'respuesta' => $mensaje];
    }
}
