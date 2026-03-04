<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\CompanySubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeveloperSubscriptionController extends Controller
{
    public function __construct(private readonly CompanySubscriptionService $companySubscriptionService)
    {
    }

    public function assignDeveloperTest(Request $request, Company $company): RedirectResponse
    {
        $this->companySubscriptionService->syncBusinessSubscription($company, 'developer_test', 'monthly', $company->subscription?->stripe_subscription_id);

        return back()->with('success', 'Plan Developer_Test asignado manualmente.');
    }
}
