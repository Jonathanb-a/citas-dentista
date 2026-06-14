<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Expediente;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $pacienteId = auth()->id();

        // Próxima cita (la más cercana que esté pendiente o confirmada)
        $proximaCita = Cita::with(['servicio.especialidad'])
            ->where('paciente_id', $pacienteId)
            ->whereIn('status', ['pendiente', 'confirmada'])
            ->where('fecha', '>=', Carbon::today())
            ->orderBy('fecha', 'asc')
            ->orderBy('hora', 'asc')
            ->first();

        // Conteos
        $totalPendientes = Cita::where('paciente_id', $pacienteId)
            ->where('status', 'pendiente')->count();

        $totalConfirmadas = Cita::where('paciente_id', $pacienteId)
            ->where('status', 'confirmada')->count();

        $totalAtendidas = Cita::where('paciente_id', $pacienteId)
            ->where('status', 'atendida')->count();

        // Expediente (para mostrar notas de la doctora)
        $expediente = Expediente::where('paciente_id', $pacienteId)->first();

        return view('paciente.dashboard', compact(
            'proximaCita',
            'totalPendientes',
            'totalConfirmadas',
            'totalAtendidas',
            'expediente'
        ));
    }
}