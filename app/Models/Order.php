<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'customer_id',
        'equipment_id',
        'technician',
        'technician_profile_id',
        'symptoms',
        'status',
        'estimated_cost',
        'ai_potential_causes',
        'ai_estimated_time',
        'ai_suggested_parts',
        'ai_technical_advice',
        'ai_diagnosed_at',
        'ai_diagnosis_pending',
        'ai_diagnosis_error',
        'ai_tokens_used',
        'ai_provider',
        'ai_model',
        'ai_requires_parts_replacement',
        'ai_cost_repair_labor',
        'ai_cost_replacement_parts',
        'ai_cost_replacement_total',
    ];

    protected function casts(): array
    {
        return [
            'estimated_cost' => 'decimal:2',
            'ai_potential_causes' => 'array',
            'ai_suggested_parts' => 'array',
            'ai_diagnosed_at' => 'datetime',
            'ai_diagnosis_pending' => 'boolean',
            'ai_requires_parts_replacement' => 'boolean',
            'ai_cost_repair_labor' => 'decimal:2',
            'ai_cost_replacement_parts' => 'decimal:2',
            'ai_cost_replacement_total' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function billingItems(): HasMany
    {
        return $this->hasMany(BillingDocumentItem::class);
    }

    public function aiUsages(): HasMany
    {
        return $this->hasMany(CompanyAiUsage::class);
    }

    public function technicianProfile(): BelongsTo
    {
        return $this->belongsTo(TechnicianProfile::class, 'technician_profile_id');
    }

    public function diagnostics(): HasMany
    {
        return $this->hasMany(OrderDiagnostic::class);
    }

    public function latestDiagnostic(): HasOne
    {
        return $this->hasOne(OrderDiagnostic::class)->latestOfMany('version');
    }

    public function assignmentLogs(): HasMany
    {
        return $this->hasMany(OrderAssignmentLog::class);
    }

    public function equipmentEvents(): HasMany
    {
        return $this->hasMany(EquipmentEvent::class);
    }

    public function repairOutcome(): HasOne
    {
        return $this->hasOne(OrderRepairOutcome::class);
    }
}
