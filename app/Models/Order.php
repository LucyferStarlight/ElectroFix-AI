<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'customer_id',
        'equipment_id',
        'technician',
        'symptoms',
        'status',
        'estimated_cost',
        'ai_potential_causes',
        'ai_estimated_time',
        'ai_suggested_parts',
        'ai_technical_advice',
    ];

    protected function casts(): array
    {
        return [
            'estimated_cost' => 'decimal:2',
            'ai_potential_causes' => 'array',
            'ai_suggested_parts' => 'array',
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
}
