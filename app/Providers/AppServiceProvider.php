<?php

namespace App\Providers;

use App\Contracts\AiDiagnosticProvider;
use App\Services\Ai\ArisProvider;
use App\Services\Ai\GeminiProvider;
use App\Services\Ai\GroqProvider;
use App\Services\Ai\LocalFallbackProvider;
use App\Models\Company;
use App\Models\Order;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\Stripe\StripeClient::class, function (): \Stripe\StripeClient {
            return new \Stripe\StripeClient((string) config('services.stripe.secret'));
        });

        $this->app->bind(AiDiagnosticProvider::class, function (): AiDiagnosticProvider {
            $provider = (string) config('ai.provider', env('AI_PROVIDER', 'gemini'));
            $apiKey = (string) config('services.gemini.api_key');

            if ($provider === 'aris') {
                return app(ArisProvider::class);
            }

            if ($provider === 'local') {
                return app(LocalFallbackProvider::class);
            }

            if ($provider === 'groq') {
                return app(GroqProvider::class);
            }

            if ($apiKey === '') {
                return app(LocalFallbackProvider::class);
            }

            return app(GeminiProvider::class);
        });

        $this->app->bind(ArisProvider::class, function () {
            return new ArisProvider();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Cashier::useCustomerModel(Company::class);

        RateLimiter::for('api', function (Request $request): Limit {
            $key = $request->user()?->id ?: $request->ip();

            return Limit::perMinute(60)->by((string) $key);
        });

        RateLimiter::for('ai-diagnostics', function (Request $request): Limit {
            $companyId = $request->user()?->company_id;

            if (! $companyId) {
                $order = $request->route('order');
                if ($order instanceof Order) {
                    $companyId = $order->company_id;
                }
            }

            if (! $companyId) {
                $companyId = (int) $request->input('company_id');
            }

            $key = $companyId ? 'company:'.$companyId : 'company:guest';

            return Limit::perMinute(10)->by($key);
        });
    }
}
