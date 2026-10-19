<?php

/**
 * Subida de imágenes (App\Services\ImagenService), reutilizable por cualquier módulo.
 *
 * El disco se define en config/filesystems.php ("imagenes") y se elige por .env.
 * En Railway los archivos del contenedor se pierden en cada deploy: hay que montar
 * un Volume y apuntar IMAGENES_ROOT a él (ver CLAUDE.md, sección "Imágenes").
 */
return [
    // Nombre del disco de config/filesystems.php donde se guardan las imágenes
    'disk' => env('IMAGENES_DISK', 'imagenes'),

    // Peso máximo por imagen, en KB
    'max_kb' => 5120,

    // Lado máximo en píxeles (evita imágenes gigantes que saturan memoria al mostrarse)
    'max_px' => 4096,

    // Redimensionado al guardar (solo si la extensión GD está cargada; si no, se guarda tal cual).
    // Reduce a este ancho máximo sin ampliar nunca, corrige la rotación EXIF y quita los metadatos.
    // En Railway GD se instala con la variable RAILPACK_PHP_EXTENSIONS=gd.
    'redimensionar' => (bool) env('IMAGENES_REDIMENSIONAR', true),
    'ancho_max' => 1600,
    'calidad' => 85, // JPG y WebP
];
