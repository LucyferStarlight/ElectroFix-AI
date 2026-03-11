<?php

namespace App\Http\Controllers;

use App\Mail\SupportRequestMail;
use App\Models\SupportRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SupportController extends Controller
{
    public function show(Request $request)
    {
        return view('support', [
            'currentPage' => 'support',
            'user' => $request->user(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'email' => ['required', 'email', 'max:180'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        $support = SupportRequest::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'message' => $data['message'],
            'user_id' => $user?->id,
            'company_id' => $user?->company_id,
            'source_url' => $request->headers->get('referer'),
            'user_agent' => $request->userAgent(),
            'status' => 'open',
        ]);

        try {
            Mail::to((string) config('support.email'))
                ->send(new SupportRequestMail($support));
        } catch (\Throwable $e) {
            Log::warning('Support email failed', [
                'error' => $e->getMessage(),
                'support_request_id' => $support->id,
            ]);
        }

        return back()->with('success', 'Tu mensaje fue enviado a soporte. Te responderemos por correo.');
    }
}
