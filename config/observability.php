<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Canal principal de observabilidad
    |--------------------------------------------------------------------------
    */
    'channel' => env('OBSERVABILITY_CHANNEL', 'observability'),

    /*
    |--------------------------------------------------------------------------
    | Preparación para exportadores externos (sin integración activa)
    |--------------------------------------------------------------------------
    */
    'external' => [
        'enabled' => env('OBSERVABILITY_EXTERNAL_ENABLED', false),
        'driver' => env('OBSERVABILITY_EXTERNAL_DRIVER', 'none'),
        'endpoint' => env('OBSERVABILITY_EXTERNAL_ENDPOINT'),
        'api_key' => env('OBSERVABILITY_EXTERNAL_API_KEY'),
        'timeout_seconds' => (int) env('OBSERVABILITY_EXTERNAL_TIMEOUT_SECONDS', 3),
    ],
];
