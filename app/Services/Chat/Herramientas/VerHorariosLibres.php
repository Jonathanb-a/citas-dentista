<?php

namespace App\Services\Chat\Herramientas;

use App\Services\Chat\ContextoChat;
use App\Services\Chat\DatosCita;
use App\Services\Chat\OpcionesChat;
use App\Services\Chat\TextoPlano;
use App\Services\DisponibilidadService;
use App\Services\IA\Datos\HerramientaIA;

/** Espacios libres de un día, siempre por DisponibilidadService (bloqueos, citas, descansos…) */
class VerHorariosLibres implements HerramientaChat
{
    public function __construct(private DatosCita $datos, private DisponibilidadService $disponibilidad, private OpcionesChat $opciones) {}

    public function definicion(): HerramientaIA
    {
        return new HerramientaIA(
            'ver_horarios_libres',
            'Devuelve los horarios libres de un servicio en una fecha (desde mañana y hasta 3 meses). '
            . 'Si el paciente solo quiere ver horarios y no dio fecha, llámala SIN fecha: devuelve los próximos días con lugar. '
            . 'Lista en texto, en orden, los horarios que devuelve.',
            [
                'type' => 'object',
                'properties' => [
                    'servicio_id' => ['type' => 'integer', 'description' => 'Id del servicio (de listar_servicios o del catálogo)'],
                    'fecha' => ['type' => 'string', 'description' => 'Fecha en formato AAAA-MM-DD. Opcional: sin fecha devuelve los próximos días con lugar'],
                ],
                'required' => ['servicio_id'],
            ],
        );
    }

    public function ejecutar(array $argumentos, ContextoChat $contexto): array
    {
        $servicio = $this->datos->servicio($argumentos['servicio_id'] ?? null);

        if (!$servicio) {
            return ['error' => 'servicio_invalido', 'mensaje' => 'Ese servicio no existe o no está disponible. Usa un id de listar_servicios.'];
        }

        // Sin fecha (solo consultar): los próximos días con lugar, sin pedirle nada más al paciente
        if (!array_key_exists('fecha', $argumentos) || $argumentos['fecha'] === null || $argumentos['fecha'] === '') {
            return $this->proximosDias($servicio, $contexto);
        }

        $fecha = $this->datos->fecha($argumentos['fecha']);

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
        } else {
            // Un botón por horario (hora real de los espacios libres), debajo de la respuesta
            $contexto->opciones = $this->opciones->deHorarios(
                $resultado['servicio'],
                $fecha['fecha'],
                array_column($resultado['espacios'], 'hora'),
            );
            $resultado['botones'] = 'El sistema mostrará debajo de tu respuesta un botón por horario. '
                . 'Lista los horarios en texto, en este orden, sin escribir botones, y ofrece agendar.';
        }

        return $resultado;
    }

    /** Los primeros días con lugar desde mañana (por DisponibilidadService, que respeta bloqueos y citas) */
    private function proximosDias($servicio, ContextoChat $contexto): array
    {
        $desde = $this->datos->hoy()->addDays((int) config('chat.agenda.dias_minimos'));
        $dias = [];
        $maxDias = (int) config('chat.opciones.max_dias');

        for ($i = 0; $i < (int) config('chat.opciones.dias_busqueda') && count($dias) < $maxDias; $i++) {
            $fecha = $desde->copy()->addDays($i)->toDateString();

            // Respeta el límite de 3 meses por si la ventana de búsqueda se ampliara
            if (isset($this->datos->fecha($fecha)['error'])) {
                break;
            }

            $libres = count($this->disponibilidad->espaciosDisponibles($fecha, $servicio->id));

            if ($libres > 0) {
                $dias[] = ['fecha' => $fecha, 'fecha_legible' => $this->datos->fechaLegible($fecha), 'espacios_libres' => $libres];
            }
        }

        $nombre = TextoPlano::limpiar($servicio->nombre);

        if ($dias === []) {
            return [
                'servicio' => $nombre,
                'proximos_dias' => [],
                'mensaje' => 'No hay lugar en los próximos ' . (int) config('chat.opciones.dias_busqueda') . ' días. Dile al paciente que pruebe con una fecha más lejana o contacte al consultorio.',
            ];
        }

        $contexto->opciones = $this->opciones->deFechas($nombre, array_column($dias, 'fecha'));

        return [
            'servicio' => $nombre,
            'proximos_dias' => $dias,
            'botones' => 'El sistema mostrará debajo de tu respuesta un botón por día. '
                . 'Lista los días en texto, en este orden, sin escribir botones; pregunta cuál le conviene u ofrece agendar.',
        ];
    }
}
