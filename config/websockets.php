<?php

use Illuminate\Support\Str;

return [
    'apps' => [
        [
            'id' => env('REVERB_APP_ID', (string) Str::uuid()),
            'name' => env('APP_NAME', 'SANADK'),
            'key' => env('REVERB_APP_KEY', env('PUSHER_APP_KEY')),
            'secret' => env('REVERB_APP_SECRET', env('PUSHER_APP_SECRET')),
            'path' => env('REVERB_PATH', '/'),
            'capacity' => null,
            'enable_client_messages' => false,
            'enable_statistics' => true,
        ],
    ],

    'dashboard' => [
        'port' => env('REVERB_DASHBOARD_PORT', 8080),
    ],

    'ssl' => [
        'local_cert' => env('REVERB_SSL_LOCAL_CERT', null),
        'local_pk' => env('REVERB_SSL_LOCAL_PK', null),
        'passphrase' => env('REVERB_SSL_PASSPHRASE', null),
    ],

    'max_request_size_in_kb' => 250,

    'path' => env('REVERB_PATH', '/'),

    'allowed_origins' => [
        // e.g. 'https://your-app.example'
    ],

    'statistics' => [
        'model' => \BeyondCode\LaravelWebSockets\Statistics\Models\WebSocketsStatisticsEntry::class,
    ],
];
