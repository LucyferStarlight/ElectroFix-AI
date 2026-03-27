<?php

namespace App\Models;

use App\Services\BillingService;
use App\Services\Exceptions\QuoteVersionException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class BillingDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'customer_id',
        'order_id',
        'document_number',
        'document_type',
        'version',
        'status',
        'is_active',
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
            'is_active' => 'boolean',
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

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillingDocumentItem::class);
    }

    public function repairOutcomes(): HasMany
    {
        return $this->hasMany(OrderRepairOutcome::class);
    }

    public function createNewVersion(User $actor, array $payload): self
    {
        if (! $this->isQuote()) {
            throw QuoteVersionException::onlyQuotesCanBeVersioned();
        }

        return app(BillingService::class)->createNewQuoteVersion($this, $actor, $payload);
    }

    public function approveQuote(?string $approvedBy = 'customer', string $approvalChannel = 'system'): self
    {
        if (! $this->isQuote()) {
            throw QuoteVersionException::onlyQuotesCanBeApproved();
        }

        $order = $this->order;
        if (! $order) {
            throw QuoteVersionException::quoteRequiresSingleOrder();
        }

        DB::transaction(function () use ($order, $approvedBy, $approvalChannel): void {
            $this->forceFill([
                'status' => 'approved',
                'is_active' => true,
            ])->save();

            $this->newQuery()
                ->where('order_id', $order->id)
                ->where('document_type', 'quote')
                ->whereKeyNot($this->getKey())
                ->update(['is_active' => false]);

            $order->approve($approvedBy, $approvalChannel);
        });

        return $this->refresh();
    }

    public function rejectQuote(): self
    {
        if (! $this->isQuote()) {
            throw QuoteVersionException::onlyQuotesCanBeRejected();
        }

        $this->forceFill([
            'status' => 'rejected',
            'is_active' => false,
        ])->save();

        return $this->refresh();
    }

    public function isQuote(): bool
    {
        return $this->document_type === 'quote';
    }

    public function customerDisplayName(): string
    {
        if ($this->customer_mode === 'walk_in') {
            return $this->walk_in_name ?: 'Cliente de Mostrador';
        }

        return $this->customer?->name ?: 'Cliente no disponible';
    }
}
