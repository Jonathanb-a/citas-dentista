<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LandingColeccionService;
use App\Services\LandingContenidoService;
use Illuminate\Http\Request;

/**
 * Fotos de la galería de la landing (#galeria): agregar, quitar, reordenar y editar.
 * Mientras no se toque nada la landing muestra las 5 fotos originales.
 */
class LandingGaleriaController extends Controller
{
    const COLECCION = 'galeria';

    // Pestaña "Galería": el título de la sección y la lista de fotos
    public function index(LandingColeccionService $colecciones, LandingContenidoService $landing)
    {
        return view('admin.landing.galeria', [
            'definicion' => LandingColeccionService::definicion(self::COLECCION),
            'items' => $colecciones->items(self::COLECCION),
            'personalizada' => $colecciones->personalizada(self::COLECCION),
            'secciones' => LandingContenidoService::seccionesDePestana('galeria'),
            'landing' => $landing,
        ]);
    }

    public function agregar(Request $request, LandingColeccionService $colecciones)
    {
        $datos = $request->validateWithBag(
            'nueva',
            LandingColeccionService::reglas(self::COLECCION, true),
            LandingColeccionService::mensajes(self::COLECCION)
        );

        return $this->ejecutar(function () use ($colecciones, $request, $datos) {
            $colecciones->agregar(self::COLECCION, $request->file('imagen'), $datos);
        }, 'La foto se agregó al final de la galería.');
    }

    public function actualizar(Request $request, LandingColeccionService $colecciones, string $item)
    {
        $datos = $request->validateWithBag(
            'foto_' . $item,
            LandingColeccionService::reglas(self::COLECCION, false),
            LandingColeccionService::mensajes(self::COLECCION)
        );

        return $this->ejecutar(function () use ($colecciones, $request, $item, $datos) {
            $colecciones->actualizar(self::COLECCION, $item, $datos, $request->file('imagen'));
        }, 'Los cambios de la foto se guardaron.');
    }

    public function eliminar(LandingColeccionService $colecciones, string $item)
    {
        return $this->ejecutar(fn () => $colecciones->eliminar(self::COLECCION, $item), 'La foto se quitó de la galería.');
    }

    public function subir(LandingColeccionService $colecciones, string $item)
    {
        return $this->ejecutar(fn () => $colecciones->mover(self::COLECCION, $item, -1));
    }

    public function bajar(LandingColeccionService $colecciones, string $item)
    {
        return $this->ejecutar(fn () => $colecciones->mover(self::COLECCION, $item, 1));
    }

    public function restaurarImagen(LandingColeccionService $colecciones, string $item)
    {
        return $this->ejecutar(fn () => $colecciones->restaurarImagen(self::COLECCION, $item), 'Se restauró la imagen original de esa foto.');
    }

    public function restaurar(LandingColeccionService $colecciones)
    {
        return $this->ejecutar(fn () => $colecciones->restaurar(self::COLECCION), 'La galería volvió a sus fotos originales.');
    }

    // Una regla de negocio (mínimo o máximo de fotos) se muestra como aviso, no como error
    private function ejecutar(callable $accion, ?string $exito = null)
    {
        try {
            $accion();
        } catch (\DomainException $e) {
            return redirect()->route('admin.landing.galeria')->with('error', $e->getMessage());
        }

        $redireccion = redirect()->route('admin.landing.galeria');

        return $exito ? $redireccion->with('success', $exito) : $redireccion;
    }
}
