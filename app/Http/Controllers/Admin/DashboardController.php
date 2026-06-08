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

        return view('admin.dashboard', compact(
            'citasHoy',
            'citasPendientes',
            'citasAtendidas',
            'citasCanceladas',
            'totalPacientes',
            'citas'
        ));
    }
}