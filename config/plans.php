<?php

return [
    'starter' => [
        'label' => 'Starter',
        'price' => env('PLAN_PRICE_STARTER', 299),
        'ai_enabled' => false,
        'features' => [
            'Gestión básica de órdenes',
            'Inventario esencial',
            'Facturación interna',
            'Soporte por correo',
        ],
    ],
    'pro' => [
        'label' => 'Pro',
        'price' => env('PLAN_PRICE_PRO', 599),
        'ai_enabled' => false,
        'features' => [
            'Órdenes y equipos ilimitados',
            'Inventario completo',
            'Facturación interna avanzada',
            'Reportes operativos',
        ],
    ],
    'enterprise' => [
        'label' => 'Enterprise',
        'price' => env('PLAN_PRICE_ENTERPRISE', 999),
        'ai_enabled' => true,
        'features' => [
            'IA de diagnóstico ARIS incluida',
            'Reportes avanzados',
            'Soporte prioritario',
            'Integraciones personalizadas',
        ],
    ],
];
