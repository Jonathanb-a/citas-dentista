<?php

namespace App\Http\Controllers\Asistente;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $hoy = Carbon::today();

        // Citas de hoy
        $citasHoy = Cita::whereDate('fecha', $hoy)->count();
        $citasPendientes = Cita::where('status', 'pendiente')->count();
        $citasAtendidas = Cita::whereDate('fecha', $hoy)->where('status', 'atendida')->count();
        $citasCanceladas = Cita::whereDate('fecha', $hoy)->where('status', 'cancelada')->count();

        // Lista de citas de hoy
        $citas = Cita::with(['paciente', 'servicio'])
            ->whereDate('fecha', $hoy)
            ->orderBy('hora', 'asc')
            ->get();

        // Citas del mes para el calendario
        $citasMes = Cita::with(['paciente', 'servicio'])
            ->whereIn('status', ['pendiente', 'confirmada', 'atendida'])
            ->orderBy('hora')
            ->get()
            ->map(function ($cita) {
                return [
                    'id' => $cita->id,
                    'fecha' => Carbon::parse($cita->fecha)->format('Y-m-d'),
                    'hora' => Carbon::parse($cita->hora)->format('H:i'),
                    'hora_formato' => Carbon::parse($cita->hora)->format('h:i A'),
                    'paciente' => $cita->paciente->name . ' ' . $cita->paciente->apellidos,
                    'servicio' => $cita->servicio->nombre,
                    'status' => $cita->status,
                ];
            });

        return view('asistente.dashboard', compact(
            'citasHoy',
            'citasPendientes',
            'citasAtendidas',
            'citasCanceladas',
            'citas',
            'citasMes'
        ));
    }
}