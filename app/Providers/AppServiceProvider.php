<?php

namespace App\Providers;

use App\Application\AI\Contracts\AiProviderInterface;
use App\Infrastructure\AI\GeminiProvider;
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
        $this->app->bind(AiProviderInterface::class, GeminiProvider::class);
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
