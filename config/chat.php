<?php

/*
 * Chat público de la landing (POST /chat). Las cifras protegen la cuota de la IA:
 * cada llamada gasta. Los tres límites se ajustan desde el .env sin tocar código.
 */
return [

    // Historial que viaja del navegador en cada petición
    'max_mensajes' => 12,
    'max_caracteres' => 500,

    // Por IP: ventana móvil de un minuto y de 24 horas desde el primer mensaje
    'limite_minuto' => (int) (env('CHAT_LIMITE_MINUTO') ?: 6),
    'limite_dia' => (int) (env('CHAT_LIMITE_DIA') ?: 40),

    // Llamadas a la IA por día en total (día natural de la zona horaria del consultorio).
    // Debe quedar por debajo del tope del proveedor (plan gratuito de Groq: 1.000 al día)
    'limite_global_dia' => (int) (env('CHAT_LIMITE_GLOBAL_DIA') ?: 800),

    // Instrucciones del sistema; el servidor rellena {{nombre}}, {{subtitulo}}, {{direccion}} y {{fecha_hora}}
    'prompt' => resource_path('prompts/asistente.md'),

    // Lo que ve el visitante (nunca un error técnico)
    'mensajes' => [
        'limite_minuto' => 'Estás escribiendo muy rápido. Espera un momento y vuelve a intentarlo.',
        'limite_dia' => 'Llegaste al máximo de mensajes de hoy. Puedes agendar tu cita desde esta página '
            . 'o comunicarte con el consultorio.',
        'limite_global' => 'Hoy ya atendí muchas conversaciones y no puedo responder más por este medio. '
            . 'Puedes agendar tu cita desde esta página o comunicarte con el consultorio.',
        'mensaje_invalido' => 'Escribe un mensaje de hasta 500 caracteres.',
        'historial_invalido' => 'Esta conversación ya no es válida. Empieza una nueva.',
    ],
];
