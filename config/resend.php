<?php

/**
 * Configuración del paquete resend/resend-laravel.
 * Se publica aquí solo para apagar sus rutas por defecto: el paquete expone
 * POST /resend/webhook y este proyecto no usa webhooks de Resend. Así la ruta
 * no queda abierta aunque se olvide poner RESEND_ROUTES en el .env.
 */
return [

    // La misma clave que usa el mailer 'resend' (config/services.php)
    'api_key' => env('RESEND_API_KEY'),

    // Apagado por defecto: el proyecto no recibe webhooks de Resend
    'routes' => env('RESEND_ROUTES', false),

    'domain' => env('RESEND_DOMAIN', null),

    'path' => env('RESEND_PATH', 'resend'),

    'webhook' => [
        'secret' => env('RESEND_WEBHOOK_SECRET'),
        'tolerance' => env('RESEND_WEBHOOK_TOLERANCE', 300),
    ],

    'middleware' => [],

];
