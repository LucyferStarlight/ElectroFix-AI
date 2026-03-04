<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'requested_plan_id',
        'requested_billing_period',
        'effective_at',
        'status',
        'requested_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'effective_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function requestedPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'requested_plan_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }
}
