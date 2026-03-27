<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDiagnostic extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'customer_id',
        'order_id',
        'equipment_id',
        'version',
        'created_by_user_id',
        'source',
        'symptoms_snapshot',
        'normalized_symptoms',
        'symptom_keywords',
        'equipment_snapshot',
        'equipment_type',
        'diagnostic_summary',
        'failure_type',
        'diagnostic_signature',
        'possible_causes',
        'recommended_actions',
        'requires_parts_replacement',
        'repair_labor_cost',
        'replacement_parts_cost',
        'replacement_total_cost',
        'estimated_time',
        'confidence_score',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'provider',
        'model',
    ];

    protected function casts(): array
    {
        return [
            'equipment_snapshot' => 'array',
            'symptom_keywords' => 'array',
            'possible_causes' => 'array',
            'recommended_actions' => 'array',
            'requires_parts_replacement' => 'boolean',
            'repair_labor_cost' => 'decimal:2',
            'replacement_parts_cost' => 'decimal:2',
            'replacement_total_cost' => 'decimal:2',
            'confidence_score' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeForCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeForEquipment(Builder $query, int $equipmentId): Builder
    {
        return $query->where('equipment_id', $equipmentId);
    }

    public function scopeForFailureType(Builder $query, string $failureType): Builder
    {
        return $query->where('failure_type', $failureType);
    }
}
