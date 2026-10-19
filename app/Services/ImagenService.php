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

        $tipo = getimagesize($archivo->getRealPath())[2];
        $extension = self::FORMATOS[$tipo];
        $nombre = strtolower((string) Str::ulid()) . '.' . $extension;
        // El ULID en minúsculas cumple PATRON_RUTA ([A-Za-z0-9])

        // Con GD se guarda la versión reducida y sin metadatos; si no hay GD, o algo falla,
        // se guarda el archivo original: el redimensionado nunca impide una subida
        $optimizada = $this->optimizar($archivo, $tipo);

        if ($optimizada !== null) {
            $ruta = $this->disco()->put("$carpeta/$nombre", $optimizada, 'public') ? "$carpeta/$nombre" : false;
        } else {
            $ruta = $this->disco()->putFileAs($carpeta, $archivo, $nombre, 'public');
        }

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

    // ---------- Redimensionado (opcional, requiere la extensión GD) ----------

    // Aparte para poder simular un servidor sin GD en las pruebas
    protected function gdDisponible(): bool
    {
        return extension_loaded('gd');
    }

    /**
     * Devuelve los bytes de la imagen reducida a config('imagenes.ancho_max') de ancho
     * (nunca ampliada), con la rotación EXIF aplicada y sin metadatos, en el mismo formato.
     * Devuelve null cuando hay que guardar el original: sin GD, desactivado, WebP animado,
     * poca memoria o cualquier fallo (este último se registra en el log). Nunca lanza excepciones.
     */
    private function optimizar(UploadedFile $archivo, int $tipo): ?string
    {
        if (!config('imagenes.redimensionar') || !$this->gdDisponible() || !$this->gdSoporta($tipo)) {
            return null;
        }

        try {
            $ruta = $archivo->getRealPath();

            // GD solo lee el primer cuadro: reducirlo destruiría la animación
            if ($tipo === IMAGETYPE_WEBP && $this->webpAnimado($ruta)) {
                return null;
            }

            [$ancho, $alto] = getimagesize($ruta);

            // Quedarse sin memoria es un error fatal que ningún try/catch atrapa: se calcula antes
            if (!$this->hayMemoriaPara($ancho, $alto)) {
                Log::info("Imágenes: no hay memoria para redimensionar una imagen de {$ancho}x{$alto}; se guardó el original.");

                return null;
            }

            $imagen = @imagecreatefromstring((string) file_get_contents($ruta));
            if ($imagen === false) {
                throw new \RuntimeException('GD no pudo leer la imagen.');
            }

            if ($tipo === IMAGETYPE_JPEG) {
                $imagen = $this->corregirRotacion($imagen, $ruta);
            }

            $imagen = $this->limitarAncho($imagen, $tipo);
            $bytes = $this->codificar($imagen, $tipo);

            if ($bytes === '') {
                throw new \RuntimeException('GD devolvió una imagen vacía.');
            }

            return $bytes;
        } catch (\Throwable $e) {
            Log::warning('Imágenes: no se pudo redimensionar, se guardó el original: ' . $e->getMessage());

            return null;
        }
    }

    private function gdSoporta(int $tipo): bool
    {
        return match ($tipo) {
            IMAGETYPE_JPEG => function_exists('imagecreatefromjpeg') && function_exists('imagejpeg'),
            IMAGETYPE_PNG => function_exists('imagecreatefrompng') && function_exists('imagepng'),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') && function_exists('imagewebp'),
            default => false,
        };
    }

    // Un WebP animado siempre usa el contenedor VP8X con el bit de animación (0x02) encendido
    private function webpAnimado(string $ruta): bool
    {
        $inicio = (string) file_get_contents($ruta, false, null, 0, 32);

        return strlen($inicio) >= 21
            && substr($inicio, 0, 4) === 'RIFF'
            && substr($inicio, 8, 4) === 'WEBP'
            && substr($inicio, 12, 4) === 'VP8X'
            && (ord($inicio[20]) & 0x02) !== 0;
    }

    // GD descomprime a 4 bytes por píxel; se necesita la original y una copia reducida
    private function hayMemoriaPara(int $ancho, int $alto): bool
    {
        $limite = ini_get('memory_limit');

        if ($limite === false || $limite === '-1' || $limite === '') {
            return true;
        }

        $bytes = (int) $limite;
        $unidad = strtolower(substr(trim($limite), -1));
        $bytes *= match ($unidad) {
            'g' => 1024 ** 3,
            'm' => 1024 ** 2,
            'k' => 1024,
            default => 1,
        };

        return ($ancho * $alto * 4 * 2.5) < ($bytes - memory_get_usage());
    }

    // Aplica la orientación EXIF de las fotos de celular (1 = normal; 2 a 8 = girada o espejada)
    private function corregirRotacion(\GdImage $imagen, string $ruta): \GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $imagen;
        }

        $exif = @exif_read_data($ruta, 'IFD0');
        $orientacion = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        // Ángulos en sentido antihorario, como imagerotate()
        $giro = [3 => 180, 4 => 180, 5 => 270, 6 => 270, 7 => 90, 8 => 90][$orientacion] ?? 0;
        $espejo = in_array($orientacion, [2, 4, 5, 7], true);

        if ($giro !== 0) {
            $girada = imagerotate($imagen, $giro, 0);
            if ($girada === false) {
                throw new \RuntimeException('GD no pudo girar la imagen.');
            }
            $imagen = $girada;
        }

        if ($espejo) {
            imageflip($imagen, IMG_FLIP_HORIZONTAL);
        }

        return $imagen;
    }

    // Reduce al ancho máximo conservando la proporción y la transparencia; nunca amplía
    private function limitarAncho(\GdImage $imagen, int $tipo): \GdImage
    {
        $conAlfa = $tipo !== IMAGETYPE_JPEG;
        $maximo = (int) config('imagenes.ancho_max');
        $ancho = imagesx($imagen);
        $alto = imagesy($imagen);

        if ($conAlfa && !imageistruecolor($imagen)) {
            imagepalettetotruecolor($imagen); // PNG indexado
        }

        if ($ancho <= $maximo) {
            return $imagen;
        }

        $nuevoAlto = max(1, (int) round($alto * $maximo / $ancho));
        $reducida = imagecreatetruecolor($maximo, $nuevoAlto);

        if ($conAlfa) {
            imagealphablending($reducida, false);
            imagesavealpha($reducida, true);
            imagefill($reducida, 0, 0, imagecolorallocatealpha($reducida, 0, 0, 0, 127));
        }

        if (!imagecopyresampled($reducida, $imagen, 0, 0, 0, 0, $maximo, $nuevoAlto, $ancho, $alto)) {
            throw new \RuntimeException('GD no pudo reducir la imagen.');
        }

        return $reducida;
    }

    // Volver a codificar descarta EXIF, GPS y cualquier otro metadato
    private function codificar(\GdImage $imagen, int $tipo): string
    {
        $calidad = (int) config('imagenes.calidad');

        if ($tipo !== IMAGETYPE_JPEG) {
            imagealphablending($imagen, false);
            imagesavealpha($imagen, true);
        }

        ob_start();
        try {
            $ok = match ($tipo) {
                IMAGETYPE_JPEG => imagejpeg($imagen, null, $calidad),
                IMAGETYPE_PNG => imagepng($imagen),
                IMAGETYPE_WEBP => imagewebp($imagen, null, $calidad),
            };
            $bytes = (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }

        if (!$ok) {
            throw new \RuntimeException('GD no pudo codificar la imagen.');
        }

        return $bytes;
    }
}
