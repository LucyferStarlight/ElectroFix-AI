<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Credenciales inválidas.'])
                ->onlyInput('email');
        }

        if (! $request->user()->is_active) {
            Auth::logout();

            return back()
                ->withErrors(['email' => 'Tu cuenta está desactivada.'])
                ->onlyInput('email');
        }

        $user = $request->user();
        if ($user->role !== 'developer' && $user->role !== 'admin') {
            $status = $user->company?->subscription?->status;
            if (! in_array($status, ['active', 'trialing'], true)) {
                Auth::logout();

                $message = $user->role === 'worker'
                    ? 'El acceso ha sido suspendido por un tema administrativo. Por favor, informa a tu empleador.'
                    : 'Tu suscripción no está vigente. Actualiza tu pago para recuperar el acceso.';

                return back()
                    ->withErrors(['email' => $message])
                    ->onlyInput('email');
            }
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }
}
