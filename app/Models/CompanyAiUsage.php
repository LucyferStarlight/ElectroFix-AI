<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyAiUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'order_id',
        'year_month',
        'plan_snapshot',
        'prompt_chars',
        'response_chars',
        'prompt_tokens_estimated',
        'response_tokens_estimated',
        'total_tokens_estimated',
        'status',
        'error_message',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

