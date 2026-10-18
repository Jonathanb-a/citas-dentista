<?php

namespace App\Services;

use App\Models\Bloqueo;
use App\Models\Cita;
use App\Models\HistorialCita;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BloqueoService
{
    const STATUS_ACTIVOS = ['pendiente', 'confirmada'];

    /**
     * Citas activas que se traslapan con un bloqueo que aún no se guarda.
     */
    public function citasEnConflicto(array $datos): Collection
    {
        $bloqueo = new Bloqueo($datos);

        return Cita::with(['paciente', 'servicio'])
            ->whereIn('status', self::STATUS_ACTIVOS)
            ->whereDate('fecha', '>=', $bloqueo->fecha_inicio->toDateString())
            ->whereDate('fecha', '<=', $bloqueo->fecha_fin->toDateString())
            ->orderBy('fecha')
            ->orderBy('hora')
            ->get()
            ->filter(fn ($cita) => $bloqueo->chocaConCita($cita))
            ->values();
    }

    /**
     * Guarda el bloqueo. Si hay citas en el periodo y la doctora aún no decidió
     * qué hacer con ellas, no guarda nada y devuelve las citas para mostrar el aviso.
     *
     * $accion: null (sin decidir), 'conservar' o 'cancelar'.
     * $citasVistas: ids de las citas que el aviso le mostró; solo esas se cancelan.
     */
    public function guardar(array $datos, ?string $accion, array $citasVistas, int $userId): array
    {
        $conflictos = $this->citasEnConflicto($datos);

        $vistas = array_map('intval', $citasVistas);
        $hayNuevas = $conflictos->contains(fn ($cita) => !in_array($cita->id, $vistas, true));

        // Sin decisión, o aparecieron citas que no vio: se vuelve a avisar
        if ($conflictos->isNotEmpty() && ($accion === null || $hayNuevas)) {
            return ['conflictos' => $conflictos];
        }

        return DB::transaction(function () use ($datos, $accion, $conflictos, $userId) {
            $bloqueo = Bloqueo::create($datos + ['creado_por' => $userId]);

            $canceladas = 0;
            if ($accion === 'cancelar') {
                foreach ($conflictos as $cita) {
                    $this->cancelarPorBloqueo($cita, $bloqueo, $userId);
                    $canceladas++;
                }
            }

            return [
                'bloqueo' => $bloqueo,
                'canceladas' => $canceladas,
                'conservadas' => $accion === 'cancelar' ? 0 : $conflictos->count(),
            ];
        });
    }

    private function cancelarPorBloqueo(Cita $cita, Bloqueo $bloqueo, int $userId): void
    {
        $cita->update(['status' => 'cancelada']);

        $descripcion = 'Cita cancelada por bloqueo de agenda';
        if ($bloqueo->motivo) {
            $descripcion .= ' (' . $bloqueo->motivo . ')';
        }

        HistorialCita::create([
            'cita_id' => $cita->id,
            'user_id' => $userId,
            'accion' => 'cancelada',
            'descripcion' => $descripcion . '.',
        ]);
    }

    /**
     * Ids de las citas activas de la lista que caen en un bloqueo vigente.
     * Una sola consulta de bloqueos para toda la lista.
     */
    public function idsDeCitasBloqueadas(Collection $citas): array
    {
        $bloqueos = Bloqueo::vigentes()->get();

        if ($bloqueos->isEmpty()) {
            return [];
        }

        return $citas
            ->filter(fn ($cita) => in_array($cita->status, self::STATUS_ACTIVOS, true)
                && $cita->fecha->gte(Carbon::today())
                && $bloqueos->contains(fn ($bloqueo) => $bloqueo->chocaConCita($cita)))
            ->pluck('id')
            ->all();
    }
}
