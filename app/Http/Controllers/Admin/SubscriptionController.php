<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSubscriptionRequest;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function edit(Request $request)
    {
        $company = $request->user()->company;
        abort_if(! $company, 404, 'Empresa no encontrada para este usuario.');

        $subscription = Subscription::query()->firstOrCreate(
            ['company_id' => $company->id],
            [
                'plan' => 'starter',
                'status' => 'trial',
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addMonth()->toDateString(),
                'billing_cycle' => 'monthly',
                'user_limit' => 10,
            ]
        );

        return view('admin.subscription.edit', [
            'currentPage' => 'admin-subscription',
            'subscription' => $subscription,
        ]);
    }

    public function update(UpdateSubscriptionRequest $request): RedirectResponse
    {
        $company = $request->user()->company;
        abort_if(! $company, 404, 'Empresa no encontrada para este usuario.');

        $subscription = Subscription::query()->where('company_id', $company->id)->firstOrFail();
        $subscription->update($request->validated());

        return back()->with('success', 'Suscripción actualizada.');
    }
}
