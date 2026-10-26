<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LandingColeccionService;
use Illuminate\Http\Request;

/**
 * Opiniones de pacientes de la landing (#testimonios): agregar, quitar, reordenar y
 * editar. Sin fotos. Copia del flujo de LandingGaleriaController; la landing solo las
 * muestra si tienen marcada la autorización del paciente.
 */
class LandingTestimoniosController extends Controller
{
    const COLECCION = 'testimonios';

    // Pestaña "Opiniones": la lista y el formulario para agregar
    public function index(LandingColeccionService $colecciones)
    {
        return view('admin.landing.testimonios', [
            'definicion' => LandingColeccionService::definicion(self::COLECCION),
            'items' => $colecciones->items(self::COLECCION),
        ]);
    }

    public function agregar(Request $request, LandingColeccionService $colecciones)
    {
        $datos = $request->validateWithBag(
            'nueva',
            LandingColeccionService::reglas(self::COLECCION, false),
            LandingColeccionService::mensajes(self::COLECCION)
        );

        return $this->ejecutar(
            fn () => $colecciones->agregar(self::COLECCION, null, $datos),
            'La opinión se agregó al final de la lista.'
        );
    }

    public function actualizar(Request $request, LandingColeccionService $colecciones, string $item)
    {
        $datos = $request->validateWithBag(
            'opinion_' . $item,
            LandingColeccionService::reglas(self::COLECCION, false),
            LandingColeccionService::mensajes(self::COLECCION)
        );

        return $this->ejecutar(
            fn () => $colecciones->actualizar(self::COLECCION, $item, $datos),
            'Los cambios de la opinión se guardaron.'
        );
    }

    public function eliminar(LandingColeccionService $colecciones, string $item)
    {
        return $this->ejecutar(fn () => $colecciones->eliminar(self::COLECCION, $item), 'La opinión se quitó.');
    }

    public function subir(LandingColeccionService $colecciones, string $item)
    {
        return $this->ejecutar(fn () => $colecciones->mover(self::COLECCION, $item, -1));
    }

    public function bajar(LandingColeccionService $colecciones, string $item)
    {
        return $this->ejecutar(fn () => $colecciones->mover(self::COLECCION, $item, 1));
    }

    // Una regla de negocio (máximo de opiniones) se muestra como aviso, no como error
    private function ejecutar(callable $accion, ?string $exito = null)
    {
        try {
            $accion();
        } catch (\DomainException $e) {
            return redirect()->route('admin.landing.testimonios')->with('error', $e->getMessage());
        }

        $redireccion = redirect()->route('admin.landing.testimonios');

        return $exito ? $redireccion->with('success', $exito) : $redireccion;
    }
}
