<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cita;
use App\Models\HistorialCita;
use Carbon\Carbon;

class LiberarCitasNoConfirmadas extends Command
{
    protected $signature = 'citas:liberar-no-confirmadas';

    protected $description = 'Libera las citas confirmadas por la clínica que el paciente no confirmó dentro del límite de 24hrs';

    public function handle()
    {
        // Buscar citas que:
        // - Están confirmadas por la clínica
        // - El paciente NO confirmó
        // - Ya pasó el límite de confirmación
        $citasVencidas = Cita::where('status', 'confirmada')
            ->where('confirmado_por_paciente', false)
            ->whereNotNull('confirmacion_limite')
            ->where('confirmacion_limite', '<', Carbon::now())
            ->get();

        if ($citasVencidas->isEmpty()) {
            $this->info('No hay citas para liberar.');
            return;
        }

        $contador = 0;

        foreach ($citasVencidas as $cita) {
            $cita->update(['status' => 'cancelada']);

            HistorialCita::create([
                'cita_id' => $cita->id,
                'user_id' => null,
                'accion' => 'cancelada',
                'descripcion' => 'Cita liberada automáticamente: el paciente no confirmó su asistencia dentro del plazo de 24hrs.',
            ]);

            // TODO: Webhook n8n — avisar a la doctora que se liberó esta cita

            $contador++;
        }

        $this->info("Se liberaron {$contador} cita(s) no confirmada(s).");
    }
}