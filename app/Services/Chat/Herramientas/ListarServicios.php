<?php

namespace App\Services\Chat\Herramientas;

use App\Models\Servicio;
use App\Services\Chat\ContextoChat;
use App\Services\Chat\TextoPlano;
use App\Services\IA\Datos\HerramientaIA;
use Illuminate\Support\Str;

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

    public function ejecutar(array $argumentos, ContextoChat $contexto): array
    {
        $servicios = Servicio::with('especialidad')
            ->where('activo', true)
            ->whereHas('especialidad', fn ($q) => $q->where('activo', true))
            ->orderBy('nombre')
            ->limit(40)
            ->get();

        return [
            'servicios' => $servicios->map(fn (Servicio $s) => [
                'id' => $s->id,
                'nombre' => TextoPlano::limpiar($s->nombre),
                'especialidad' => TextoPlano::limpiar($s->especialidad->nombre),
                'duracion_minutos' => $s->duracion_minutos,
                'descripcion' => Str::limit((string) TextoPlano::limpiar((string) $s->descripcion), 160),
            ])->values()->all(),
        ];
    }
}
