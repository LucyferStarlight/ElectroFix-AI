<?php

return [
    'provider' => env('AI_PROVIDER', 'groq'),
    'fallback_on_missing_key' => env('AI_FALLBACK_ON_MISSING_KEY', false),
];
