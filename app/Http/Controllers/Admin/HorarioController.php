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
            'dias' => 'required|array|min:1',
            'dias.*' => 'in:lunes,martes,miercoles,jueves,viernes,sabado,domingo',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'descanso_minutos' => 'nullable|integer|min:0|max:120',
        ], [
            'dias.required' => 'Debes seleccionar al menos un día.',
            'dias.min' => 'Debes seleccionar al menos un día.',
            'hora_inicio.required' => 'La hora de inicio es obligatoria.',
            'hora_fin.required' => 'La hora de fin es obligatoria.',
            'hora_fin.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ]);

        foreach ($request->dias as $dia) {
            Horario::create([
                'dia_semana' => $dia,
                'hora_inicio' => $request->hora_inicio,
                'hora_fin' => $request->hora_fin,
                'descanso_minutos' => $request->descanso_minutos ?? 0,
                'disponible' => true,
            ]);
        }

        $cantidad = count($request->dias);
        $mensaje = $cantidad === 1
            ? 'Horario agregado correctamente.'
            : "Horario agregado a {$cantidad} días correctamente.";

        return redirect()->route('admin.horarios.index')->with('success', $mensaje);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'descanso_minutos' => 'nullable|integer|min:0|max:120',
        ], [
            'hora_inicio.required' => 'La hora de inicio es obligatoria.',
            'hora_fin.required' => 'La hora de fin es obligatoria.',
            'hora_fin.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ]);

        $horario = Horario::findOrFail($id);
        $horario->update([
            'hora_inicio' => $request->hora_inicio,
            'hora_fin' => $request->hora_fin,
            'descanso_minutos' => $request->descanso_minutos ?? 0,
        ]);

        return redirect()->route('admin.horarios.index')
            ->with('success', 'Horario actualizado correctamente.');
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