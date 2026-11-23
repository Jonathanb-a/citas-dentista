<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Servicio;
use App\Models\HistorialCita;
use App\Services\DisponibilidadService;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Support\Consultorio;

class CitaController extends Controller
{
    protected $disponibilidad;

    public function __construct(DisponibilidadService $disponibilidad)
    {
        $this->disponibilidad = $disponibilidad;
    }

    // Vista principal — listar citas del paciente
    public function index()
    {
        $citas = Cita::with(['servicio.especialidad'])
            ->where('paciente_id', auth()->id())
            ->orderBy('fecha', 'desc')
            ->orderBy('hora', 'desc')
            ->get();

        return view('paciente.citas.index', compact('citas'));
    }

    // Vista para agendar nueva cita
    public function create()
    {
        $especialidades = Especialidad::where('activo', true)
            ->whereHas('servicios', function ($q) {
                $q->where('activo', true);
            })
            ->with(['servicios' => function ($q) {
                $q->where('activo', true);
            }])
            ->get();

        return view('paciente.citas.create', compact('especialidades'));
    }

    // API — devuelve los espacios disponibles (llamado por AJAX)
    public function disponibilidad(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date|after_or_equal:' . Consultorio::hoy()->toDateString() . '|before_or_equal:' . Consultorio::hoy()->addMonths(3)->toDateString(),
            'servicio_id' => 'required|exists:servicios,id',
        ]);

        $espacios = $this->disponibilidad->espaciosDisponibles(
            $request->fecha,
            $request->servicio_id
        );

        return response()->json($espacios);
    }

    // Guardar la cita
    public function store(Request $request)
    {
        $request->validate([
            'servicio_id' => 'required|exists:servicios,id',
            'horario_id' => 'required|exists:horarios,id',
            'fecha' => 'required|date|after_or_equal:' . Consultorio::hoy()->toDateString() . '|before_or_equal:' . Consultorio::hoy()->addMonths(3)->toDateString(),
            'hora' => 'required|date_format:H:i',
            'motivo' => 'nullable|string|max:500',
        ], [
            'servicio_id.required' => 'Debes seleccionar un servicio.',
            'fecha.required' => 'Debes seleccionar una fecha.',
            'fecha.after_or_equal' => 'No puedes agendar en una fecha pasada.',
            'fecha.before_or_equal' => 'Solo puedes agendar hasta 3 meses por adelantado.',
            'hora.required' => 'Debes seleccionar un horario.',
        ]);

        // Límite de citas activas por paciente (evita saturar la agenda)
        $citasActivas = Cita::where('paciente_id', auth()->id())
            ->whereIn('status', ['pendiente', 'confirmada'])
            ->whereDate('fecha', '>=', Consultorio::hoy())
            ->count();

        if ($citasActivas >= 3) {
            return back()->with('error',
                'Ya tienes 3 citas activas. Para agendar otra, espera a que te atiendan o cancela alguna.'
            )->withInput();
        }

        // Todo en una transacción que primero bloquea el día: dos personas que agendan a la vez hacen fila
        // y la segunda ve la cita de la primera (el horario_id lo deriva el servidor del espacio libre)
        try {
            $cita = DB::transaction(function () use ($request) {
                $espacio = $this->disponibilidad->espacioConBloqueo($request->fecha, $request->servicio_id, $request->hora);

                if (!$espacio) {
                    return null;
                }

                $cita = Cita::create([
                    'paciente_id' => auth()->id(),
                    'servicio_id' => $request->servicio_id,
                    'horario_id' => $espacio['horario_id'],
                    'fecha' => $request->fecha,
                    'hora' => $request->hora,
                    'status' => 'pendiente',
                    'confirmado_por_paciente' => false,
                    'confirmacion_limite' => Carbon::now()->addHours(24),
                    'motivo' => $request->motivo,
                ]);

                HistorialCita::create([
                    'cita_id' => $cita->id,
                    'user_id' => auth()->id(),
                    'accion' => 'creada',
                    'descripcion' => 'El paciente agendó la cita.',
                ]);

                return $cita;
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Solo un choque de horario (race condition) se avisa como tal; otro error de la base sigue su curso
            if (!Cita::esChoqueDeHorario($e)) {
                throw $e;
            }

            return back()->withErrors([
                'hora' => 'Lo sentimos, alguien acaba de tomar ese horario. Por favor elige otro.',
            ])->withInput();
        }

        if (!$cita) {
            return back()->withErrors([
                'hora' => 'Lo sentimos, ese horario ya fue ocupado. Por favor elige otro.',
            ])->withInput();
        }

        // Aviso a la clínica, ya con la cita guardada y después de responder al paciente
        app(TelegramService::class)->enSegundoPlano(fn ($telegram) => $telegram->citaAgendada($cita));

        return redirect()->route('paciente.citas.index')
            ->with('success', 'Tu cita fue agendada correctamente. Recibirás una confirmación pronto.');
    }

    // Cancelar cita
    public function cancelar($id)
    {
        $cita = Cita::where('id', $id)
            ->where('paciente_id', auth()->id())
            ->firstOrFail();

        if (in_array($cita->status, ['atendida', 'cancelada'])) {
            return back()->with('error', 'Esta cita no se puede cancelar.');
        }

        $cita->update(['status' => 'cancelada']);

        HistorialCita::create([
            'cita_id' => $cita->id,
            'user_id' => auth()->id(),
            'accion' => 'cancelada',
            'descripcion' => 'El paciente canceló la cita.',
        ]);

        app(TelegramService::class)->enSegundoPlano(fn ($telegram) => $telegram->citaCancelada($cita));

        return redirect()->route('paciente.citas.index')
            ->with('success', 'Tu cita fue cancelada correctamente.');
    }

    // Confirmar asistencia (el paciente confirma)
    public function confirmar($id)
    {
        $cita = Cita::where('id', $id)
            ->where('paciente_id', auth()->id())
            ->firstOrFail();

        if ($cita->status !== 'confirmada') {
            return back()->with('error', 'Esta cita aún no ha sido aceptada por la clínica o ya no se puede confirmar.');
        }

        if ($cita->confirmado_por_paciente) {
            return back()->with('error', 'Ya confirmaste esta cita anteriormente.');
        }

        $cita->update(['confirmado_por_paciente' => true]);

        HistorialCita::create([
            'cita_id' => $cita->id,
            'user_id' => auth()->id(),
            'accion' => 'confirmada',
            'descripcion' => 'El paciente confirmó su asistencia.',
        ]);

        return back()->with('success', '¡Gracias! Tu asistencia quedó confirmada.');
    }
}