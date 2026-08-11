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

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        'enterprise' => [
            'enabled' => env('RECAPTCHA_ENTERPRISE_ENABLED', false),
            'project_id' => env('RECAPTCHA_ENTERPRISE_PROJECT_ID', 'sapp-171813'),
            'api_key' => env('RECAPTCHA_ENTERPRISE_API_KEY'),
        ],
    ],

    'llm' => [
        'enabled' => env('LLM_ENABLED', false),
        'api_key' => env('LLM_API_KEY'),
        'model' => env('LLM_MODEL', 'deepseek/deepseek-chat-v3-0324'),
        'description_model' => env('LLM_DESCRIPTION_MODEL', 'deepseek/deepseek-chat-v3-0324'),
        'endpoint' => env('LLM_ENDPOINT', 'https://openrouter.ai/api/v1/chat/completions'),
    ],

    'openrouter' => [
        'key' => env('OPENROUTER_API_KEY'),
        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'model' => env('OPENROUTER_MODEL', '~openai/gpt-latest'),
        'app_name' => env('OPENROUTER_APP_NAME', 'SAPP'),
    ],

];
