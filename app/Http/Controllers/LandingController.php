<?php

namespace App\Http\Controllers;

use App\Models\Anuncio;
use App\Models\Especialidad;
use App\Services\LandingColeccionService;
use App\Services\LandingContenidoService;
use Illuminate\Support\Facades\Log;

class LandingController extends Controller
{
    const MAX_ANUNCIOS = 8;

    public function index(LandingContenidoService $landing, LandingColeccionService $colecciones)
    {
        $anuncios = collect();

        // La landing es la cara de la clínica: si los anuncios fallan (tabla sin migrar,
        // base caída), se ve como siempre, sin anuncios, y no un error 500
        try {
            $anuncios = Anuncio::vigentes()->ordenados()->limit(self::MAX_ANUNCIOS)->get();
        } catch (\Throwable $e) {
            Log::warning('Landing: no se pudieron cargar los anuncios: ' . $e->getMessage());
        }

        // Tratamientos: los servicios activos agrupados por especialidad activa (los mismos
        // que el paciente puede agendar). Si la consulta falla, la sección no se muestra
        $tratamientos = collect();

        try {
            $tratamientos = Especialidad::where('activo', true)
                ->whereHas('servicios', fn ($q) => $q->where('activo', true))
                ->with(['servicios' => fn ($q) => $q->where('activo', true)->orderBy('nombre')])
                ->orderBy('nombre')
                ->get();
        } catch (\Throwable $e) {
            Log::warning('Landing: no se pudieron cargar los tratamientos: ' . $e->getMessage());
        }

        // $landing entrega cada imagen y texto: el personalizado o, si no hay, el original.
        // $galeria son las fotos de la galería en su orden (los originales mientras no se toque)
        $galeria = $colecciones->items('galeria');

        // Opiniones de pacientes: solo las que tienen la autorización marcada. Sin ninguna
        // (o si la lectura falla) la sección no se muestra
        $testimonios = array_values(array_filter($colecciones->items('testimonios'), fn ($opinion) => $opinion['autorizado'] === true));

        return view('welcome', compact('anuncios', 'tratamientos', 'landing', 'galeria', 'testimonios'));
    }
}
