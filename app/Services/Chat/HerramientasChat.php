<?php

namespace App\Services\Chat;

use App\Services\Chat\Herramientas\AgendarCita;
use App\Services\Chat\Herramientas\HerramientaChat;
use App\Services\Chat\Herramientas\ListarServicios;
use App\Services\Chat\Herramientas\VerHorariosLibres;
use Illuminate\Support\Facades\Log;

/**
 * Las ÚNICAS herramientas del chat. No hay (ni debe haber) herramientas para cancelar,
 * reprogramar o consultar citas: por chat no hay forma segura de verificar quién es el paciente.
 * Cualquier otro nombre que pida el modelo se rechaza.
 */
class HerramientasChat
{
    /** @var array<string, HerramientaChat> */
    private array $herramientas = [];

    public function __construct(ListarServicios $servicios, VerHorariosLibres $horarios, AgendarCita $agendar)
    {
        foreach ([$servicios, $horarios, $agendar] as $herramienta) {
            $this->herramientas[$herramienta->definicion()->nombre] = $herramienta;
        }
    }

    /** @return \App\Services\IA\Datos\HerramientaIA[] */
    public function definiciones(): array
    {
        return array_values(array_map(fn (HerramientaChat $h) => $h->definicion(), $this->herramientas));
    }

    /** Nunca lanza excepciones: un fallo se devuelve como error para que la IA lo explique con calma */
    public function ejecutar(string $nombre, array $argumentos, ContextoChat $contexto): array
    {
        $herramienta = $this->herramientas[$nombre] ?? null;

        if (!$herramienta) {
            return ['error' => 'herramienta_desconocida', 'mensaje' => 'Esa herramienta no existe. Solo puedes usar las que tienes disponibles.'];
        }

        try {
            return $herramienta->ejecutar($argumentos, $contexto);
        } catch (\Throwable $e) {
            // Solo el nombre de la herramienta (uno de los tres) y la clase del error: nada de lo escrito
            Log::warning('Chat: falló la herramienta ' . $nombre . ' (' . class_basename($e) . ')');

            return ['error' => 'herramienta_fallo', 'mensaje' => 'No se pudo completar esa consulta. Disculpa con el paciente o sugiere contactar al consultorio.'];
        }
    }
}
