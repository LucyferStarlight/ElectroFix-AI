<?php

return [
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', env('CASHIER_WEBHOOK_SECRET', '')),

    'plans' => [
        'starter' => [
            'label' => 'Starter',
            'prices' => [
                'monthly' => env('STRIPE_PRICE_STARTER_MONTHLY', env('STARTER_MONTHLY', '')),
                'semiannual' => env('STRIPE_PRICE_STARTER_SEMIANNUAL', env('STARTER_SEMIANNUAL', '')),
                'annual' => env('STRIPE_PRICE_STARTER_ANNUAL', env('STARTER_ANNUAL', '')),
            ],
        ],
        'pro' => [
            'label' => 'Pro',
            'prices' => [
                'monthly' => env('STRIPE_PRICE_PRO_MONTHLY', env('PRO_MONTHLY', '')),
                'semiannual' => env('STRIPE_PRICE_PRO_SEMIANNUAL', env('PRO_SEMIANNUAL', '')),
                'annual' => env('STRIPE_PRICE_PRO_ANNUAL', env('PRO_ANNUAL', '')),
            ],
        ],
        'enterprise' => [
            'label' => 'Enterprise',
            'prices' => [
                'monthly' => env('STRIPE_PRICE_ENTERPRISE_MONTHLY', env('ENTERPRISE_MONTHLY', '')),
                'semiannual' => env('STRIPE_PRICE_ENTERPRISE_SEMIANNUAL', env('ENTERPRISE_SEMIANNUAL', '')),
                'annual' => env('STRIPE_PRICE_ENTERPRISE_ANNUAL', env('ENTERPRISE_ANNUAL', '')),
            ],
        ],
    ],
];
