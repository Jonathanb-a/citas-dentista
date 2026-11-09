<?php

namespace App\Services\Chat\Herramientas;

use App\Services\Chat\ContextoChat;
use App\Services\Chat\DatosCita;
use App\Services\Chat\TextoPlano;
use App\Services\DisponibilidadService;
use App\Services\IA\Datos\HerramientaIA;

/** Espacios libres de un día, siempre por DisponibilidadService (bloqueos, citas, descansos…) */
class VerHorariosLibres implements HerramientaChat
{
    public function __construct(private DatosCita $datos, private DisponibilidadService $disponibilidad) {}

    public function definicion(): HerramientaIA
    {
        return new HerramientaIA(
            'ver_horarios_libres',
            'Devuelve los horarios libres de un servicio en una fecha. La fecha debe ser desde mañana y hasta 3 meses. '
            . 'Ofrece al paciente como máximo 3 de ellos.',
            [
                'type' => 'object',
                'properties' => [
                    'servicio_id' => ['type' => 'integer', 'description' => 'Id del servicio (de listar_servicios o del catálogo)'],
                    'fecha' => ['type' => 'string', 'description' => 'Fecha en formato AAAA-MM-DD'],
                ],
                'required' => ['servicio_id', 'fecha'],
            ],
        );
    }

    public function ejecutar(array $argumentos, ContextoChat $contexto): array
    {
        $servicio = $this->datos->servicio($argumentos['servicio_id'] ?? null);

        if (!$servicio) {
            return ['error' => 'servicio_invalido', 'mensaje' => 'Ese servicio no existe o no está disponible. Usa un id de listar_servicios.'];
        }

        $fecha = $this->datos->fecha($argumentos['fecha'] ?? null);

        if (isset($fecha['error'])) {
            return ['error' => 'fecha_invalida', 'mensaje' => match ($fecha['error']) {
                'formato' => 'La fecha debe tener el formato AAAA-MM-DD y existir en el calendario.',
                'pasada' => 'Solo se agenda desde mañana; hoy y las fechas pasadas no están disponibles.',
                default => 'Solo se puede agendar con hasta 3 meses de anticipación.',
            }];
        }

        $espacios = $this->disponibilidad->espaciosDisponibles($fecha['fecha'], $servicio->id);
        $maximo = (int) config('chat.agenda.max_horarios');

        $resultado = [
            'servicio' => TextoPlano::limpiar($servicio->nombre),
            'fecha' => $fecha['fecha'],
            'fecha_legible' => $this->datos->fechaLegible($fecha['fecha']),
            // Solo hora y formato legible: el horario_id lo deriva el servidor al agendar
            'espacios' => array_map(
                fn (array $e) => ['hora' => $e['hora'], 'hora_formato' => $e['hora_formato']],
                array_slice($espacios, 0, $maximo),
            ),
            'hay_mas' => count($espacios) > $maximo,
        ];

        if ($espacios === []) {
            $resultado['mensaje'] = 'No hay espacios libres ese día. Ofrece otra fecha.';
        }

        return $resultado;
    }
}
