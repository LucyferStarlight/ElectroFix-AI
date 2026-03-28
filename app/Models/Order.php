<?php

namespace App\Models;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Services\Exceptions\OrderApprovalException;
use App\Services\Exceptions\OrderPaymentException;
use App\Services\Exceptions\OrderWorkflowException;
use App\Services\OrderStateMachine;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'customer_id',
        'equipment_id',
        'technician',
        'technician_profile_id',
        'symptoms',
        'status',
        'payment_status',
        'total_paid',
        'approved_at',
        'approved_by',
        'approval_channel',
        'rejected_at',
        'rejection_reason',
        'estimated_cost',
        'ai_potential_causes',
        'ai_estimated_time',
        'ai_suggested_parts',
        'ai_technical_advice',
        'ai_diagnosed_at',
        'ai_diagnosis_pending',
        'ai_diagnosis_error',
        'ai_tokens_used',
        'ai_provider',
        'ai_model',
        'ai_requires_parts_replacement',
        'ai_cost_repair_labor',
        'ai_cost_replacement_parts',
        'ai_cost_replacement_total',
    ];

    protected function casts(): array
    {
        return [
            'estimated_cost' => 'decimal:2',
            'total_paid' => 'decimal:2',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'ai_potential_causes' => 'array',
            'ai_suggested_parts' => 'array',
            'ai_diagnosed_at' => 'datetime',
            'ai_diagnosis_pending' => 'boolean',
            'ai_requires_parts_replacement' => 'boolean',
            'ai_cost_repair_labor' => 'decimal:2',
            'ai_cost_replacement_parts' => 'decimal:2',
            'ai_cost_replacement_total' => 'decimal:2',
        ];
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            get: static fn (?string $value): ?string => $value === null
                ? null
                : OrderStatus::fromInput($value)->value,
            set: static fn (OrderStatus|string|null $value): ?string => $value === null
                ? null
                : OrderStatus::fromInput($value)->value
        );
    }

    public function statusEnum(): OrderStatus
    {
        return OrderStatus::fromInput((string) $this->status);
    }

    public function canTransitionTo(OrderStatus|string $status): bool
    {
        return app(OrderStateMachine::class)->canTransition($this->statusEnum(), $status);
    }

    public function availableTransitions(): array
    {
        return app(OrderStateMachine::class)->availableTransitions($this->statusEnum());
    }

    public function approve(?string $approvedBy = 'system', string $approvalChannel = 'system'): self
    {
        $approvedBy = $this->normalizeApprovedBy($approvedBy);
        $approvalChannel = $this->normalizeApprovalChannel($approvalChannel);

        DB::transaction(function () use ($approvedBy, $approvalChannel): void {
            $order = self::query()
                ->lockForUpdate()
                ->find($this->id);

            if (! $order) {
                throw OrderWorkflowException::orderNotFoundForTransition();
            }

            $order->assertCriticalRelations();

            $order->forceFill([
                'approved_at' => $order->approved_at ?? now(),
                'approved_by' => $approvedBy,
                'approval_channel' => $approvalChannel,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            $order->save();

            app(OrderStateMachine::class)->transition($order, OrderStatus::APPROVED);
        });

        return $this->refresh();
    }

    public function reject(string $reason): self
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw OrderApprovalException::rejectionReasonRequired();
        }

        DB::transaction(function () use ($reason): void {
            $order = self::query()
                ->lockForUpdate()
                ->find($this->id);

            if (! $order) {
                throw OrderWorkflowException::orderNotFoundForTransition();
            }

            $order->assertCriticalRelations();

            $order->forceFill([
                'rejected_at' => now(),
                'rejection_reason' => $reason,
                'approved_at' => null,
                'approved_by' => null,
                'approval_channel' => null,
            ]);

            $order->save();

            app(OrderStateMachine::class)->transition($order, OrderStatus::CANCELED);
        });

        return $this->refresh();
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null
            && filled($this->approval_channel)
            && $this->rejected_at === null;
    }

    public static function allowedApprovalActors(): array
    {
        return ['customer', 'system'];
    }

    public static function allowedApprovalChannels(): array
    {
        return ['whatsapp', 'system', 'verbal', 'phone', 'email', 'other'];
    }

    private function normalizeApprovedBy(?string $approvedBy): ?string
    {
        if ($approvedBy === null) {
            return null;
        }

        $approvedBy = strtolower(trim($approvedBy));

        if (! in_array($approvedBy, self::allowedApprovalActors(), true)) {
            throw OrderApprovalException::invalidApprovalActor($approvedBy);
        }

        return $approvedBy;
    }

    private function normalizeApprovalChannel(string $approvalChannel): string
    {
        $approvalChannel = strtolower(trim($approvalChannel));

        if (! in_array($approvalChannel, self::allowedApprovalChannels(), true)) {
            throw OrderApprovalException::invalidApprovalChannel($approvalChannel);
        }

        return $approvalChannel;
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

    public function billingItems(): HasMany
    {
        return $this->hasMany(BillingDocumentItem::class);
    }

    public function billingDocuments(): HasMany
    {
        return $this->hasMany(BillingDocument::class);
    }

    public function activeQuote(): HasOne
    {
        return $this->hasOne(BillingDocument::class)
            ->where('document_type', 'quote')
            ->where('is_active', true)
            ->latestOfMany('version');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function aiUsages(): HasMany
    {
        return $this->hasMany(CompanyAiUsage::class);
    }

    public function technicianProfile(): BelongsTo
    {
        return $this->belongsTo(TechnicianProfile::class, 'technician_profile_id');
    }

    public function diagnostics(): HasMany
    {
        return $this->hasMany(OrderDiagnostic::class);
    }

    public function latestDiagnostic(): HasOne
    {
        return $this->hasOne(OrderDiagnostic::class)->latestOfMany('version');
    }

    public function assignmentLogs(): HasMany
    {
        return $this->hasMany(OrderAssignmentLog::class);
    }

    public function equipmentEvents(): HasMany
    {
        return $this->hasMany(EquipmentEvent::class);
    }

    public function repairOutcome(): HasOne
    {
        return $this->hasOne(OrderRepairOutcome::class);
    }

    public function registerPayment(float $amount, array $context = []): OrderPayment
    {
        if ($amount <= 0) {
            throw OrderPaymentException::amountMustBePositive();
        }

        return DB::transaction(function () use ($amount, $context): OrderPayment {
            $order = self::query()
                ->with(['customer:id,company_id', 'equipment:id,company_id,customer_id'])
                ->lockForUpdate()
                ->find($this->id);

            if (! $order) {
                throw OrderWorkflowException::orderNotFoundForTransition();
            }

            $order->assertCriticalRelationsForPayments();
            $order->assertPaymentContextIsComplete($context);

            $payment = $order->payments()->create([
                'billing_document_id' => $context['billing_document_id'] ?? null,
                'direction' => 'payment',
                'amount' => round($amount, 2),
                'currency' => strtolower((string) ($context['currency'] ?? 'mxn')),
                'source' => (string) ($context['source'] ?? 'manual'),
                'stripe_payment_intent_id' => $context['stripe_payment_intent_id'] ?? null,
                'stripe_checkout_session_id' => $context['stripe_checkout_session_id'] ?? null,
                'stripe_charge_id' => $context['stripe_charge_id'] ?? null,
                'stripe_refund_id' => null,
                'status' => (string) ($context['status'] ?? 'succeeded'),
                'metadata' => $context['metadata'] ?? null,
                'processed_at' => $context['processed_at'] ?? now(),
            ]);

            $order->recalculatePaymentStatus();

            return $payment->fresh();
        });
    }

    public function registerRefund(float $amount, array $context = []): OrderPayment
    {
        if ($amount <= 0) {
            throw OrderPaymentException::amountMustBePositive();
        }

        $currentNetPaid = (float) self::query()
            ->whereKey($this->id)
            ->value('total_paid');

        if ($amount > $currentNetPaid) {
            throw OrderPaymentException::refundExceedsPaidAmount();
        }

        return DB::transaction(function () use ($amount, $context): OrderPayment {
            $order = self::query()
                ->with(['customer:id,company_id', 'equipment:id,company_id,customer_id'])
                ->lockForUpdate()
                ->find($this->id);

            if (! $order) {
                throw OrderWorkflowException::orderNotFoundForTransition();
            }

            $order->assertCriticalRelationsForPayments();
            $order->assertPaymentContextIsComplete($context);

            $refund = $order->payments()->create([
                'billing_document_id' => $context['billing_document_id'] ?? null,
                'direction' => 'refund',
                'amount' => round($amount, 2),
                'currency' => strtolower((string) ($context['currency'] ?? 'mxn')),
                'source' => (string) ($context['source'] ?? 'manual'),
                'stripe_payment_intent_id' => $context['stripe_payment_intent_id'] ?? null,
                'stripe_checkout_session_id' => $context['stripe_checkout_session_id'] ?? null,
                'stripe_charge_id' => $context['stripe_charge_id'] ?? null,
                'stripe_refund_id' => $context['stripe_refund_id'] ?? null,
                'status' => (string) ($context['status'] ?? 'refunded'),
                'metadata' => $context['metadata'] ?? null,
                'processed_at' => $context['processed_at'] ?? now(),
            ]);

            $order->recalculatePaymentStatus();

            return $refund->fresh();
        });
    }

    public function assertCriticalRelations(): void
    {
        $this->loadMissing([
            'customer:id,company_id',
            'equipment:id,company_id,customer_id',
        ]);

        if (! $this->customer || ! $this->equipment) {
            throw OrderWorkflowException::orderRelationsIncomplete();
        }

        if ((int) $this->customer->company_id !== (int) $this->company_id) {
            throw OrderWorkflowException::orderCustomerCompanyMismatch();
        }

        if ((int) $this->equipment->company_id !== (int) $this->company_id) {
            throw OrderWorkflowException::orderEquipmentCompanyMismatch();
        }

        if ((int) $this->equipment->customer_id !== (int) $this->customer_id) {
            throw OrderWorkflowException::orderEquipmentCustomerMismatch();
        }
    }

    private function assertCriticalRelationsForPayments(): void
    {
        try {
            $this->assertCriticalRelations();
        } catch (OrderWorkflowException $exception) {
            throw OrderPaymentException::inconsistentOrderRelations();
        }
    }

    private function assertPaymentContextIsComplete(array $context): void
    {
        $source = trim((string) ($context['source'] ?? 'manual'));
        if ($source === '') {
            throw OrderPaymentException::invalidPaymentContext('source');
        }

        $currency = trim((string) ($context['currency'] ?? 'mxn'));
        if ($currency === '') {
            throw OrderPaymentException::invalidPaymentContext('currency');
        }
    }

    public function isFullyPaid(): bool
    {
        $totalOrderCost = $this->totalOrderCost();

        return $totalOrderCost > 0
            && (float) $this->total_paid >= $totalOrderCost;
    }

    public function totalOrderCost(): float
    {
        $invoicedAmount = (float) $this->billingItems()->sum('line_total');

        if ($invoicedAmount > 0) {
            return round($invoicedAmount, 2);
        }

        $activeQuoteAmount = (float) $this->billingDocuments()
            ->where('document_type', 'quote')
            ->where('is_active', true)
            ->value('total');

        if ($activeQuoteAmount > 0) {
            return round($activeQuoteAmount, 2);
        }

        return round((float) $this->estimated_cost, 2);
    }

    public function paymentDueAmount(): float
    {
        return $this->totalOrderCost();
    }

    public function outstandingBalance(): float
    {
        return max(0, round($this->paymentDueAmount() - (float) $this->total_paid, 2));
    }

    public function recalculatePaymentStatus(): self
    {
        $payments = (float) $this->payments()->where('direction', 'payment')->sum('amount');
        $refunds = (float) $this->payments()->where('direction', 'refund')->sum('amount');
        $netPaid = round(max(0, $payments - $refunds), 2);

        $hasPayments = $payments > 0;
        $hasRefunds = $refunds > 0;
        $totalOrderCost = $this->totalOrderCost();

        $paymentStatus = match (true) {
            $hasPayments && $netPaid <= 0 && $hasRefunds => OrderPaymentStatus::REFUNDED->value,
            $totalOrderCost > 0 && $netPaid >= $totalOrderCost => OrderPaymentStatus::PAID->value,
            $netPaid > 0 => OrderPaymentStatus::PARTIAL->value,
            default => OrderPaymentStatus::PENDING->value,
        };

        $this->forceFill([
            'total_paid' => $netPaid,
            'payment_status' => $paymentStatus,
        ])->save();

        return $this->refresh();
    }

    public function refreshPaymentTotals(): self
    {
        return $this->recalculatePaymentStatus();
    }

    public function hasQuotes(): bool
    {
        return $this->billingDocuments()
            ->where('document_type', 'quote')
            ->exists();
    }

    public function hasApprovedActiveQuote(): bool
    {
        return $this->billingDocuments()
            ->where('document_type', 'quote')
            ->where('is_active', true)
            ->where('status', 'approved')
            ->exists();
    }
}
