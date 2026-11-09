<?php

use App\Services\IA\GeminiProveedor;
use App\Services\IA\GroqProveedor;

/*
 * Servicio de IA (App\Services\IA). Los valores vienen del .env; el código nunca
 * llama a env() fuera de los archivos de config/.
 *
 * Para agregar un proveedor (Groq, OpenAI, Anthropic…): crear su clase en
 * app/Services/IA (extiende ProveedorHttp) y registrarla aquí con su key y modelo.
 * Se elige con IA_PROVEEDOR.
 */
return [

    'proveedor' => env('IA_PROVEEDOR') ?: 'groq',

    // Una llamada colgada no debe dejar esperando al visitante
    'timeout' => 15,
    'conexion' => 5,

    // Lo que ve el usuario cuando la IA no responde (nunca un error técnico)
    'mensaje_error' => 'Por ahora no puedo responderte. Puedes agendar tu cita desde esta página '
        . 'o comunicarte con el consultorio y con gusto te atendemos.',

    'proveedores' => [

        // API compatible con OpenAI. gpt-oss-120b: modelo de producción con herramientas y plan
        // gratuito (https://console.groq.com/docs/models). Alternativa: qwen/qwen3.8-27b (Preview)
        'groq' => [
            'clase' => GroqProveedor::class,
            'api_key' => env('GROQ_API_KEY'),
            'modelo' => env('GROQ_MODELO') ?: 'openai/gpt-oss-120b',
            'url' => 'https://api.groq.com/openai/v1/chat/completions',
            // Menos razonamiento = menos latencia y menos tokens (el plan gratuito da 8K por minuto).
            // Solo se manda con modelos gpt-oss (low, medium o high); null = no mandarlo
            'reasoning_effort' => 'low',
            // Tope de la respuesta (el razonamiento cuenta dentro de este límite)
            'max_tokens' => 1024,
        ],

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
