<?php

namespace App\Services\Chat;

use App\Models\Servicio;
use Carbon\Carbon;

/**
 * Las instrucciones del sistema salen de resources/prompts/asistente.md. Los únicos valores que se
 * insertan los pone el servidor: config del consultorio, horario general, servicios, promociones
 * vigentes y el día de hoy. El texto del visitante nunca pasa por aquí.
 *
 * Orden del prompt, de lo más estable a lo más cambiante (Groq reutiliza por prefijo exacto: lo
 * que cambia va al final y solo el día, no la hora): reglas, horario, servicios, promociones, hoy.
 * Dos peticiones iguales del mismo día arman exactamente el mismo texto.
 */
class InstruccionesChat
{
    public function __construct(
        private DatosParaPrompt $datos,
        private HorarioConsultorio $horario,
        private PromocionesVigentes $promociones,
    ) {}

    public function texto(): string
    {
        $archivo = (string) config('chat.prompt');
        $plantilla = is_file($archivo) ? file_get_contents($archivo) : false;

        if ($plantilla === false || trim($plantilla) === '') {
            throw new \RuntimeException('No se encontró el archivo de instrucciones del chat.');
        }

        $hoy = Carbon::now((string) config('consultorio.zona_horaria'))->locale('es');

        // strtr hace un solo recorrido: un valor insertado no se vuelve a interpretar
        $texto = strtr($plantilla, [
            '{{nombre}}' => (string) config('consultorio.nombre'),
            '{{subtitulo}}' => (string) config('consultorio.subtitulo'),
            '{{direccion}}' => (string) config('consultorio.direccion'),
            '{{horario}}' => $this->horario->resumen(),
            '{{servicios}}' => $this->catalogo(),
            '{{promociones}}' => $this->bloquePromociones(),
            '{{fecha}}' => $hoy->translatedFormat('l j \d\e F \d\e Y'),
        ]);

        // Sin promociones el bloque queda vacío: sin renglones en blanco de sobra
        return trim((string) preg_replace("/\n{3,}/", "\n\n", $texto));
    }

    /** Anuncios y promociones vigentes; sin ninguno, nada (el asistente no menciona promociones) */
    private function bloquePromociones(): string
    {
        $lineas = $this->promociones->lineas();

        return $lineas === '' ? '' : "Promociones y avisos vigentes:\n" . $lineas;
    }

    /**
     * Catálogo de servicios con su id, de la base de datos: ahorra una llamada a la IA por
     * conversación (cada una pesa miles de tokens). Lo escribe la clínica, no el visitante, pero
     * entra como dato: limpio, recortado y sin lo que parezca una instrucción.
     */
    private function catalogo(): string
    {
        try {
            $servicios = Servicio::where('activo', true)
                ->whereHas('especialidad', fn ($q) => $q->where('activo', true))
                ->orderBy('nombre')
                ->orderBy('id')
                ->limit(40)
                ->get(['id', 'nombre', 'duracion_minutos']);
        } catch (\Throwable $e) {
            return '(consulta la lista con la herramienta listar_servicios)';
        }

        $lineas = [];

        foreach ($servicios as $servicio) {
            if ($this->datos->pareceInstruccion($servicio->nombre)) {
                $this->datos->registrarOmitido('el servicio', (int) $servicio->id);

                continue;
            }

            $nombre = $this->datos->limpiar($servicio->nombre, (int) config('chat.instrucciones.titulo_max'));

            if ($nombre !== '') {
                $lineas[] = '- id ' . $servicio->id . ': ' . $nombre . ' (' . $servicio->duracion_minutos . ' min)';
            }
        }

        return $lineas === []
            ? '(por ahora no hay servicios disponibles para agendar por el chat)'
            : implode("\n", $lineas);
    }
}
