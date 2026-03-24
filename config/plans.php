<?php

return [
    'starter' => [
        'label' => 'Básico',
        'price' => env('PLAN_PRICE_STARTER', 299),
        'ai_enabled' => true,
        'features' => [
            'Gestión básica de órdenes',
            'Inventario esencial',
            'Facturación interna',
            'Diagnóstico IA · 10 consultas/mes',
            'Contribuye al entrenamiento de ARIS',
        ],
    ],
    'pro' => [
        'label' => 'Profesional',
        'price' => env('PLAN_PRICE_PRO', 599),
        'ai_enabled' => true,
        'features' => [
            'Órdenes y equipos ilimitados',
            'Inventario completo',
            'Facturación interna avanzada',
            'Reportes operativos',
            'Diagnóstico IA · 70 consultas/mes',
            'Entrenamiento ARIS por caso con IA',
        ],
    ],
    'enterprise' => [
        'label' => 'Empresarial',
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
