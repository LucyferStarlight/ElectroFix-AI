<?php

return [
    'trial_promo' => [
        'start_at' => env('TRIAL_PROMO_START_AT'),
        'start_date' => env('TRIAL_PROMO_START_DATE'),
        'start_time' => env('TRIAL_PROMO_START_TIME', '01:00:00'),
        'timezone' => env('TRIAL_PROMO_TIMEZONE', 'America/Mexico_City'),
        'duration_months' => (int) env('TRIAL_PROMO_DURATION_MONTHS', 6),
    ],
];
