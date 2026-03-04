<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderAssignmentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'order_id',
        'from_technician_profile_id',
        'to_technician_profile_id',
        'changed_by_user_id',
        'reason',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function fromTechnician(): BelongsTo
    {
        return $this->belongsTo(TechnicianProfile::class, 'from_technician_profile_id');
    }

    public function toTechnician(): BelongsTo
    {
        return $this->belongsTo(TechnicianProfile::class, 'to_technician_profile_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}

