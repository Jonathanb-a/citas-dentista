<?php

namespace App\Services\Chat\Herramientas;

use App\Models\Servicio;
use App\Services\Chat\ContextoChat;
use App\Services\Chat\DatosParaPrompt;
use App\Services\IA\Datos\HerramientaIA;

/** Servicios que el paciente puede agendar (activos, de especialidades activas). Sin precio: el sistema no lo maneja */
class ListarServicios implements HerramientaChat
{
    public function definicion(): HerramientaIA
    {
        return new HerramientaIA(
            'listar_servicios',
            'Lista los servicios del consultorio que se pueden agendar, con su id, especialidad y duración en minutos. '
            . 'No incluye precios. Úsala si no tienes claro qué servicio quiere el paciente o necesitas un id.',
        );
    }

    public function __construct(private DatosParaPrompt $datos) {}

    public function ejecutar(array $argumentos, ContextoChat $contexto): array
    {
        $servicios = Servicio::with('especialidad')
            ->where('activo', true)
            ->whereHas('especialidad', fn ($q) => $q->where('activo', true))
            ->orderBy('nombre')
            ->orderBy('id')
            ->limit(40)
            ->get();

        $lista = [];

        foreach ($servicios as $servicio) {
            // Los textos de la clínica son datos, no órdenes: se limpian y, si parecen una instrucción, el servicio se omite
            if ($this->datos->pareceInstruccion($servicio->nombre)
                || $this->datos->pareceInstruccion($servicio->descripcion)
                || $this->datos->pareceInstruccion($servicio->especialidad->nombre)) {
                $this->datos->registrarOmitido('el servicio', (int) $servicio->id);

                continue;
            }

            $lista[] = [
                'id' => $servicio->id,
                'nombre' => $this->datos->limpiar($servicio->nombre, (int) config('chat.instrucciones.titulo_max')),
                'especialidad' => $this->datos->limpiar($servicio->especialidad->nombre, (int) config('chat.instrucciones.titulo_max')),
                'duracion_minutos' => $servicio->duracion_minutos,
                'descripcion' => $this->datos->limpiar($servicio->descripcion, (int) config('chat.instrucciones.descripcion_max')),
            ];
        }

        return ['servicios' => $lista];
    }
}
