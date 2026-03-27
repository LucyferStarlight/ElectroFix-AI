<?php

return [
    'trial_promo' => [
        'start_date' => env('TRIAL_PROMO_START_DATE'),
        'duration_months' => (int) env('TRIAL_PROMO_DURATION_MONTHS', 6),
    ],
];
