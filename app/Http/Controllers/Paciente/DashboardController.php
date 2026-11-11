<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $pacienteId = auth()->id();

        // "Hoy" en la zona horaria del consultorio (la app corre en UTC)
        $hoy = Carbon::now((string) config('consultorio.zona_horaria'))->toDateString();

        // Próxima cita (la más cercana que esté pendiente o confirmada). Solo las del propio paciente
        $proximaCita = Cita::with(['servicio.especialidad'])
            ->where('paciente_id', $pacienteId)
            ->whereIn('status', ['pendiente', 'confirmada'])
            ->where('fecha', '>=', $hoy)
            ->orderBy('fecha', 'asc')
            ->orderBy('hora', 'asc')
            ->first();

        // Resumen: una sola consulta agrupada, sin cargar las citas en memoria
        $porEstado = Cita::where('paciente_id', $pacienteId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalCitas = (int) $porEstado->sum();            // todas, incluidas canceladas y reagendadas
        $totalAtendidas = (int) ($porEstado['atendida'] ?? 0);
        $totalCanceladas = (int) ($porEstado['cancelada'] ?? 0);

        return view('paciente.dashboard', compact('proximaCita', 'totalCitas', 'totalAtendidas', 'totalCanceladas'));
    }
}
