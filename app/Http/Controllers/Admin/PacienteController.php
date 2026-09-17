<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expediente;
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
                'expediente.actualizadoPor',
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

    // Guardar la historia clínica; si el paciente no tiene expediente, se crea
    public function actualizarExpediente(Request $request, $id)
    {
        $paciente = $this->pacientes()->with('expediente')->findOrFail($id);

        $booleanos = ['embarazada', 'fuma', 'alcohol', 'bruxismo', 'usa_hilo_dental'];

        $datos = $request->validateWithBag('expediente', [
            'direccion' => 'nullable|string|max:255',
            'contacto_emergencia_nombre' => 'nullable|string|max:255',
            'contacto_emergencia_telefono' => 'nullable|string|max:20',
            'tipo_sangre' => ['nullable', Rule::in(Expediente::TIPOS_SANGRE)],
            'antecedentes_familiares' => 'nullable|string|max:5000',
            'alergias' => 'nullable|string|max:5000',
            'enfermedades' => 'nullable|string|max:5000',
            'medicamentos' => 'nullable|string|max:5000',
            'cirugias_previas' => 'nullable|string|max:5000',
            'frecuencia_cepillado' => ['nullable', Rule::in(Expediente::FRECUENCIAS_CEPILLADO)],
            'padecimiento_actual' => 'nullable|string|max:5000',
            'diagnostico' => 'nullable|string|max:5000',
            'plan_tratamiento' => 'nullable|string|max:5000',
            'notas_doctora' => 'nullable|string|max:5000',
            'observaciones' => 'nullable|string|max:5000',
            'embarazada' => 'nullable|boolean',
            'fuma' => 'nullable|boolean',
            'alcohol' => 'nullable|boolean',
            'bruxismo' => 'nullable|boolean',
            'usa_hilo_dental' => 'nullable|boolean',
        ], [
            'max' => 'El campo :attribute no puede tener más de :max caracteres.',
            'tipo_sangre.in' => 'Selecciona un tipo de sangre válido.',
            'frecuencia_cepillado.in' => 'Selecciona una frecuencia de cepillado válida.',
            'boolean' => 'El campo :attribute debe ser sí o no.',
        ], [
            'direccion' => 'dirección',
            'contacto_emergencia_nombre' => 'nombre del contacto de emergencia',
            'contacto_emergencia_telefono' => 'teléfono del contacto de emergencia',
            'antecedentes_familiares' => 'antecedentes heredofamiliares',
            'cirugias_previas' => 'cirugías previas',
            'padecimiento_actual' => 'padecimiento actual',
            'diagnostico' => 'diagnóstico',
            'plan_tratamiento' => 'plan de tratamiento',
            'notas_doctora' => 'notas de la doctora',
            'usa_hilo_dental' => 'usa hilo dental',
        ]);

        // Un checkbox sin marcar no se envía: se guarda como "no"
        foreach ($booleanos as $campo) {
            $datos[$campo] = $request->boolean($campo);
        }

        // El embarazo no aplica a pacientes de sexo masculino
        $sexo = $paciente->sexo ?? $paciente->expediente?->sexo;
        if ($sexo === 'masculino') {
            $datos['embarazada'] = null;
        }

        // Trazabilidad: quién y cuándo modificó el expediente
        $datos['actualizado_por'] = auth()->id();
        $datos['ultima_actualizacion'] = now();

        Expediente::updateOrCreate(['paciente_id' => $paciente->id], $datos);

        return redirect()->route('admin.pacientes.show', $paciente->id)
            ->with('success', 'Expediente actualizado correctamente.');
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
