<?php

namespace App\Services\Chat;

use Carbon\Carbon;

/**
 * Botones que el SERVIDOR agrega a una respuesta del asistente. Se arman a partir de los resultados
 * de las herramientas (servicios y horarios reales), nunca de lo que escribe el modelo ni el visitante.
 * Cada opción es {texto, mensaje}: tocarla en el widget envía `mensaje` como un mensaje normal del
 * visitante (mismo flujo, mismos límites y misma confirmación de agendado).
 *
 * Los textos salen de datos de la clínica (nombres de servicios), así que pasan por DatosParaPrompt
 * (una sola línea, sin marcas) y las opciones que no caben en los límites se descartan.
 */
class OpcionesChat
{
    private const DIAS = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];

    private const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    public function __construct(private DatosParaPrompt $datos, private DatosCita $cita) {}

    /** @param array<int, array{nombre: string}> $servicios ya limpios, en el orden en que se listan */
    public function deServicios(array $servicios): array
    {
        $opciones = [];

        foreach ($servicios as $servicio) {
            $opciones[] = [
                'texto' => $servicio['nombre'],
                'mensaje' => 'Ver horarios de ' . $servicio['nombre'],
            ];
        }

        return $this->filtrar($opciones);
    }

    /** @param string[] $fechas AAAA-MM-DD ya validadas */
    public function deFechas(string $servicio, array $fechas): array
    {
        $opciones = [];

        foreach ($fechas as $fecha) {
            $opciones[] = [
                'texto' => $this->fechaCorta($fecha),
                'mensaje' => 'Ver horarios de ' . $servicio . ' el ' . $this->cita->fechaLegible($fecha),
            ];
        }

        return $this->filtrar($opciones);
    }

    /** @param string[] $horas HH:MM ya validadas (de los espacios libres) */
    public function deHorarios(string $servicio, string $fecha, array $horas): array
    {
        $opciones = [];

        foreach ($horas as $hora) {
            $legible = $this->cita->horaLegible($hora);
            $opciones[] = [
                'texto' => $legible,
                'mensaje' => 'Quiero agendar ' . $servicio . ' el ' . $this->cita->fechaLegible($fecha) . ' a las ' . $legible,
            ];
        }

        return $this->filtrar($opciones);
    }

    /** "lun 16 nov" */
    public function fechaCorta(string $fecha): string
    {
        $f = Carbon::createFromFormat('!Y-m-d', $fecha);

        return self::DIAS[(int) $f->format('w')] . ' ' . $f->format('j') . ' ' . self::MESES[(int) $f->format('n') - 1];
    }

    /** Textos limpios, sin duplicados, dentro de los límites y máximo config('chat.opciones.max') */
    private function filtrar(array $opciones): array
    {
        $textoMax = (int) config('chat.opciones.texto_max');
        $mensajeMax = (int) config('chat.opciones.mensaje_max');
        $limpias = [];
        $vistos = [];

        foreach ($opciones as $opcion) {
            $texto = $this->datos->limpiar($opcion['texto'], $textoMax);
            $mensaje = $this->datos->limpiar($opcion['mensaje'], 100000);

            if ($texto === '' || $mensaje === '' || mb_strlen($mensaje) > $mensajeMax || isset($vistos[$mensaje])
                || $this->datos->pareceInstruccion($opcion['mensaje'])) {
                continue;
            }

            $vistos[$mensaje] = true;
            $limpias[] = ['texto' => $texto, 'mensaje' => $mensaje];
        }

        return array_slice($limpias, 0, (int) config('chat.opciones.max'));
    }
}
