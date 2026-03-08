<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionChangeRequest;
use App\Services\PlanCatalogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingPlansController extends Controller
{
    public function __construct(private readonly PlanCatalogService $planCatalogService)
    {
    }

    public function index(Request $request): View
    {
        $company = $request->user()?->company;
        abort_if(! $company, 404, 'Empresa no encontrada para este usuario.');

        $pendingChange = SubscriptionChangeRequest::query()
            ->where('company_id', $company->id)
            ->where('status', 'pending')
            ->orderByDesc('effective_at')
            ->first();

        return view('subscription-required', [
            'plans' => $this->planCatalogService->publicPlans(),
            'subscription' => $company->subscription,
            'pendingChange' => $pendingChange,
        ]);
    }
}
