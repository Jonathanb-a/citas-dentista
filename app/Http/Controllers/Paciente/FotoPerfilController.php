<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;
use App\Services\ImagenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Foto de perfil del paciente: subir, cambiar y quitar. Sin id en la ruta: cada paciente solo toca la
 * suya (auth()->user()). Pasa por ImagenService (validación real, nombre aleatorio, sin metadatos,
 * recorte cuadrado y 512 px con GD) y borra la anterior después de guardar la nueva.
 *
 * Si el disco de imágenes no está disponible, se avisa con un mensaje amable y el perfil sigue con las
 * iniciales. Nada de lo que sube el paciente ni ninguna ruta llega al log: solo la clase del error.
 */
class FotoPerfilController extends Controller
{
    public function guardar(Request $request, ImagenService $imagenes)
    {
        $paciente = $request->user();

        // Los pacientes del chat no tienen cuenta usable
        abort_if($paciente->esDeChat(), 403);

        $request->validateWithBag('foto', ['foto' => ImagenService::reglas(null, true)], ImagenService::mensajes('foto'));

        try {
            $nueva = $imagenes->guardar($request->file('foto'), 'perfiles', null, ['cuadrada' => (int) config('imagenes.avatar_px')]);
        } catch (ValidationException $e) {
            return back()->withErrors(['foto' => 'El archivo no es una imagen válida (JPG, PNG o WebP).'], 'foto');
        } catch (\Throwable $e) {
            Log::warning('Foto de perfil: no se pudo guardar (' . class_basename($e) . ').');

            return back()->with('error', 'No pudimos guardar tu foto en este momento. Inténtalo más tarde; tu perfil sigue funcionando.');
        }

        $anterior = $paciente->foto;

        try {
            $paciente->foto = $nueva;
            $paciente->save();
        } catch (\Throwable $e) {
            $imagenes->eliminar($nueva);   // no dejar un archivo huérfano
            Log::warning('Foto de perfil: no se pudo registrar (' . class_basename($e) . ').');

            return back()->with('error', 'No pudimos guardar tu foto en este momento. Inténtalo más tarde.');
        }

        // Ya quedó registrada la nueva: se borra la anterior
        $imagenes->eliminar($anterior);

        return redirect()->route('paciente.perfil.index')->with('success', 'Tu foto de perfil se actualizó.');
    }

    public function quitar(Request $request, ImagenService $imagenes)
    {
        $paciente = $request->user();
        abort_if($paciente->esDeChat(), 403);

        $anterior = $paciente->foto;

        if ($anterior !== null) {
            $paciente->foto = null;
            $paciente->save();
            $imagenes->eliminar($anterior);
        }

        return redirect()->route('paciente.perfil.index')->with('success', 'Quitamos tu foto de perfil.');
    }
}
