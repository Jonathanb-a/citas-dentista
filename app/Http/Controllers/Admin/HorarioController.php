<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Horario;
use Illuminate\Http\Request;

class HorarioController extends Controller
{
    public function index()
    {
        $dias = ['lunes','martes','miercoles','jueves','viernes','sabado','domingo'];

        $horarios = Horario::orderByRaw("FIELD(dia_semana, 'lunes','martes','miercoles','jueves','viernes','sabado','domingo')")
            ->orderBy('hora_inicio')
            ->get()
            ->groupBy('dia_semana');

        return view('admin.horarios.index', compact('horarios', 'dias'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'dia_semana' => 'required|in:lunes,martes,miercoles,jueves,viernes,sabado,domingo',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
        ], [
            'dia_semana.required' => 'Debes seleccionar un día.',
            'hora_inicio.required' => 'La hora de inicio es obligatoria.',
            'hora_fin.required' => 'La hora de fin es obligatoria.',
            'hora_fin.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ]);

        Horario::create([
            'dia_semana' => $request->dia_semana,
            'hora_inicio' => $request->hora_inicio,
            'hora_fin' => $request->hora_fin,
            'disponible' => true,
        ]);

        return redirect()->route('admin.horarios.index')
            ->with('success', 'Horario agregado correctamente.');
    }

    public function toggle($id)
    {
        $horario = Horario::findOrFail($id);
        $horario->update(['disponible' => !$horario->disponible]);

        return redirect()->route('admin.horarios.index')
            ->with('success', 'Estado actualizado correctamente.');
    }

    public function destroy($id)
    {
        $horario = Horario::findOrFail($id);
        $horario->delete();

        return redirect()->route('admin.horarios.index')
            ->with('success', 'Horario eliminado correctamente.');
    }
}