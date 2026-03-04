<?php

namespace App\Services;

use App\Models\BillingDocument;
use App\Models\Company;
use App\Models\CompanyAiUsage;
use App\Models\Order;
use App\Models\TechnicianProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardMetricsService
{
    public function companyMetrics(Company $company, Carbon $from, Carbon $to): array
    {
        $cacheKey = sprintf('dashboard-metrics:%d:%s:%s', $company->id, $from->format('Ymd'), $to->format('Ymd'));

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($company, $from, $to): array {
            $ordersBase = Order::query()
                ->where('company_id', $company->id)
                ->whereBetween('created_at', [$from, $to]);

            $ordersByStatus = (clone $ordersBase)
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            $billingBase = BillingDocument::query()
                ->where('company_id', $company->id)
                ->where('document_type', 'invoice')
                ->whereBetween('issued_at', [$from, $to]);

            $quotesCount = BillingDocument::query()
                ->where('company_id', $company->id)
                ->where('document_type', 'quote')
                ->whereBetween('issued_at', [$from, $to])
                ->count();
            $invoicesCount = $billingBase->count();

            $aiSuccess = CompanyAiUsage::query()
                ->where('company_id', $company->id)
                ->where('status', 'success')
                ->whereBetween('created_at', [$from, $to])
                ->count();
            $aiBlocked = CompanyAiUsage::query()
                ->where('company_id', $company->id)
                ->whereIn('status', ['blocked_plan', 'blocked_quota', 'blocked_tokens'])
                ->whereBetween('created_at', [$from, $to])
                ->count();

            $technicians = TechnicianProfile::query()
                ->where('company_id', $company->id)
                ->where('is_assignable', true)
                ->get(['id', 'status']);

            $activeTechnicians = $technicians->whereIn('status', ['available', 'assigned'])->count();
            $assignedTechnicians = $technicians->where('status', 'assigned')->count();
            $availableTechnicians = $technicians->where('status', 'available')->count();

            $topReincidence = Order::query()
                ->where('company_id', $company->id)
                ->whereBetween('created_at', [$from, $to])
                ->select('equipment_id', DB::raw('count(*) as total'))
                ->groupBy('equipment_id')
                ->orderByDesc('total')
                ->limit(5)
                ->with('equipment:id,brand,model,serial_number')
                ->get()
                ->map(fn (Order $row): array => [
                    'equipment_id' => $row->equipment_id,
                    'equipment' => trim(($row->equipment?->brand ?? '').' '.($row->equipment?->model ?? '')),
                    'serial_number' => $row->equipment?->serial_number,
                    'total_orders' => (int) $row->total,
                ])->toArray();

            return [
                'orders' => [
                    'total' => (clone $ordersBase)->count(),
                    'today' => Order::query()->where('company_id', $company->id)->whereDate('created_at', now())->count(),
                    'week' => Order::query()->where('company_id', $company->id)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                    'month' => Order::query()->where('company_id', $company->id)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                    'by_status' => $ordersByStatus,
                ],
                'revenue' => [
                    'gross' => (float) $billingBase->sum('total'),
                    'net' => (float) $billingBase->sum('subtotal'),
                    'vat' => (float) $billingBase->sum('vat_amount'),
                ],
                'conversion' => [
                    'quotes' => $quotesCount,
                    'invoices' => $invoicesCount,
                    'quote_to_invoice_rate' => $quotesCount > 0
                        ? round(($invoicesCount / $quotesCount) * 100, 2)
                        : 0.0,
                ],
                'ai' => [
                    'diagnostics_success' => $aiSuccess,
                    'diagnostics_blocked' => $aiBlocked,
                    'acceptance_rate' => ($aiSuccess + $aiBlocked) > 0
                        ? round(($aiSuccess / ($aiSuccess + $aiBlocked)) * 100, 2)
                        : 0.0,
                ],
                'technicians' => [
                    'active' => $activeTechnicians,
                    'available' => $availableTechnicians,
                    'assigned' => $assignedTechnicians,
                    'inactive' => $technicians->where('status', 'inactive')->count(),
                ],
                'equipment_reincidence' => $topReincidence,
            ];
        });
    }
}

