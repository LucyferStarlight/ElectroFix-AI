<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationConfirmation extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'admin_user_id',
        'access_token',
        'payload_snapshot',
        'expires_at',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload_snapshot' => 'array',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
