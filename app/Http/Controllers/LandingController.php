<?php

namespace App\Http\Controllers;

use App\Models\Anuncio;
use Illuminate\Support\Facades\Log;

class LandingController extends Controller
{
    const MAX_ANUNCIOS = 8;

    public function index()
    {
        $anuncios = collect();

        // La landing es la cara de la clínica: si los anuncios fallan (tabla sin migrar,
        // base caída), se ve como siempre, sin anuncios, y no un error 500
        try {
            $anuncios = Anuncio::vigentes()->ordenados()->limit(self::MAX_ANUNCIOS)->get();
        } catch (\Throwable $e) {
            Log::warning('Landing: no se pudieron cargar los anuncios: ' . $e->getMessage());
        }

        return view('welcome', compact('anuncios'));
    }
}
