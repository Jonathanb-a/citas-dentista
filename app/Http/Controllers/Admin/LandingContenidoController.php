<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LandingContenidoService;
use Illuminate\Http\Request;

/**
 * Imágenes y textos editables de la landing page (ver config/landing.php).
 * Solo guarda lo que se cambia: restaurar vuelve al valor original.
 *
 * El servicio se recibe en cada acción (no en el constructor): guarda en memoria lo
 * personalizado durante la petición y no debe sobrevivir a ella.
 */
class LandingContenidoController extends Controller
{
    // Pestaña "Imágenes y textos"
    public function contenido(LandingContenidoService $landing)
    {
        return $this->pestana('contenido', $landing);
    }

    // Pestaña "Contacto"
    public function contacto(LandingContenidoService $landing)
    {
        return $this->pestana('contacto', $landing);
    }

    private function pestana(string $pestana, LandingContenidoService $landing)
    {
        return view('admin.landing.pagina', [
            'pestana' => $pestana,
            'secciones' => LandingContenidoService::seccionesDePestana($pestana),
            'landing' => $landing,
        ]);
    }

    public function guardar(Request $request, LandingContenidoService $landing, string $seccion)
    {
        $this->seccionOFallar($seccion);

        $campos = LandingContenidoService::camposDeSeccion($seccion);

        // Los textos se normalizan antes de validar (teléfono con guiones, iframe del mapa, etc.)
        $normalizados = [];
        foreach ($campos as $clave => $definicion) {
            $nombre = LandingContenidoService::nombreDeCampo($clave);

            if ($definicion['tipo'] !== 'imagen' && $request->has($nombre)) {
                $normalizados[$nombre] = LandingContenidoService::normalizar($clave, $request->input($nombre));
            }
        }
        $request->merge($normalizados);

        $request->validate(LandingContenidoService::reglas($seccion), LandingContenidoService::mensajes($seccion));

        $valores = [];
        $archivos = [];
        foreach ($campos as $clave => $definicion) {
            $nombre = LandingContenidoService::nombreDeCampo($clave);

            if ($definicion['tipo'] === 'imagen') {
                if ($request->hasFile($nombre)) {
                    $archivos[$clave] = $request->file($nombre);
                }
            } elseif ($request->has($nombre)) {
                $valores[$clave] = $request->input($nombre); // ya normalizado; null = volver al original
            }
        }

        $landing->guardar($valores, $archivos);

        return $this->volver($seccion, 'Los cambios de "' . LandingContenidoService::secciones()[$seccion]['titulo'] . '" se guardaron.');
    }

    public function restaurarCampo(LandingContenidoService $landing, string $clave)
    {
        abort_unless(isset(LandingContenidoService::campos()[$clave]), 404);

        $landing->restaurarCampo($clave);

        return $this->volver(explode('.', $clave)[0], 'Se restauró el valor original de "' . LandingContenidoService::definicion($clave)['etiqueta'] . '".');
    }

    public function restaurarSeccion(LandingContenidoService $landing, string $seccion)
    {
        $this->seccionOFallar($seccion);

        $landing->restaurarSeccion($seccion);

        return $this->volver($seccion, 'Se restauró la sección "' . LandingContenidoService::secciones()[$seccion]['titulo'] . '" a sus valores originales.');
    }

    private function seccionOFallar(string $seccion): void
    {
        abort_unless(isset(LandingContenidoService::secciones()[$seccion]), 404);
    }

    private function volver(string $seccion, string $mensaje)
    {
        $pestana = LandingContenidoService::secciones()[$seccion]['pestana'];

        return redirect(route('admin.landing.' . $pestana) . '#seccion-' . $seccion)->with('success', $mensaje);
    }
}
