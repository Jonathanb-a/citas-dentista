<?php

namespace Tests\Support;

use Illuminate\Http\UploadedFile;

/**
 * Archivos de imagen con bytes reales para las pruebas. No usa GD
 * (UploadedFile::fake()->image() lo necesita y no siempre está instalado).
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
}
