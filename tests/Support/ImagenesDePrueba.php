<?php

namespace Tests\Support;

use Illuminate\Http\UploadedFile;

/**
 * Archivos de imagen con bytes reales para las pruebas. Los métodos png(), jpg() y
 * webp() no usan GD; los de la sección "con GD" solo sirven si la extensión está cargada
 * (UploadedFile::fake()->image() también la necesita).
 */
class ImagenesDePrueba
{
    public static function png(int $ancho = 1, int $alto = 1, int $relleno = 0): string
    {
        $chunk = fn (string $tipo, string $datos) => pack('N', strlen($datos)) . $tipo . $datos . pack('N', crc32($tipo . $datos));

        return "\x89PNG\r\n\x1a\n"
            . $chunk('IHDR', pack('NNCCCCC', $ancho, $alto, 8, 2, 0, 0, 0))
            . $chunk('IDAT', gzcompress(str_repeat("\0", 1 + 3 * min($ancho, 4))))
            . $chunk('IEND', '')
            . str_repeat("\0", $relleno); // bytes extra al final, para probar el límite de peso
    }

    public static function jpg(): string
    {
        return base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAMCAgICAgMCAgIDAwMDBAYEBAQEBAgGBgUGCQgKCgkICQkKDA8MCgsOCwkJDRENDg8QEBEQCgwSExIQEw8QEBD/yQALCAABAAEBAREA/8wABgAQEAX/2gAIAQEAAD8A0s8g/9k=');
    }

    public static function webp(): string
    {
        return base64_decode('UklGRhoAAABXRUJQVlA4TA0AAAAvAAAAEAcQERGIiP4HAA==');
    }

    public static function archivo(string $nombre, string $contenido): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nombre, $contenido);
    }

    public static function pngSubido(string $nombre = 'foto.png', int $ancho = 1, int $alto = 1, int $relleno = 0): UploadedFile
    {
        return self::archivo($nombre, self::png($ancho, $alto, $relleno));
    }

    /** Cabecera de un WebP animado (contenedor VP8X con el bit de animación); no hace falta que se pueda decodificar */
    public static function webpAnimado(int $ancho = 2000, int $alto = 1000): string
    {
        $dimensiones = substr(pack('V', $ancho - 1), 0, 3) . substr(pack('V', $alto - 1), 0, 3);
        $vp8x = 'VP8X' . pack('V', 10) . "\x02\x00\x00\x00" . $dimensiones;
        $anim = 'ANIM' . pack('V', 6) . "\x00\x00\x00\x00\x00\x00";

        return 'RIFF' . pack('V', 4 + strlen($vp8x) + strlen($anim)) . 'WEBP' . $vp8x . $anim;
    }

    // ---------- Con GD ----------

    /** @param callable(\GdImage):void|null $dibujar */
    public static function conGd(int $ancho, int $alto, string $formato = 'png', ?callable $dibujar = null, bool $transparente = false): string
    {
        $imagen = imagecreatetruecolor($ancho, $alto);

        if ($transparente) {
            imagealphablending($imagen, false);
            imagesavealpha($imagen, true);
            imagefill($imagen, 0, 0, imagecolorallocatealpha($imagen, 0, 0, 0, 127));
        } else {
            imagefill($imagen, 0, 0, imagecolorallocate($imagen, 255, 255, 255));
        }

        if ($dibujar) {
            $dibujar($imagen);
        }

        ob_start();
        match ($formato) {
            'jpg' => imagejpeg($imagen, null, 95),
            'webp' => imagewebp($imagen, null, 95),
            default => imagepng($imagen),
        };

        return (string) ob_get_clean();
    }

    /** Mitad izquierda roja y derecha azul: sirve para comprobar hacia dónde quedó girada */
    public static function rojoYAzul(int $ancho, int $alto, string $formato = 'jpg'): string
    {
        return self::conGd($ancho, $alto, $formato, function ($imagen) use ($ancho, $alto) {
            imagefilledrectangle($imagen, 0, 0, intdiv($ancho, 2) - 1, $alto, imagecolorallocate($imagen, 255, 0, 0));
            imagefilledrectangle($imagen, intdiv($ancho, 2), 0, $ancho, $alto, imagecolorallocate($imagen, 0, 0, 255));
        });
    }

    /** Agrega al JPEG un segmento EXIF con la orientación indicada y, opcionalmente, un comentario */
    public static function conExif(string $jpeg, int $orientacion, string $comentario = ''): string
    {
        $tiff = "MM\x00\x2A\x00\x00\x00\x08" . pack('n', 1) . pack('nnN', 0x0112, 3, 1) . pack('n', $orientacion) . "\x00\x00" . pack('N', 0);
        $exif = "Exif\x00\x00" . $tiff;
        $segmento = "\xFF\xE1" . pack('n', strlen($exif) + 2) . $exif;

        if ($comentario !== '') {
            $segmento .= "\xFF\xFE" . pack('n', strlen($comentario) + 2) . $comentario;
        }

        return substr($jpeg, 0, 2) . $segmento . substr($jpeg, 2);
    }
}
