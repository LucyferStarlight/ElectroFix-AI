<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'customer_id',
        'document_number',
        'document_type',
        'customer_mode',
        'walk_in_name',
        'source',
        'tax_mode',
        'vat_percentage',
        'subtotal',
        'vat_amount',
        'total',
        'notes',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'vat_percentage' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'issued_at' => 'datetime',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillingDocumentItem::class);
    }

    public function customerDisplayName(): string
    {
        if ($this->customer_mode === 'walk_in') {
            return $this->walk_in_name ?: 'Cliente de Mostrador';
        }

        return $this->customer?->name ?: 'Cliente no disponible';
    }
}
