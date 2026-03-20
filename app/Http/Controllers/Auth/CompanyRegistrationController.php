<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\TechnicianProfile;
use App\Models\User;
use App\Services\StripeCheckoutService;
use App\Support\TechnicianStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CompanyRegistrationController extends Controller
{
    public function __construct(private readonly StripeCheckoutService $stripeCheckoutService)
    {
    }

    public function showForm()
    {
        $plans = config('plans', []);

        return view('auth.register', compact('plans'));
    }

    public function store(Request $request): RedirectResponse
    {
        $plans = config('plans', []);
        $planKeys = array_keys($plans);

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:180'],
            'admin_name' => ['required', 'string', 'max:180'],
            'email' => ['required', 'email', 'max:180'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
            'plan' => ['required', Rule::in($planKeys)],
            'phone' => ['nullable', 'string', 'max:30'],
            'terms' => ['accepted'],
        ]);

        $existingUser = User::query()->where('email', $data['email'])->first();
        if ($existingUser && $existingUser->company?->status !== 'pending_payment') {
            return back()
                ->withErrors(['email' => 'Este correo ya está registrado.'])
                ->withInput();
        }

        $company = $existingUser?->company;
        if (! $company) {
            $company = Company::query()->create([
                'name' => $data['company_name'],
                'owner_name' => $data['admin_name'],
                'owner_email' => $data['email'],
                'owner_phone' => $data['phone'] ?? null,
                'billing_email' => $data['email'],
                'status' => 'pending_payment',
            ]);
        } else {
            $company->update([
                'name' => $data['company_name'],
                'owner_name' => $data['admin_name'],
                'owner_email' => $data['email'],
                'owner_phone' => $data['phone'] ?? $company->owner_phone,
                'billing_email' => $data['email'],
                'status' => 'pending_payment',
            ]);
        }

        $admin = $existingUser ?: User::query()->create([
            'company_id' => $company->id,
            'name' => $data['admin_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'admin',
            'is_active' => true,
            'can_access_billing' => true,
            'can_access_inventory' => true,
            'must_change_password' => false,
        ]);

        if ($existingUser) {
            $admin->update([
                'name' => $data['admin_name'],
                'password' => Hash::make($data['password']),
            ]);
        }

        if (! $admin->technicianProfile) {
            $this->createTechnicianProfile($company->id, $admin);
        }

        $plan = (string) $data['plan'];
        $billingPeriod = 'monthly';

        if (! $company->stripe_id) {
            $company->stripe_id = $this->stripeCheckoutService->createCustomer(
                $company->name,
                $data['email'],
                $data['phone'] ?? null
            );
            $company->save();
        }

        $priceId = (string) data_get(config('stripe.plans', []), "{$plan}.prices.{$billingPeriod}");
        if ($priceId === '') {
            return back()->withErrors([
                'plan' => 'El plan seleccionado no está configurado correctamente.',
            ])->withInput();
        }

        $successUrl = url('/onboarding/success').'?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = route('register');

        $metadata = [
            'company_id' => (string) $company->id,
            'plan' => $plan,
            'billing_period' => $billingPeriod,
            'company_name' => $company->name,
            'admin_name' => $admin->name,
            'admin_email' => $admin->email,
        ];

        $session = $this->stripeCheckoutService->createCheckoutSession([
            'mode' => 'subscription',
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer' => $company->stripe_id,
            'metadata' => $metadata,
            'subscription_data' => [
                'metadata' => $metadata,
            ],
        ]);

        $company->update([
            'pending_plan' => $plan,
            'pending_billing_period' => $billingPeriod,
            'stripe_checkout_session_id' => $session['id'],
        ]);

        return redirect()->away($session['url']);
    }

    public function success(Request $request)
    {
        $sessionId = (string) $request->query('session_id', '');
        $company = $sessionId !== ''
            ? Company::query()->where('stripe_checkout_session_id', $sessionId)->first()
            : null;

        $plan = $company?->subscription?->plan ?? $company?->pending_plan ?? null;
        $planData = $plan ? (config('plans')[$plan] ?? null) : null;

        return view('onboarding.success', [
            'company' => $company,
            'plan' => $plan,
            'planData' => $planData,
            'status' => $company?->status ?? 'pending_payment',
        ]);
    }

    public function suspended(Request $request)
    {
        $user = $request->user();
        $company = $user?->company;

        return view('account.suspended', [
            'company' => $company,
            'supportEmail' => config('support.email'),
            'supportWhatsapp' => config('support.whatsapp_url'),
        ]);
    }

    public function retryCheckout(Request $request): RedirectResponse
    {
        $company = $request->user()?->company;
        if (! $company) {
            abort(404);
        }

        $plan = (string) ($company->pending_plan ?: $company->subscription?->plan ?: 'starter');
        $billingPeriod = (string) ($company->pending_billing_period ?: 'monthly');

        $priceId = (string) data_get(config('stripe.plans', []), "{$plan}.prices.{$billingPeriod}");
        if ($priceId === '') {
            return back()->withErrors(['plan' => 'El plan seleccionado no está configurado correctamente.']);
        }

        if (! $company->stripe_id) {
            $company->stripe_id = $this->stripeCheckoutService->createCustomer(
                $company->name,
                $company->owner_email,
                $company->owner_phone
            );
            $company->save();
        }

        $successUrl = url('/onboarding/success').'?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = route('register');

        $metadata = [
            'company_id' => (string) $company->id,
            'plan' => $plan,
            'billing_period' => $billingPeriod,
            'company_name' => $company->name,
            'admin_name' => $company->owner_name,
            'admin_email' => $company->owner_email,
        ];

        $session = $this->stripeCheckoutService->createCheckoutSession([
            'mode' => 'subscription',
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer' => $company->stripe_id,
            'metadata' => $metadata,
            'subscription_data' => [
                'metadata' => $metadata,
            ],
        ]);

        $company->update([
            'pending_plan' => $plan,
            'pending_billing_period' => $billingPeriod,
            'stripe_checkout_session_id' => $session['id'],
        ]);

        return redirect()->away($session['url']);
    }

    private function createTechnicianProfile(int $companyId, User $user): void
    {
        TechnicianProfile::query()->create([
            'company_id' => $companyId,
            'user_id' => $user->id,
            'employee_code' => 'USR-'.$user->id,
            'display_name' => $user->name,
            'specialties' => [],
            'status' => TechnicianStatus::AVAILABLE,
            'max_concurrent_orders' => 5,
            'hourly_cost' => 0,
            'is_assignable' => true,
        ]);
    }
}
