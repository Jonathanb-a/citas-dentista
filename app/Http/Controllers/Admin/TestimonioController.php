<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonio;
use App\Services\TestimonioService;
use Illuminate\Http\Request;

/** Moderación de los testimonios de pacientes (solo la doctora: rutas con role:admin) */
class TestimonioController extends Controller
{
    public function __construct(private TestimonioService $servicio) {}

    public function index(Request $request)
    {
        $filtro = in_array($request->query('estado'), Testimonio::ESTADOS, true) ? $request->query('estado') : Testimonio::PENDIENTE;

        $conteos = Testimonio::selectRaw('estado, count(*) as total')->groupBy('estado')->pluck('total', 'estado');

        $testimonios = Testimonio::with('paciente:id,name,apellidos')
            ->where('estado', $filtro)
            ->orderByDesc('destacado')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.testimonios.index', [
            'testimonios' => $testimonios,
            'filtro' => $filtro,
            'conteos' => $conteos,
            'destacados' => Testimonio::where('destacado', true)->count(),
            'maxDestacados' => (int) config('testimonios.max_destacados'),
        ]);
    }

    public function aprobar(int $id)
    {
        $ok = $this->servicio->aprobar(Testimonio::findOrFail($id));

        return back()->with($ok ? 'success' : 'error', $ok ? 'Testimonio aprobado: ya se muestra en la página.' : 'Solo se puede aprobar un testimonio pendiente.');
    }

    public function ocultar(int $id)
    {
        $ok = $this->servicio->ocultar(Testimonio::findOrFail($id));

        return back()->with($ok ? 'success' : 'error', $ok ? 'Testimonio oculto: ya no se muestra en la página.' : 'Ese testimonio ya estaba oculto.');
    }

    public function restaurar(int $id)
    {
        $ok = $this->servicio->restaurar(Testimonio::findOrFail($id));

        return back()->with($ok ? 'success' : 'error', $ok ? 'Testimonio restaurado: quedó pendiente de revisión.' : 'Solo se puede restaurar un testimonio oculto.');
    }

    public function destacar(Request $request, int $id)
    {
        $quitar = $request->boolean('quitar');
        $motivo = $this->servicio->destacar(Testimonio::findOrFail($id), !$quitar);

        if ($motivo === 'maximo') {
            return back()->with('error', 'Solo puedes destacar hasta ' . (int) config('testimonios.max_destacados') . ' testimonios. Quita uno para destacar otro.');
        }

        if ($motivo === 'no_aprobado') {
            return back()->with('error', 'Solo se puede destacar un testimonio aprobado.');
        }

        return back()->with('success', $quitar ? 'Ya no está destacado.' : 'Testimonio destacado: aparece primero en la página.');
    }

    /** Definitivo: no queda registro del testimonio */
    public function destroy(int $id)
    {
        Testimonio::findOrFail($id)->delete();

        return back()->with('success', 'Testimonio eliminado.');
    }
}
