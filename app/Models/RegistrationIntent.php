<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrationIntent extends Model
{
    use HasFactory;

    protected $fillable = [
        'access_token',
        'payload_snapshot',
        'expires_at',
        'consumed_at',
        'stripe_session_id',
        'confirmation_token',
    ];

    protected function casts(): array
    {
        return [
            'payload_snapshot' => 'array',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }
}
