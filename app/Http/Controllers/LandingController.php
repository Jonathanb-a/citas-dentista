<?php

namespace App\Http\Controllers;

use App\Models\Anuncio;
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

        // $landing entrega cada imagen y texto: el personalizado o, si no hay, el original.
        // $galeria son las fotos de la galería en su orden (los originales mientras no se toque)
        $galeria = $colecciones->items('galeria');

        return view('welcome', compact('anuncios', 'landing', 'galeria'));
    }
}
