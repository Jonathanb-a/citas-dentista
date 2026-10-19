<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Anuncio;
use App\Services\ImagenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnuncioController extends Controller
{
    const CARPETA_IMAGENES = 'anuncios';

    protected $imagenes;

    public function __construct(ImagenService $imagenes)
    {
        $this->imagenes = $imagenes;
    }

    public function index()
    {
        $anuncios = Anuncio::ordenados()->get();
        $hoy = Anuncio::hoy();

        return view('admin.anuncios.index', compact('anuncios', 'hoy'));
    }

    public function create()
    {
        return view('admin.anuncios.create');
    }

    public function store(Request $request)
    {
        $datos = $this->preparar($request->validate(Anuncio::reglas(), Anuncio::mensajes()), $request);
        $datos['orden'] = ((int) Anuncio::max('orden')) + 1; // al final del carrusel

        $archivo = $request->file('imagen');
        $ruta = $archivo ? $this->imagenes->guardar($archivo, self::CARPETA_IMAGENES) : null;

        try {
            Anuncio::create($datos + ['imagen' => $ruta]);
        } catch (\Throwable $e) {
            // No dejar la imagen huérfana si no se pudo guardar el anuncio
            $this->imagenes->eliminar($ruta);
            throw $e;
        }

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
        $datos = $this->preparar($request->validate(Anuncio::reglas(), Anuncio::mensajes()), $request);

        $rutaNueva = null;
        $rutaFinal = $anuncio->imagen;
        $rutaABorrar = null;

        if ($archivo = $request->file('imagen')) {
            $rutaNueva = $this->imagenes->guardar($archivo, self::CARPETA_IMAGENES);
            $rutaABorrar = $anuncio->imagen;
            $rutaFinal = $rutaNueva;
        } elseif ($request->boolean('quitar_imagen')) {
            $rutaABorrar = $anuncio->imagen;
            $rutaFinal = null;
        }

        try {
            $anuncio->update($datos + ['imagen' => $rutaFinal]);
        } catch (\Throwable $e) {
            $this->imagenes->eliminar($rutaNueva);
            throw $e;
        }

        // La imagen anterior se borra hasta que el anuncio ya quedó guardado
        $this->imagenes->eliminar($rutaABorrar);

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
        $anuncio = Anuncio::findOrFail($id);
        $ruta = $anuncio->imagen;

        $anuncio->delete();
        $this->imagenes->eliminar($ruta);

        return redirect()->route('admin.anuncios.index')->with('success', 'Anuncio eliminado correctamente.');
    }

    public function subir($id)
    {
        $this->mover($id, -1);

        return redirect()->route('admin.anuncios.index');
    }

    public function bajar($id)
    {
        $this->mover($id, 1);

        return redirect()->route('admin.anuncios.index');
    }

    // Intercambia posición con el vecino y renumera todo 1..n para que nunca haya empates
    private function mover($id, int $direccion): void
    {
        DB::transaction(function () use ($id, $direccion) {
            $lista = Anuncio::ordenados()->lockForUpdate()->get()->all();

            $actual = null;
            foreach ($lista as $posicion => $anuncio) {
                if ($anuncio->id === (int) $id) {
                    $actual = $posicion;
                    break;
                }
            }

            abort_if($actual === null, 404);

            $destino = $actual + $direccion;
            if (isset($lista[$destino])) {
                [$lista[$actual], $lista[$destino]] = [$lista[$destino], $lista[$actual]];
            }

            foreach ($lista as $posicion => $anuncio) {
                if ($anuncio->orden !== $posicion + 1) {
                    $anuncio->update(['orden' => $posicion + 1]);
                }
            }
        });
    }

    // El checkbox "activo" no llega apagado y la imagen se maneja aparte: no van a create/update
    private function preparar(array $datos, Request $request): array
    {
        unset($datos['imagen'], $datos['quitar_imagen']);
        $datos['activo'] = $request->boolean('activo');

        return $datos;
    }
}
