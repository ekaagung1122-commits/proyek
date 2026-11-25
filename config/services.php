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

    'chatbot' => [
        'enabled' => env('CHATBOT_AI_ENABLED', true),
        'provider' => env('CHATBOT_AI_PROVIDER', 'gemini'),
        'api_key' => env('CHATBOT_AI_API_KEY'),
        'model' => env('CHATBOT_AI_MODEL', 'gemini-3.5-flash-lite'),
        'base_url' => env('CHATBOT_AI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'timeout' => env('CHATBOT_AI_TIMEOUT', 20),
        'max_messages' => env('CHATBOT_AI_MAX_MESSAGES', 20),
    ],

    'firebase_app_check' => [
        'enabled' => env('FIREBASE_APP_CHECK_ENABLED', false),
        'project_id' => env('FIREBASE_PROJECT_ID', 'my-flutter-project-dece5'),
        'project_number' => env('FIREBASE_PROJECT_NUMBER', '595920904804'),
        'jwks_url' => env('FIREBASE_APP_CHECK_JWKS_URL', 'https://firebaseappcheck.googleapis.com/v1/jwks'),
        'jwks_cache_seconds' => env('FIREBASE_APP_CHECK_JWKS_CACHE_SECONDS', 21600),
    ],

];
