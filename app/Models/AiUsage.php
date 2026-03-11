<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsage extends Model
{
    use HasFactory;

    protected $table = 'ai_usage';

    protected $fillable = [
        'company_id',
        'ai_requests_used',
        'ai_tokens_used',
        'current_cycle_start',
        'current_cycle_end',
        'overage_requests',
        'overage_tokens',
    ];

    protected function casts(): array
    {
        return [
            'current_cycle_start' => 'date',
            'current_cycle_end' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
