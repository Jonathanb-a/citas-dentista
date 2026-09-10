<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $hoy = Carbon::today();

        // Estadisticas del dia
        $citasHoy = Cita::whereDate('fecha', $hoy)->count();
        $citasPendientes = Cita::whereDate('fecha', $hoy)->where('status', 'pendiente')->count();
        $citasAtendidas = Cita::whereDate('fecha', $hoy)->where('status', 'atendida')->count();
        $citasCanceladas = Cita::whereDate('fecha', $hoy)->where('status', 'cancelada')->count();

        // Total pacientes
        $totalPacientes = User::where('role', 'paciente')->count();

        // Citas de hoy con detalle
        $citas = Cita::with(['paciente', 'servicio'])
            ->whereDate('fecha', $hoy)
            ->orderBy('hora')
            ->get();

        // Citas del mes para el calendario (todas, con detalle)
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
                    'motivo' => $cita->motivo,
                ];
            });

        return view('admin.dashboard', compact(
            'citasHoy',
            'citasPendientes',
            'citasAtendidas',
            'citasCanceladas',
            'totalPacientes',
            'citas',
            'citasMes'
        ));
    }
}