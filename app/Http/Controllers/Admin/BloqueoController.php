<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bloqueo;
use App\Services\BloqueoService;
use Illuminate\Http\Request;

class BloqueoController extends Controller
{
    protected $bloqueos;

    public function __construct(BloqueoService $bloqueos)
    {
        $this->bloqueos = $bloqueos;
    }

    public function index()
    {
        $vigentes = Bloqueo::vigentes()->orderBy('fecha_inicio')->orderBy('hora_inicio')->get();
        $pasados = Bloqueo::whereNotIn('id', $vigentes->pluck('id'))
            ->orderByDesc('fecha_inicio')
            ->limit(20)
            ->get();

        return view('admin.bloqueos.index', compact('vigentes', 'pasados'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:dia,rango,horas',
            'fecha_inicio' => 'required|date|after_or_equal:today',
            'fecha_fin' => 'required_if:tipo,rango|nullable|date|after_or_equal:fecha_inicio',
            'hora_inicio' => 'required_if:tipo,horas|nullable|date_format:H:i',
            'hora_fin' => 'required_if:tipo,horas|nullable|date_format:H:i|after:hora_inicio',
            'motivo' => 'nullable|string|max:255',
            'accion_citas' => 'nullable|in:conservar,cancelar',
            'citas_vistas' => 'nullable|array',
            'citas_vistas.*' => 'integer',
        ], [
            'fecha_inicio.required' => 'Elige la fecha del bloqueo.',
            'fecha_inicio.after_or_equal' => 'No puedes bloquear una fecha pasada.',
            'fecha_fin.required_if' => 'Elige hasta qué día dura el bloqueo.',
            'fecha_fin.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
            'hora_inicio.required_if' => 'Indica la hora en que inicia el bloqueo.',
            'hora_fin.required_if' => 'Indica la hora en que termina el bloqueo.',
            'hora_fin.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ]);

        $datos = [
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->tipo === 'rango' ? $request->fecha_fin : $request->fecha_inicio,
            'hora_inicio' => $request->tipo === 'horas' ? $request->hora_inicio : null,
            'hora_fin' => $request->tipo === 'horas' ? $request->hora_fin : null,
            'motivo' => $request->filled('motivo') ? trim($request->motivo) : null,
        ];

        $resultado = $this->bloqueos->guardar(
            $datos,
            $request->accion_citas,
            $request->input('citas_vistas', []),
            auth()->id()
        );

        // Hay citas en el periodo: no se guardó nada, se muestra el aviso
        if (isset($resultado['conflictos'])) {
            return back()->withInput()->with('conflictos', $resultado['conflictos']->map(fn ($cita) => [
                'id' => $cita->id,
                'paciente' => trim($cita->paciente->name . ' ' . $cita->paciente->apellidos),
                'fecha' => $cita->fecha->format('d/m/Y'),
                'hora' => \Carbon\Carbon::parse($cita->hora)->format('h:i A'),
                'servicio' => $cita->servicio->nombre,
                'status' => $cita->status,
            ])->all());
        }

        $mensaje = 'Bloqueo guardado correctamente.';
        if ($resultado['canceladas'] > 0) {
            $mensaje .= " Se cancelaron {$resultado['canceladas']} cita(s); avisa a los pacientes.";
        } elseif ($resultado['conservadas'] > 0) {
            $mensaje .= " Quedaron {$resultado['conservadas']} cita(s) marcadas para reagendar.";
        }

        return redirect()->route('admin.bloqueos.index')->with('success', $mensaje);
    }

    public function destroy($id)
    {
        Bloqueo::findOrFail($id)->delete();

        return redirect()->route('admin.bloqueos.index')
            ->with('success', 'Bloqueo eliminado. Esos horarios vuelven a estar disponibles.');
    }
}
