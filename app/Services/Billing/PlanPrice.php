<?php

namespace App\Services\Billing;

class PlanPrice
{
    public static function get(string $plan, string $period): ?string
    {
        $plans = [

            'starter' => [
                'monthly' => env('STARTER_MONTHLY'),
                'semiannual' => env('STARTER_SEMIANNUAL'),
                'annual' => env('STARTER_ANNUAL'),
            ],

            'pro' => [
                'monthly' => env('PRO_MONTHLY'),
                'semiannual' => env('PRO_SEMIANNUAL'),
                'annual' => env('PRO_ANNUAL'),
            ],

            'enterprise' => [
                'monthly' => env('ENTERPRISE_MONTHLY'),
                'semiannual' => env('ENTERPRISE_SEMIANNUAL'),
                'annual' => env('ENTERPRISE_ANNUAL'),
            ],

        ];

        return $plans[$plan][$period] ?? null;
    }
}