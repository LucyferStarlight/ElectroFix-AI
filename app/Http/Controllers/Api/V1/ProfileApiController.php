<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\Request;

class ProfileApiController extends Controller
{
    use ApiResponse;

    public function show(Request $request)
    {
        $user = $request->user()->loadMissing('company.subscription', 'technicianProfile');

        return $this->success([
            'user' => (new UserResource($user))->resolve(),
            'company' => $user->company ? [
                'id' => $user->company->id,
                'name' => $user->company->name,
                'currency' => $user->company->currency,
                'subscription' => $user->company->subscription ? [
                    'plan' => $user->company->subscription->plan,
                    'status' => $user->company->subscription->status,
                    'billing_period' => $user->company->subscription->billing_period,
                    'starts_at' => $user->company->subscription->starts_at?->toDateString(),
                    'ends_at' => $user->company->subscription->ends_at?->toDateString(),
                    'current_period_end' => $user->company->subscription->current_period_end?->toIso8601String(),
                    'cancel_at_period_end' => (bool) $user->company->subscription->cancel_at_period_end,
                ] : null,
            ] : null,
            'technician_profile' => $user->technicianProfile ? [
                'id' => $user->technicianProfile->id,
                'display_name' => $user->technicianProfile->display_name,
                'status' => $user->technicianProfile->status,
            ] : null,
        ]);
    }
}
