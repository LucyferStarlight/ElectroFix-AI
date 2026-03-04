<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterCompanyRequest;
use App\Models\RegistrationConfirmation;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;

class RegistrationController extends Controller
{
    public function __construct(private readonly RegistrationService $registrationService)
    {
    }

    public function showForm()
    {
        return view('auth.register');
    }

    public function store(RegisterCompanyRequest $request): RedirectResponse
    {
        $confirmation = $this->registrationService->register($request->validated());

        return redirect()->route('register.confirmation', $confirmation->access_token);
    }

    public function confirmation(string $token)
    {
        $confirmation = RegistrationConfirmation::query()
            ->where('access_token', $token)
            ->firstOrFail();

        if ($confirmation->expires_at->isPast()) {
            abort(410, 'La confirmación expiró.');
        }

        if ($confirmation->consumed_at) {
            return view('auth.register-confirmation-expired');
        }

        $snapshot = $confirmation->payload_snapshot;

        $confirmation->update([
            'consumed_at' => now(),
        ]);

        return view('auth.register-confirmation', [
            'snapshot' => $snapshot,
            'loginEmail' => data_get($snapshot, 'admin.email'),
        ]);
    }
}
