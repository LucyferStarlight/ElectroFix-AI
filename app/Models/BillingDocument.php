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

    public function markAsSent(): self
    {
        if (! $this->isQuote()) {
            throw QuoteVersionException::onlyQuotesCanBeSent();
        }

        $this->ensureCurrentQuoteContext();

        DB::transaction(function (): void {
            $this->forceFill([
                'status' => 'sent',
                'is_active' => true,
            ])->save();

            $this->deactivateOtherVersions();
        });

        return $this->refresh();
    }

    public function approve(?string $approvedBy = 'customer', string $approvalChannel = 'system'): self
    {
        if (! $this->isQuote()) {
            throw QuoteVersionException::onlyQuotesCanBeApproved();
        }

        DB::transaction(function () use ($approvedBy, $approvalChannel): void {
            $document = self::query()
                ->lockForUpdate()
                ->find($this->id);

            if (! $document) {
                throw QuoteVersionException::quoteRequiresActiveOrderContext();
            }

            $status = (string) $document->status;
            if (! in_array($status, ['sent', 'approved'], true)) {
                throw QuoteVersionException::invalidQuoteStatusForApproval($status);
            }

            $order = $document->ensureCurrentQuoteContext();

            $document->forceFill([
                'status' => 'approved',
                'is_active' => true,
            ])->save();

            $document->deactivateOtherVersions();

            $order->approve($approvedBy, $approvalChannel);
        });

        return $this->refresh();
    }

    public function reject(): self
    {
        if (! $this->isQuote()) {
            throw QuoteVersionException::onlyQuotesCanBeRejected();
        }

        $this->ensureCurrentQuoteContext();

        $this->forceFill([
            'status' => 'rejected',
            'is_active' => false,
        ])->save();

        return $this->refresh();
    }

    public function approveQuote(?string $approvedBy = 'customer', string $approvalChannel = 'system'): self
    {
        return $this->approve($approvedBy, $approvalChannel);
    }

    public function rejectQuote(): self
    {
        return $this->reject();
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

    private function ensureCurrentQuoteContext(): Order
    {
        $this->loadMissing('order', 'customer');
        $order = $this->order?->loadMissing(['customer:id,company_id', 'equipment:id,company_id,customer_id']);

        if (! $order) {
            throw QuoteVersionException::quoteRequiresSingleOrder();
        }

        if ((int) $order->company_id !== (int) $this->company_id) {
            throw QuoteVersionException::quoteCompanyMismatch();
        }

        if ($this->customer_id !== null && (int) $order->customer_id !== (int) $this->customer_id) {
            throw QuoteVersionException::quoteCustomerMismatch();
        }

        if (! $order->customer || ! $order->equipment) {
            throw QuoteVersionException::quoteRequiresActiveOrderContext();
        }

        if ((int) $order->customer->company_id !== (int) $order->company_id
            || (int) $order->equipment->company_id !== (int) $order->company_id
            || (int) $order->equipment->customer_id !== (int) $order->customer_id) {
            throw QuoteVersionException::quoteRequiresActiveOrderContext();
        }

        return $order;
    }

    private function deactivateOtherVersions(): void
    {
        $this->newQuery()
            ->where('order_id', $this->order_id)
            ->where('document_type', 'quote')
            ->whereKeyNot($this->getKey())
            ->update(['is_active' => false]);
    }
}
