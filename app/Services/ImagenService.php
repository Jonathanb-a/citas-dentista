<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Subida, validación, guardado y borrado de imágenes, para cualquier módulo.
 *
 * El disco sale de config('imagenes.disk') (por .env), así que cambiar de disco
 * local a un Volume de Railway o a S3 no toca el código de quien la usa.
 *
 * En la base de datos se guarda solo la ruta relativa que devuelve guardar()
 * (por ejemplo "anuncios/01k….webp"); la URL pública sale de url().
 *
 * Uso típico en un controller:
 *     $ruta = $imagenes->guardar($request->file('imagen'), 'anuncios');
 *     ... guardar $ruta en la base ...
 *     $imagenes->eliminar($rutaAnterior);
 * y en las reglas de validación: 'imagen' => ImagenService::reglas()
 */
class ImagenService
{
    // Tipos reales permitidos (se detectan por el contenido, no por el nombre del archivo)
    const FORMATOS = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];

    // Ruta que genera guardar(); eliminar() y url() no aceptan nada que no tenga esta forma
    const PATRON_RUTA = '#^[a-z0-9_-]+(/[a-z0-9_-]+)*/[A-Za-z0-9]+\.(jpg|png|webp)$#';

    // ---------- Validación ----------

    /**
     * Reglas para un campo de formulario con imagen.
     * Revisan el contenido real: tipo MIME detectado, que getimagesize la lea
     * como imagen completa, peso y tamaño en píxeles.
     */
    public static function reglas(?int $maxKb = null, bool $requerida = false): array
    {
        $maxKb = $maxKb ?? (int) config('imagenes.max_kb');
        $maxPx = (int) config('imagenes.max_px');

        return [
            $requerida ? 'required' : 'nullable',
            'file',
            'mimes:jpg,jpeg,png,webp',
            'mimetypes:image/jpeg,image/png,image/webp',
            'max:' . $maxKb,
            function ($atributo, $valor, $fallar) use ($maxPx) {
                $info = $valor instanceof UploadedFile ? @getimagesize($valor->getRealPath()) : false;

                if ($info === false || !isset(self::FORMATOS[$info[2]])) {
                    $fallar('El archivo no es una imagen válida (JPG, PNG o WebP).');
                } elseif ($info[0] > $maxPx || $info[1] > $maxPx) {
                    $fallar("La imagen es demasiado grande: máximo {$maxPx} px por lado.");
                }
            },
        ];
    }

    public static function mensajes(string $campo = 'imagen'): array
    {
        $maxMb = round(config('imagenes.max_kb') / 1024, 1);

        return [
            "$campo.file" => 'Sube un archivo de imagen.',
            "$campo.mimes" => 'La imagen debe ser JPG, PNG o WebP.',
            "$campo.mimetypes" => 'La imagen debe ser JPG, PNG o WebP.',
            "$campo.max" => "La imagen no puede pesar más de {$maxMb} MB.",
            "$campo.uploaded" => "No se pudo subir la imagen. Revisa que pese menos de {$maxMb} MB.",
        ];
    }

    // ---------- Guardado y borrado ----------

    /**
     * Valida y guarda la imagen con un nombre aleatorio y la extensión real.
     * Devuelve la ruta relativa para guardar en la base de datos.
     *
     * @throws \Illuminate\Validation\ValidationException si no es una imagen válida
     */
    public function guardar(UploadedFile $archivo, string $carpeta, ?int $maxKb = null): string
    {
        if (!preg_match('#^[a-z0-9_-]+(/[a-z0-9_-]+)*$#', $carpeta)) {
            throw new \InvalidArgumentException('Carpeta de imágenes inválida.');
        }

        // La validación del formulario puede haberse omitido: se revisa de nuevo aquí
        Validator::make(['imagen' => $archivo], ['imagen' => self::reglas($maxKb, true)], self::mensajes())->validate();

        $extension = self::FORMATOS[getimagesize($archivo->getRealPath())[2]];
        $nombre = strtolower((string) Str::ulid()) . '.' . $extension;
        // El ULID en minúsculas cumple PATRON_RUTA ([A-Za-z0-9])

        $ruta = $this->disco()->putFileAs($carpeta, $archivo, $nombre, 'public');

        if ($ruta === false || $ruta === null) {
            throw new \RuntimeException('No se pudo guardar la imagen en el disco configurado.');
        }

        return $ruta;
    }

    /**
     * Guarda la nueva y, solo si eso salió bien, borra la anterior.
     * Si necesitas guardar la ruta en la base entre ambos pasos, usa guardar() y eliminar().
     */
    public function reemplazar(?string $anterior, UploadedFile $nueva, string $carpeta, ?int $maxKb = null): string
    {
        $ruta = $this->guardar($nueva, $carpeta, $maxKb);
        $this->eliminar($anterior);

        return $ruta;
    }

    /** Borra la imagen. Nunca lanza excepciones: un archivo que ya no está no es un error. */
    public function eliminar(?string $ruta): bool
    {
        if ($ruta === null || $ruta === '') {
            return false;
        }

        if (!$this->rutaValida($ruta)) {
            Log::warning('Imágenes: se ignoró el borrado de una ruta inválida: ' . $ruta);

            return false;
        }

        try {
            return (bool) $this->disco()->delete($ruta);
        } catch (\Throwable $e) {
            Log::warning('Imágenes: no se pudo borrar ' . $ruta . ': ' . $e->getMessage());

            return false;
        }
    }

    /** URL pública de la imagen, o null si no hay (o si la ruta no es una de las nuestras). */
    public function url(?string $ruta): ?string
    {
        if ($ruta === null || $ruta === '' || !$this->rutaValida($ruta)) {
            return null;
        }

        return $this->disco()->url($ruta);
    }

    public function rutaValida(string $ruta): bool
    {
        return (bool) preg_match(self::PATRON_RUTA, $ruta);
    }

    private function disco(): Filesystem
    {
        return Storage::disk(config('imagenes.disk'));
    }
}
