<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderRepairOutcome extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'billing_document_id',
        'company_id',
        'repair_outcome',
        'outcome_notes',
        'work_performed',
        'actual_amount_charged',
        'aris_estimated_cost',
        'had_ai_diagnosis',
        'diagnostic_accuracy',
        'technician_notes',
        'actual_causes',
        'ai_diagnosis',
        'real_diagnosis',
        'repair_applied',
        'confidence_score',
        'validated',
        'feeds_aris_training',
        'plan_at_close',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'repair_outcome' => 'string',
            'actual_amount_charged' => 'decimal:2',
            'aris_estimated_cost' => 'decimal:2',
            'had_ai_diagnosis' => 'boolean',
            'actual_causes' => 'array',
            'ai_diagnosis' => 'array',
            'real_diagnosis' => 'array',
            'confidence_score' => 'decimal:2',
            'validated' => 'boolean',
            'feeds_aris_training' => 'boolean',
            'delivered_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function billingDocument(): BelongsTo
    {
        return $this->belongsTo(BillingDocument::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForArisTraining(Builder $query): Builder
    {
        return $query->where('feeds_aris_training', true);
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }
}
