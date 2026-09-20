<?php

return [
    'currency' => [
        'default' => 'IDR',
        'symbol' => 'Rp',
    ],

    'ai' => [
        'default_provider' => env('AI_PROVIDER', 'gemini'),
        'providers' => [
            'gemini' => [
                'api_key' => env('GEMINI_API_KEY'),
                'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
                'timeout' => 5,
            ],
            'openai' => [
                'api_key' => env('OPENAI_API_KEY'),
                'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
                'timeout' => 5,
            ],
        ],
        'confidence' => [
            'high_threshold' => 0.85,
            'medium_threshold' => 0.50,
        ],
        'prompt_version' => 'v1.0',
        'rate_limit_per_hour' => 30,
    ],

    'whatsapp' => [
        'default_provider' => env('WHATSAPP_PROVIDER', 'fonnte'),
        'providers' => [
            'fonnte' => [
                'token' => env('FONNTE_TOKEN'),
                'webhook_secret' => env('FONNTE_WEBHOOK_SECRET'),
                'api_url' => env('FONNTE_API_URL', 'https://api.fonnte.com/send'),
                'timeout' => 5,
            ],
            'meta' => [
                'token' => env('WHATSAPP_CLOUD_TOKEN'),
                'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
                'webhook_verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
                'timeout' => 5,
            ],
        ],
    ],
];

