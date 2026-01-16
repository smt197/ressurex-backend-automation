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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ollama' => [
        'url' => env('OLLAMA_URL', 'http://192.168.1.10:11434'),
        'model' => 'llama3.2:1b', // Force la valeur au lieu d'utiliser env()
        'timeout' => env('OLLAMA_TIMEOUT', 600),
        'temperature' => env('OLLAMA_TEMPERATURE', 0.1),
    ],

    'github' => [
        'token' => env('GITHUB_TOKEN', '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'),
    ],

    'dokploy' => [
        'webhook_secret' => env('DOKPLOY_WEBHOOK_SECRET'),
        'api_url' => env('DOKPLOY_API_URL'),
        'api_token' => env('DOKPLOY_API_TOKEN'),
    ],

];
