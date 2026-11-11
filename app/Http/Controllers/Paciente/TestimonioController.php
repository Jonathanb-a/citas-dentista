<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;
use App\Http\Requests\TestimonioRequest;
use App\Services\TelegramService;
use App\Services\TestimonioService;
use Illuminate\Support\Facades\Gate;

/** "Mi opinión": el paciente crea, edita y borra SU testimonio (uno por paciente) */
class TestimonioController extends Controller
{
    public function __construct(private TestimonioService $servicio) {}

    public function index()
    {
        $paciente = auth()->user();

        return view('paciente.testimonio.index', [
            'testimonio' => $paciente->testimonio,
            'puedeOpinar' => $this->servicio->puedeOpinar($paciente),
            'motivo' => $this->servicio->motivoSinAcceso($paciente),
        ]);
    }

    public function store(TestimonioRequest $request)
    {
        Gate::authorize('create', \App\Models\Testimonio::class);

        $paciente = $request->user();
        $testimonio = $this->servicio->crear($paciente, (int) $request->input('calificacion'), (string) $request->input('texto'));

        if (!$testimonio) {
            return redirect()->route('paciente.testimonio.index')->with('error', 'Ya enviaste tu opinión. Puedes editarla desde aquí.');
        }

        app(TelegramService::class)->enSegundoPlano(fn ($telegram) => $telegram->testimonioRecibido($testimonio, editado: false));

        return redirect()->route('paciente.testimonio.index')
            ->with('success', '¡Gracias! Tu opinión quedó en revisión y se publicará cuando la doctora la apruebe.');
    }

    public function update(TestimonioRequest $request)
    {
        $testimonio = $request->user()->testimonio;
        abort_unless($testimonio, 404);
        Gate::authorize('update', $testimonio);

        $testimonio = $this->servicio->editar($testimonio, (int) $request->input('calificacion'), (string) $request->input('texto'));

        app(TelegramService::class)->enSegundoPlano(fn ($telegram) => $telegram->testimonioRecibido($testimonio, editado: true));

        return redirect()->route('paciente.testimonio.index')
            ->with('success', 'Guardamos los cambios. Tu opinión vuelve a revisión antes de publicarse.');
    }

    public function destroy()
    {
        $testimonio = auth()->user()->testimonio;
        abort_unless($testimonio, 404);
        Gate::authorize('delete', $testimonio);

        $testimonio->delete();

        return redirect()->route('paciente.testimonio.index')->with('success', 'Tu opinión fue eliminada.');
    }
}
