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
];
