<?php

namespace App\Http\Controllers\Admin;

use App\Services\DisponibilidadService;
use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\HistorialCita;
use App\Models\NotaEvolucion;
use App\Models\User;
use App\Models\Especialidad;
use App\Services\CitaManualService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CitaController extends Controller
{
    protected $citaManual;

    public function __construct(CitaManualService $citaManual)
    {
        $this->citaManual = $citaManual;
    }

    // Listar todas las citas
    public function index(Request $request)
    {
        $query = Cita::with(['paciente', 'servicio.especialidad', 'notaEvolucion']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->fecha);
        }

        $citas = $query->orderBy('fecha', 'desc')
            ->orderBy('hora', 'desc')
            ->get();

        return view('admin.citas.index', compact('citas'));
    }

    // Mostrar formulario para crear cita manual
    public function create()
    {
        $pacientes = User::where('role', 'paciente')
            ->where('activo', true)
            ->orderBy('name')
            ->get();

        $especialidades = Especialidad::where('activo', true)
            ->whereHas('servicios', function ($q) {
                $q->where('activo', true);
            })
            ->with(['servicios' => function ($q) {
                $q->where('activo', true);
            }])
            ->get();

        return view('admin.citas.create', compact('pacientes', 'especialidades'));
    }

    // Guardar cita manual
    public function store(Request $request)
    {
        $request->validate([
            'tipo_paciente' => 'required|in:existente,nuevo',
            'paciente_id' => 'required_if:tipo_paciente,existente|nullable|exists:users,id',
            'nuevo_nombre' => 'required_if:tipo_paciente,nuevo|nullable|string|max:255',
            'nuevo_apellidos' => 'required_if:tipo_paciente,nuevo|nullable|string|max:255',
            'nuevo_email' => 'required_if:tipo_paciente,nuevo|nullable|email|unique:users,email',
            'nuevo_telefono' => 'nullable|string|max:20|unique:users,telefono',
            'nuevo_sexo' => 'required_if:tipo_paciente,nuevo|nullable|in:masculino,femenino',
            'servicio_id' => 'required|exists:servicios,id',
            'horario_id' => 'required|exists:horarios,id',
            'fecha' => 'required|date|after_or_equal:today',
            'hora' => 'required|date_format:H:i',
            'status' => 'required|in:pendiente,confirmada',
            'motivo' => 'nullable|string|max:500',
        ], [
            'nuevo_email.unique' => 'Ya existe un paciente con ese correo.',
            'nuevo_telefono.unique' => 'Ya existe un paciente con ese teléfono.',
            'nuevo_sexo.required_if' => 'Selecciona el sexo del paciente.',
            'nuevo_sexo.in' => 'Selecciona una opción válida de sexo.',
        ]);

        $resultado = $this->citaManual->crear($request->all(), auth()->id());

        if (isset($resultado['error'])) {
            return back()->with('error', $resultado['error'])->withInput();
        }

        $redireccion = redirect()->route('admin.citas.index')
            ->with('success', 'Cita creada correctamente.');

        // La contraseña del paciente nuevo va en un panel aparte que no se desvanece
        if (!empty($resultado['password_temporal'])) {
            $redireccion->with('password_temporal', $resultado['password_temporal']);
        }

        return $redireccion;
    }

    // Calcular disponibilidad (AJAX)
    public function disponibilidad(Request $request, DisponibilidadService $disponibilidad)
    {
        $request->validate([
            'fecha' => 'required|date|after_or_equal:today',
            'servicio_id' => 'required|exists:servicios,id',
        ]);

        $espacios = $disponibilidad->espaciosDisponibles(
            $request->fecha,
            $request->servicio_id
        );

        return response()->json($espacios);
    }

    // Reagendar cita
    public function reagendar(Request $request, $id)
    {
        $request->validate([
            'fecha' => 'required|date|after_or_equal:today',
            'hora' => 'required|date_format:H:i',
            'horario_id' => 'required|exists:horarios,id',
        ], [
            'fecha.required' => 'Debes seleccionar una fecha.',
            'hora.required' => 'Debes seleccionar un horario.',
        ]);

        $resultado = $this->citaManual->reagendar($id, $request->all(), auth()->id());

        if (isset($resultado['error'])) {
            return back()->with('error', $resultado['error']);
        }

        return redirect()->route('admin.citas.index')
            ->with('success', 'Cita reagendada correctamente. El paciente deberá confirmar su asistencia nuevamente.');
    }

    // Confirmar cita (la doctora acepta)
    public function confirmar($id)
    {
        $cita = Cita::findOrFail($id);

        if ($cita->status !== 'pendiente') {
            return back()->with('error', 'Solo se pueden confirmar citas pendientes.');
        }

        $cita->update([
            'status' => 'confirmada',
            'confirmacion_limite' => Carbon::now()->addHours(24),
        ]);

        HistorialCita::create([
            'cita_id' => $cita->id,
            'user_id' => auth()->id(),
            'accion' => 'confirmada',
            'descripcion' => 'La cita fue confirmada por ' . auth()->user()->name . '.',
        ]);

        return back()->with('success', 'Cita confirmada correctamente.');
    }

    // Marcar como atendida
    public function atender(Request $request, $id)
    {
        $cita = Cita::findOrFail($id);

        if (!in_array($cita->status, ['confirmada', 'pendiente'])) {
            return back()->with('error', 'Esta cita no se puede marcar como atendida.');
        }

        // La nota de evolución es opcional: la doctora puede saltarla si va con prisa
        $datos = $request->validateWithBag('nota_' . $cita->id, NotaEvolucion::reglas(), NotaEvolucion::mensajes());

        $cita->update(['status' => 'atendida']);

        HistorialCita::create([
            'cita_id' => $cita->id,
            'user_id' => auth()->id(),
            'accion' => 'atendida',
            'descripcion' => 'La cita fue marcada como atendida.',
        ]);

        $nota = NotaEvolucion::guardarDeCita($cita, $datos, auth()->id());

        return back()->with('success', $nota
            ? 'Cita atendida y nota de evolución guardada.'
            : 'Cita marcada como atendida.');
    }

    // Agregar o editar la nota de evolución después (por si se saltó en el momento)
    public function guardarNota(Request $request, $id)
    {
        $cita = Cita::findOrFail($id);

        // Una nota describe una consulta que ya ocurrió
        if ($cita->status !== 'atendida') {
            return back()->with('error', 'Solo se puede registrar la evolución de una cita atendida.');
        }

        $datos = $request->validateWithBag('nota_' . $cita->id, NotaEvolucion::reglas(), NotaEvolucion::mensajes());

        // Vaciar todos los campos borra la nota
        if (!NotaEvolucion::tieneContenido($datos)) {
            $cita->notaEvolucion()->delete();

            return back()->with('success', 'Nota de evolución eliminada.');
        }

        NotaEvolucion::guardarDeCita($cita, $datos, auth()->id());

        return back()->with('success', 'Nota de evolución guardada.');
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

        return back()->with('success', 'Cita cancelada correctamente.');
    }
}