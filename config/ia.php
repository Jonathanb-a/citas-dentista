<?php

use App\Services\IA\GeminiProveedor;

/*
 * Servicio de IA (App\Services\IA). Los valores vienen del .env; el código nunca
 * llama a env() fuera de los archivos de config/.
 *
 * Para agregar un proveedor (Groq, OpenAI, Anthropic…): crear su clase en
 * app/Services/IA (extiende ProveedorHttp) y registrarla aquí con su key y modelo.
 * Se elige con IA_PROVEEDOR.
 */
return [

    'proveedor' => env('IA_PROVEEDOR') ?: 'gemini',

    // Una llamada colgada no debe dejar esperando al visitante
    'timeout' => 15,
    'conexion' => 5,

    // Lo que ve el usuario cuando la IA no responde (nunca un error técnico)
    'mensaje_error' => 'Por ahora no puedo responderte. Puedes agendar tu cita desde esta página '
        . 'o comunicarte con el consultorio y con gusto te atendemos.',

    'proveedores' => [

        'gemini' => [
            'clase' => GeminiProveedor::class,
            'api_key' => env('GEMINI_API_KEY'),
            // Flash estable según https://ai.google.dev/gemini-api/docs/models (nov 2026)
            'modelo' => env('GEMINI_MODELO') ?: 'gemini-3.6-flash',
            'url' => 'https://generativelanguage.googleapis.com/v1beta/interactions',
            // Fija la versión del contrato de la API para que no cambie sola
            'revision' => '2026-05-20',
        ],

    ],
];
