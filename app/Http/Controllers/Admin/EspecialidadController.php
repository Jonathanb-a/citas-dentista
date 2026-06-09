<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Especialidad;
use Illuminate\Http\Request;

class EspecialidadController extends Controller
{
    public function index()
    {
        $especialidades = Especialidad::withCount('servicios')->latest()->get();
        return view('admin.especialidades.index', compact('especialidades'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
        ], [
            'nombre.required' => 'El nombre de la especialidad es obligatorio.',
        ]);

        Especialidad::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'activo' => true,
        ]);

        return redirect()->route('admin.especialidades.index')
            ->with('success', 'Especialidad creada correctamente.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
        ]);

        $especialidad = Especialidad::findOrFail($id);
        $especialidad->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
        ]);

        return redirect()->route('admin.especialidades.index')
            ->with('success', 'Especialidad actualizada correctamente.');
    }

    public function toggle($id)
    {
        $especialidad = Especialidad::findOrFail($id);
        $especialidad->update(['activo' => !$especialidad->activo]);

        return redirect()->route('admin.especialidades.index')
            ->with('success', 'Estado actualizado correctamente.');
    }

    public function destroy($id)
    {
        $especialidad = Especialidad::findOrFail($id);
        $especialidad->delete();

        return redirect()->route('admin.especialidades.index')
            ->with('success', 'Especialidad eliminada correctamente.');
    }
}