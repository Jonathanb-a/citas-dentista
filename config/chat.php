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

    // Las respuestas de la IA se recortan a esto antes de firmarlas: vuelven en el historial y
    // deben caber en la validación (el visitante sigue teniendo máx. `max_caracteres` por mensaje)
    'max_caracteres_respuesta' => 1200,

    // Bucle de herramientas: vueltas por mensaje (cada una es una llamada a la IA y gasta del
    // tope global) y herramientas que se atienden en una misma vuelta
    'max_vueltas' => 4,
    'max_herramientas_por_vuelta' => 3,

    // Agendado por chat
    'agenda' => [
        // Citas desde mañana (el día de hoy no se ofrece) y hasta 3 meses, como el agendado normal
        'dias_minimos' => 1,
        'meses_maximos' => 3,
        // Espacios que devuelve una consulta de horarios
        'max_horarios' => 12,
        // Citas activas (pendientes o confirmadas) por teléfono, como el agendado normal
        'citas_activas_max' => 3,
        // Citas agendadas por chat al día: por IP y en total (el spam de agenda es el riesgo)
        'limite_ip_dia' => (int) (env('CHAT_CITAS_LIMITE_IP_DIA') ?: 2),
        'limite_dia' => (int) (env('CHAT_CITAS_LIMITE_DIA') ?: 10),
        // Vigencia de la propuesta que espera el "sí" del paciente
        'propuesta_minutos' => 15,
        // Los pacientes del chat no tienen correo: llevan uno de este dominio (.invalid nunca existe)
        'correo_dominio' => 'chat.dentissa.invalid',
    ],

    // Instrucciones del sistema; el servidor rellena {{nombre}}, {{subtitulo}}, {{direccion}} y {{fecha_hora}}
    'prompt' => resource_path('prompts/asistente.md'),

    // Lo que ve el visitante (nunca un error técnico)
    'mensajes' => [
        'limite_minuto' => 'Estás escribiendo muy rápido. Espera un momento y vuelve a intentarlo.',
        'limite_dia' => 'Llegaste al máximo de mensajes de hoy. Puedes agendar tu cita desde esta página '
            . 'o comunicarte con el consultorio.',
        'limite_global' => 'Hoy ya atendí muchas conversaciones y no puedo responder más por este medio. '
            . 'Puedes agendar tu cita desde esta página o comunicarte con el consultorio.',
        'vueltas' => 'No logré completar eso por aquí. Intenta con un mensaje más sencillo o comunícate '
            . 'con el consultorio.',
        'no_disponible' => 'No es posible agendar esta cita por el chat. Indica al paciente que se comunique '
            . 'con el consultorio.',
        // Textos del agendado que escribe el servidor (no la IA): exactos y sin gastar una llamada más.
        // Marcadores: {servicio} {fecha} {hora} {nombre} {telefono}
        'propuesta' => "Esto es lo que voy a agendar:\n• Servicio: {servicio}\n• Fecha: {fecha}\n• Hora: {hora}\n"
            . "• Nombre: {nombre}\n• Teléfono: {telefono}\n\n¿Confirmas que quieres agendar esta cita? "
            . 'Responde "sí" para confirmar o dime qué quieres cambiar.',
        'agendada' => "Listo, tu cita quedó registrada:\n• Servicio: {servicio}\n• Fecha: {fecha}\n• Hora: {hora}\n"
            . "• Nombre: {nombre}\n\nQueda pendiente hasta que el consultorio la confirme: te llamarán al "
            . '{telefono}. Para cambiarla o cancelarla, comunícate con el consultorio.',
        'mensaje_invalido' => 'Escribe un mensaje de hasta 500 caracteres.',
        'historial_invalido' => 'Esta conversación ya no es válida. Empieza una nueva.',
    ],
];
