<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Anuncio;
use Illuminate\Http\Request;

class AnuncioController extends Controller
{
    public function index()
    {
        $anuncios = Anuncio::orderByDesc('created_at')->orderByDesc('id')->get();
        $hoy = Anuncio::hoy();

        return view('admin.anuncios.index', compact('anuncios', 'hoy'));
    }

    public function create()
    {
        return view('admin.anuncios.create');
    }

    public function store(Request $request)
    {
        $datos = $request->validate(Anuncio::reglas(), Anuncio::mensajes());

        Anuncio::create($this->preparar($datos, $request));

        return redirect()->route('admin.anuncios.index')->with('success', 'Anuncio guardado correctamente.');
    }

    public function edit($id)
    {
        $anuncio = Anuncio::findOrFail($id);

        return view('admin.anuncios.edit', compact('anuncio'));
    }

    public function update(Request $request, $id)
    {
        $anuncio = Anuncio::findOrFail($id);
        $datos = $request->validate(Anuncio::reglas(), Anuncio::mensajes());

        $anuncio->update($this->preparar($datos, $request));

        return redirect()->route('admin.anuncios.index')->with('success', 'Anuncio actualizado correctamente.');
    }

    public function toggle($id)
    {
        $anuncio = Anuncio::findOrFail($id);
        $anuncio->update(['activo' => !$anuncio->activo]);

        return redirect()->route('admin.anuncios.index')
            ->with('success', $anuncio->activo ? 'Anuncio activado.' : 'Anuncio desactivado.');
    }

    public function destroy($id)
    {
        Anuncio::findOrFail($id)->delete();

        return redirect()->route('admin.anuncios.index')->with('success', 'Anuncio eliminado correctamente.');
    }

    // El checkbox "activo" no llega cuando está apagado: se lee como booleano
    private function preparar(array $datos, Request $request): array
    {
        $datos['activo'] = $request->boolean('activo');

        return $datos;
    }
}
