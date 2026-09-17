<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PacienteController extends Controller
{
    // Consulta base: solo usuarios con rol paciente
    private function pacientes()
    {
        return User::where('role', 'paciente');
    }

    // Listar pacientes con buscador
    public function index(Request $request)
    {
        $query = $this->pacientes()->withCount('citas');

        // Cada palabra debe coincidir con algún campo (así "Ana López" encuentra nombre + apellidos)
        if ($request->filled('buscar')) {
            $palabras = preg_split('/\s+/', trim($request->buscar));

            foreach ($palabras as $palabra) {
                $buscar = '%' . $palabra . '%';

                $query->where(function ($q) use ($buscar) {
                    $q->where('name', 'like', $buscar)
                      ->orWhere('apellidos', 'like', $buscar)
                      ->orWhere('email', 'like', $buscar)
                      ->orWhere('telefono', 'like', $buscar);
                });
            }
        }

        $pacientes = $query->orderBy('name')
            ->orderBy('apellidos')
            ->paginate(15)
            ->withQueryString();

        return view('admin.pacientes.index', compact('pacientes'));
    }

    // Detalle del paciente: datos, expediente e historial de citas
    public function show($id)
    {
        $paciente = $this->pacientes()
            ->with([
                'expediente',
                'citas' => function ($q) {
                    $q->orderBy('fecha', 'desc')->orderBy('hora', 'desc');
                },
                'citas.servicio.especialidad',
                'citas.historial' => function ($q) {
                    $q->orderBy('created_at', 'desc');
                },
                'citas.historial.user',
            ])
            ->findOrFail($id);

        $resumen = [
            'total' => $paciente->citas->count(),
            'atendidas' => $paciente->citas->where('status', 'atendida')->count(),
            'canceladas' => $paciente->citas->where('status', 'cancelada')->count(),
        ];

        return view('admin.pacientes.show', compact('paciente', 'resumen'));
    }

    // Editar datos de contacto del paciente
    public function update(Request $request, $id)
    {
        $paciente = $this->pacientes()->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($paciente->id)],
            'telefono' => ['nullable', 'string', 'max:20', Rule::unique('users', 'telefono')->ignore($paciente->id)],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Ingresa un correo válido.',
            'email.unique' => 'Ya existe otro usuario con ese correo.',
            'telefono.max' => 'El teléfono no puede tener más de 20 caracteres.',
            'telefono.unique' => 'Ya existe otro usuario con ese teléfono.',
        ]);

        $paciente->update([
            'name' => $request->name,
            'apellidos' => $request->apellidos,
            'email' => $request->email,
            'telefono' => $request->telefono,
        ]);

        return redirect()->route('admin.pacientes.show', $paciente->id)
            ->with('success', 'Datos del paciente actualizados correctamente.');
    }

    // Activar / desactivar paciente
    public function toggle($id)
    {
        $paciente = $this->pacientes()->findOrFail($id);

        // La doctora nunca debe poder desactivar su propia cuenta
        if ($paciente->id === auth()->id()) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        $paciente->update(['activo' => !$paciente->activo]);

        $mensaje = $paciente->activo
            ? 'Paciente activado correctamente.'
            : 'Paciente desactivado. Ya no podrá iniciar sesión.';

        return back()->with('success', $mensaje);
    }
}
