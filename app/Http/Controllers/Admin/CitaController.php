<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\HistorialCita;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CitaController extends Controller
{
    // Listar todas las citas
    public function index(Request $request)
    {
        $query = Cita::with(['paciente', 'servicio.especialidad']);

        // Filtro por status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtro por fecha
        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->fecha);
        }

        $citas = $query->orderBy('fecha', 'desc')
            ->orderBy('hora', 'desc')
            ->get();

        return view('admin.citas.index', compact('citas'));
    }

    // Confirmar cita (la doctora acepta)
    public function confirmar($id)
    {
        $cita = Cita::findOrFail($id);

        if ($cita->status !== 'pendiente') {
            return back()->with('error', 'Solo se pueden confirmar citas pendientes.');
        }

        $cita->update(['status' => 'confirmada']);

        HistorialCita::create([
            'cita_id' => $cita->id,
            'user_id' => auth()->id(),
            'accion' => 'confirmada',
            'descripcion' => 'La cita fue confirmada por ' . auth()->user()->name . '.',
        ]);

        // TODO: Webhook n8n — notificar confirmación al paciente

        return back()->with('success', 'Cita confirmada correctamente.');
    }

    // Marcar como atendida
    public function atender($id)
    {
        $cita = Cita::findOrFail($id);

        if (!in_array($cita->status, ['confirmada', 'pendiente'])) {
            return back()->with('error', 'Esta cita no se puede marcar como atendida.');
        }

        $cita->update(['status' => 'atendida']);

        HistorialCita::create([
            'cita_id' => $cita->id,
            'user_id' => auth()->id(),
            'accion' => 'atendida',
            'descripcion' => 'La cita fue marcada como atendida.',
        ]);

        return back()->with('success', 'Cita marcada como atendida.');
    }

    // Cancelar cita
    public function cancelar($id)
    {
        $cita = Cita::findOrFail($id);

        if (in_array($cita->status, ['atendida', 'cancelada'])) {
            return back()->with('error', 'Esta cita no se puede cancelar.');
        }

        $cita->update(['status' => 'cancelada']);

        HistorialCita::create([
            'cita_id' => $cita->id,
            'user_id' => auth()->id(),
            'accion' => 'cancelada',
            'descripcion' => 'La cita fue cancelada por ' . auth()->user()->name . '.',
        ]);

        // TODO: Webhook n8n — notificar cancelación al paciente

        return back()->with('success', 'Cita cancelada correctamente.');
    }
}