<?php

namespace App\Http\Controllers;

use App\Models\Anuncio;
use Illuminate\Support\Facades\Log;

class LandingController extends Controller
{
    const MAX_AVISOS = 3;
    const MAX_PROMOCIONES = 6;

    public function index()
    {
        $avisos = collect();
        $promociones = collect();

        // La landing es la cara de la clínica: si los anuncios fallan (tabla sin migrar,
        // base caída), se ve como siempre, sin anuncios, y no un error 500
        try {
            $vigentes = Anuncio::vigentes()->orderByDesc('created_at')->orderByDesc('id')->get();

            $avisos = $vigentes->where('tipo', 'aviso')->take(self::MAX_AVISOS)->values();
            $promociones = $vigentes->where('tipo', 'promocion')->take(self::MAX_PROMOCIONES)->values();
        } catch (\Throwable $e) {
            Log::warning('Landing: no se pudieron cargar los anuncios: ' . $e->getMessage());
        }

        return view('welcome', compact('avisos', 'promociones'));
    }
}
