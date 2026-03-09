<?php

return [
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', env('CASHIER_WEBHOOK_SECRET', '')),

    'plans' => [
        'starter' => [
            'label' => 'Starter',
            'prices' => [
                'monthly' => env('STRIPE_PRICE_STARTER_MONTHLY', ''),
                'semiannual' => env('STRIPE_PRICE_STARTER_SEMIANNUAL', ''),
                'annual' => env('STRIPE_PRICE_STARTER_ANNUAL', ''),
            ],
        ],
        'pro' => [
            'label' => 'Pro',
            'prices' => [
                'monthly' => env('STRIPE_PRICE_PRO_MONTHLY', ''),
                'semiannual' => env('STRIPE_PRICE_PRO_SEMIANNUAL', ''),
                'annual' => env('STRIPE_PRICE_PRO_ANNUAL', ''),
            ],
        ],
        'enterprise' => [
            'label' => 'Enterprise',
            'prices' => [
                'monthly' => env('STRIPE_PRICE_ENTERPRISE_MONTHLY', ''),
                'semiannual' => env('STRIPE_PRICE_ENTERPRISE_SEMIANNUAL', ''),
                'annual' => env('STRIPE_PRICE_ENTERPRISE_ANNUAL', ''),
            ],
        ],
    ],
];
