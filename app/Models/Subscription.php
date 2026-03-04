<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $table = 'company_subscriptions';

    protected $fillable = [
        'company_id',
        'plan_id',
        'stripe_subscription_id',
        'plan',
        'status',
        'billing_period',
        'current_period_end',
        'cancel_at_period_end',
        'starts_at',
        'ends_at',
        'billing_cycle',
        'user_limit',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'current_period_end' => 'datetime',
            'cancel_at_period_end' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function planModel(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }
}
