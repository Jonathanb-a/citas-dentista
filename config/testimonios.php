<?php

/*
 * Testimonios de pacientes con cuenta (módulo "Testimonios"). Los ajusta el .env sin tocar código.
 */
return [
    // Texto del testimonio (en caracteres, ya limpio de invisibles, enlaces y marcado)
    'texto_min' => 20,
    'texto_max' => 300,

    // Cuántos puede destacar la doctora (aparecen primero en la landing)
    'max_destacados' => (int) (env('TESTIMONIOS_DESTACADOS_MAX') ?: 6),

    // Tope de tarjetas en la landing (destacados primero), contando las opiniones manuales
    'max_landing' => 12,

    // El promedio de estrellas se muestra desde este número de testimonios aprobados
    'promedio_minimo' => (int) (env('TESTIMONIOS_PROMEDIO_MINIMO') ?: 3),

    // Crear, editar y borrar: envíos por usuario y por hora
    'limite_hora' => (int) (env('TESTIMONIOS_LIMITE_HORA') ?: 5),
];
