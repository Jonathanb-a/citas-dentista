<?php

/**
 * Datos del consultorio que salen impresos en el expediente.
 * Los valores de ejemplo son provisionales: hay que confirmarlos con la
 * doctora y ponerlos en el .env (y en las variables de Railway) antes de
 * entregar el proyecto.
 */
return [
    'nombre' => env('CONSULTORIO_NOMBRE', 'Melissa López N.'),
    'subtitulo' => env('CONSULTORIO_SUBTITULO', 'Cirujano Dentista'),
    'cedula' => env('CONSULTORIO_CEDULA', null),
    'direccion' => env('CONSULTORIO_DIRECCION', 'Lázaro Cárdenas, Michoacán'),
    'telefono' => env('CONSULTORIO_TELEFONO', null),
    'correo' => env('CONSULTORIO_CORREO', null),

    // Zona horaria con la que se decide qué es "hoy" para la vigencia de anuncios
    // (la app corre en UTC: a las 6 pm de México ya sería "mañana")
    'zona_horaria' => env('CONSULTORIO_ZONA_HORARIA', 'America/Mexico_City'),
];
