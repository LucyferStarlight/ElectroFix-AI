<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TechnicianProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'employee_code',
        'display_name',
        'specialties',
        'status',
        'max_concurrent_orders',
        'hourly_cost',
        'is_assignable',
    ];

    protected function casts(): array
    {
        return [
            'specialties' => 'array',
            'hourly_cost' => 'decimal:2',
            'is_assignable' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'technician_profile_id');
    }

    public function assignmentLogsFrom(): HasMany
    {
        return $this->hasMany(OrderAssignmentLog::class, 'from_technician_profile_id');
    }

    public function assignmentLogsTo(): HasMany
    {
        return $this->hasMany(OrderAssignmentLog::class, 'to_technician_profile_id');
    }
}

