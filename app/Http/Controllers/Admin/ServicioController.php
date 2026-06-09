<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Servicio;
use App\Models\Especialidad;
use Illuminate\Http\Request;

class ServicioController extends Controller
{
    public function index()
    {
        $servicios = Servicio::with('especialidad')->latest()->get();
        $especialidades = Especialidad::where('activo', true)->get();
        return view('admin.servicios.index', compact('servicios', 'especialidades'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'especialidad_id' => 'required|exists:especialidades,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'duracion_minutos' => 'required|integer|min:5|max:480',
        ], [
            'especialidad_id.required' => 'Debes seleccionar una especialidad.',
            'especialidad_id.exists' => 'La especialidad seleccionada no es válida.',
            'nombre.required' => 'El nombre del servicio es obligatorio.',
            'duracion_minutos.required' => 'La duración es obligatoria.',
            'duracion_minutos.min' => 'La duración mínima es de 5 minutos.',
            'duracion_minutos.max' => 'La duración máxima es de 480 minutos (8 horas).',
        ]);

        Servicio::create([
            'especialidad_id' => $request->especialidad_id,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'duracion_minutos' => $request->duracion_minutos,
            'activo' => true,
        ]);

        return redirect()->route('admin.servicios.index')
            ->with('success', 'Servicio creado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'especialidad_id' => 'required|exists:especialidades,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'duracion_minutos' => 'required|integer|min:5|max:480',
        ]);

        $servicio = Servicio::findOrFail($id);
        $servicio->update([
            'especialidad_id' => $request->especialidad_id,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'duracion_minutos' => $request->duracion_minutos,
        ]);

        return redirect()->route('admin.servicios.index')
            ->with('success', 'Servicio actualizado correctamente.');
    }

    public function toggle($id)
    {
        $servicio = Servicio::findOrFail($id);
        $servicio->update(['activo' => !$servicio->activo]);

        return redirect()->route('admin.servicios.index')
            ->with('success', 'Estado actualizado correctamente.');
    }

    public function destroy($id)
    {
        $servicio = Servicio::findOrFail($id);
        $servicio->delete();

        return redirect()->route('admin.servicios.index')
            ->with('success', 'Servicio eliminado correctamente.');
    }
}