<?php

namespace App\Providers;

use App\Application\AI\Contracts\AiProviderInterface;
use App\Infrastructure\AI\GeminiProvider;
use App\Models\Company;
use Illuminate\Support\ServiceProvider;
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
    }
}
