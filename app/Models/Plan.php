<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_public',
        'ai_enabled',
        'max_ai_requests',
        'max_ai_tokens',
        'overage_enabled',
        'overage_price_per_request',
        'overage_price_per_1000_tokens',
        'stripe_overage_requests_price_id',
        'stripe_overage_tokens_price_id',
        'ai_provider_override',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'ai_enabled' => 'boolean',
            'overage_enabled' => 'boolean',
            'overage_price_per_request' => 'decimal:4',
            'overage_price_per_1000_tokens' => 'decimal:4',
            'ai_provider_override' => 'string',
        ];
    }

    public function prices(): HasMany
    {
        return $this->hasMany(PlanPrice::class);
    }
}
