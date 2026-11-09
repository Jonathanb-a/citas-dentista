<?php

namespace App\Services\Chat;

use App\Services\IA\Datos\LlamadaHerramienta;
use App\Services\IA\Datos\MensajeIA;
use App\Services\IA\ProveedorIA;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Orquesta una petición del chat: bucle de herramientas (máx. config('chat.max_vueltas') vueltas),
 * tope global en CADA llamada a la IA, instrucciones, historial y propuesta firmada.
 * Nunca lanza excepciones ni registra el contenido de los mensajes.
 *
 * Historial: del navegador solo llegan turnos de texto (user / assistant) con la firma del
 * asistente. Las llamadas y resultados de herramientas NUNCA viajan por el navegador: viven
 * dentro de una petición y se descartan. Si el visitante trae una propuesta firmada vigente, el
 * servidor reconstruye el intercambio que la produjo para que la IA sepa con qué datos llamar.
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
        private HerramientasChat $herramientas,
        private ConfirmacionAgendado $confirmacion,
    ) {}

    /**
     * @param array<int, array{rol: string, texto: string}> $historial
     * @return array{ok: bool, respuesta: string, firma?: string, pendiente?: string|null}
     */
    public function responder(string $mensaje, array $historial, ?string $pendiente = null, string $ip = ''): array
    {
        try {
            $ultimoAsistente = $this->ultimoAsistente($historial);
            $contexto = new ContextoChat($ip, $mensaje, $this->confirmacion->leer($pendiente, $ultimoAsistente));

            $mensajes = $this->mensajes($historial, $mensaje, $contexto);
            $instrucciones = $this->instrucciones->texto();
            $definiciones = $this->herramientas->definiciones();
            $maxPorVuelta = (int) config('chat.max_herramientas_por_vuelta');

            for ($vuelta = 1; $vuelta <= (int) config('chat.max_vueltas'); $vuelta++) {
                // Cada llamada a la IA cuenta en el tope global; al alcanzarlo no se llama
                if (!$this->limiteGlobal->reservar()) {
                    return $this->fallo((string) config('chat.mensajes.limite_global'));
                }

                $respuesta = $this->ia->responder($mensajes, $definiciones, $instrucciones);

                if ($respuesta->falla) {
                    return $this->fallo((string) $respuesta->texto);
                }

                if (!$respuesta->pideHerramientas()) {
                    $texto = trim((string) $respuesta->texto);

                    return $texto === ''
                        ? $this->fallo((string) config('ia.mensaje_error'))
                        : $this->exito($texto, $contexto);
                }

                // comoMensaje() conserva los datos del proveedor (p. ej. las firmas de Gemini)
                $mensajes[] = $respuesta->comoMensaje();

                foreach (array_values($respuesta->llamadas) as $i => $llamada) {
                    $resultado = $i >= $maxPorVuelta
                        ? ['error' => 'demasiadas_herramientas', 'mensaje' => 'Demasiadas consultas a la vez; responde al paciente.']
                        : $this->herramientas->ejecutar($llamada->nombre, $llamada->argumentos, $contexto);

                    $mensajes[] = MensajeIA::resultado($llamada, $resultado);

                    // El resumen o la confirmación de la cita los escribe el servidor: exactos y sin otra llamada
                    if ($contexto->respuestaDirecta !== null) {
                        return $this->exito($contexto->respuestaDirecta, $contexto);
                    }
                }
            }

            return $this->fallo((string) config('chat.mensajes.vueltas'));
        } catch (\Throwable $e) {
            Log::warning('Chat: error inesperado (' . class_basename($e) . ')');

            return $this->fallo((string) config('ia.mensaje_error'));
        }
    }

    private function exito(string $texto, ContextoChat $contexto): array
    {
        // Lo que vuelve en el historial debe caber en la validación
        $texto = Str::limit($texto, (int) config('chat.max_caracteres_respuesta'), '…', preserveWords: true);

        return [
            'ok' => true,
            'respuesta' => $texto,
            'firma' => $this->firmas->firmar($texto),
            // La propuesta solo sirve para el turno siguiente (va atada al resumen que acaba de verse)
            'pendiente' => $contexto->propuestaNueva,
        ];
    }

    private function fallo(string $mensaje): array
    {
        return ['ok' => false, 'respuesta' => $mensaje];
    }

    private function ultimoAsistente(array $historial): ?string
    {
        $ultimo = end($historial);

        return is_array($ultimo) && $ultimo['rol'] === 'assistant' ? $ultimo['texto'] : null;
    }

    /** @return MensajeIA[] */
    private function mensajes(array $historial, string $mensaje, ContextoChat $contexto): array
    {
        $mensajes = [];
        $total = count($historial);

        foreach (array_values($historial) as $i => $turno) {
            // Propuesta vigente: se reconstruye, antes del resumen, la llamada que la produjo
            if ($i === $total - 1 && $contexto->propuestaVigente !== null) {
                $llamada = new LlamadaHerramienta('call_propuesta', 'agendar_cita', $contexto->propuestaVigente);
                $mensajes[] = MensajeIA::asistente(null, [$llamada]);
                $mensajes[] = MensajeIA::resultado($llamada, [
                    'estado' => 'falta_confirmacion',
                    'mensaje' => 'Se le mostró el resumen al paciente y se espera su confirmación.',
                ]);
            }

            $mensajes[] = $turno['rol'] === 'assistant'
                ? MensajeIA::asistente($turno['texto'])
                : MensajeIA::usuario($turno['texto']);
        }

        $mensajes[] = MensajeIA::usuario($mensaje);

        return $mensajes;
    }
}
