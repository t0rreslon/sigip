<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Asistente con IA local (Ollama). Gratis por uso: el modelo corre en el equipo
    // y los datos no salen de la institución.
    'ollama' => [
        'url' => env('OLLAMA_URL', 'http://127.0.0.1:11434'),
        'modelo' => env('OLLAMA_MODELO', 'qwen2.5:7b'),
        'tiempo' => (int) env('OLLAMA_TIEMPO', 120),
        // false = usar solo el procesador (por ejemplo, si el controlador de la tarjeta de video no es compatible).
        'gpu' => filter_var(env('OLLAMA_GPU', true), FILTER_VALIDATE_BOOL),
    ],

];
