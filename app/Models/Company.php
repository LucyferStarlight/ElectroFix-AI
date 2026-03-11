<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Cashier\Billable;

class Company extends Model
{
    use Billable;
    use HasFactory;

    protected $fillable = [
        'name',
        'owner_name',
        'owner_email',
        'owner_phone',
        'tax_id',
        'billing_email',
        'billing_phone',
        'address_line',
        'city',
        'state',
        'country',
        'postal_code',
        'currency',
        'vat_percentage',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'trial_ends_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'vat_percentage' => 'decimal:2',
            'trial_ends_at' => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function stripeName(): string
    {
        return $this->owner_name ?: $this->name;
    }

    public function stripeEmail(): ?string
    {
        return $this->billing_email ?: $this->owner_email;
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function equipments(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function billingDocuments(): HasMany
    {
        return $this->hasMany(BillingDocument::class);
    }

    public function aiUsages(): HasMany
    {
        return $this->hasMany(CompanyAiUsage::class);
    }

    public function aiUsageSummary(): HasOne
    {
        return $this->hasOne(AiUsage::class);
    }

    public function technicianProfiles(): HasMany
    {
        return $this->hasMany(TechnicianProfile::class);
    }

    public function equipmentEvents(): HasMany
    {
        return $this->hasMany(EquipmentEvent::class);
    }

    public function orderDiagnostics(): HasMany
    {
        return $this->hasMany(OrderDiagnostic::class);
    }

    public function orderAssignmentLogs(): HasMany
    {
        return $this->hasMany(OrderAssignmentLog::class);
    }

    public function subscriptionChangeRequests(): HasMany
    {
        return $this->hasMany(SubscriptionChangeRequest::class);
    }
}
