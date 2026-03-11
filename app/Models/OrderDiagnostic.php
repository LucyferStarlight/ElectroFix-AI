<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDiagnostic extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'order_id',
        'version',
        'created_by_user_id',
        'source',
        'symptoms_snapshot',
        'equipment_snapshot',
        'diagnostic_summary',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}

