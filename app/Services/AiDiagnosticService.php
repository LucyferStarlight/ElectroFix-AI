<?php

namespace App\Services;

use App\Contracts\AiDiagnosticProvider;
use App\DTOs\AiDiagnosticResult;
use App\Models\Company;
use App\Models\Order;
use App\Models\User;
use App\Services\Ai\ArisProvider;
use App\Services\Ai\LocalFallbackProvider;
use App\Services\Exceptions\AiProviderException;
use App\Services\Exceptions\AiQuotaExceededException;
use App\Services\Exceptions\AiUsageException;
use App\Services\Exceptions\ArisNotAvailableException;
use Illuminate\Support\Facades\Log;

class AiDiagnosticService
{
    private const DEFAULT_PLAN = 'starter';

    public function __construct(
        private readonly AiDiagnosticProvider $provider,
        private readonly LocalFallbackProvider $localFallbackProvider,
        private readonly ArisProvider $arisProvider,
        private readonly AiUsageService $aiUsageService,
        private readonly AiTokenEstimator $tokenEstimator,
        private readonly OrderDiagnosticService $orderDiagnosticService
    ) {
    }

    public function diagnose(Order $order, Company $company, User $actor, string $symptoms): AiDiagnosticResult
    {
        if ($order->ai_diagnosed_at) {
            throw new AiQuotaExceededException('already_diagnosed', 'Esta orden ya cuenta con un diagnóstico IA.');
        }

        $symptoms = trim($symptoms);
        if (mb_strlen($symptoms) > 600) {
            throw new AiQuotaExceededException('invalid_symptoms', 'Los síntomas no pueden exceder 600 caracteres.');
        }

        $order->loadMissing('equipment');
        $company->loadMissing('subscription.planModel');

        $plan = (string) ($company->subscription?->plan ?? self::DEFAULT_PLAN);
        $deviceInfo = $this->deviceInfo($order);
        $prompt = sprintf('Equipo: %s. Síntomas: %s', $deviceInfo, $symptoms);
        $promptChars = mb_strlen($prompt);
        $promptTokens = $this->tokenEstimator->estimateFromChars($promptChars);

        try {
            $this->aiUsageService->validateBeforeUsage($company, $plan, $promptTokens);
        } catch (AiUsageException $exception) {
            $this->aiUsageService->registerBlocked(
                $company,
                $order,
                $plan,
                $exception->status(),
                $exception->getMessage(),
                $promptChars
            );

            throw new AiQuotaExceededException($exception->status(), $exception->getMessage());
        }

        $provider = $this->resolveProvider($company);

        try {
            $result = $provider->diagnose($symptoms, $deviceInfo);
        } catch (ArisNotAvailableException $exception) {
            $this->aiUsageService->registerBlocked(
                $company,
                $order,
                $plan,
                'blocked_plan',
                $exception->getMessage(),
                $promptChars
            );

            throw new AiQuotaExceededException('blocked_plan', $exception->getMessage());
        } catch (AiProviderException $exception) {
            Log::channel('ai')->warning('AI provider failed, using local fallback', [
                'company_id' => $company->id,
                'order_id' => $order->id,
                'status' => $exception->status(),
                'message' => $exception->getMessage(),
            ]);

            $result = $this->localFallbackProvider->diagnose($symptoms, $deviceInfo);
        } catch (\Throwable $exception) {
            Log::channel('ai')->error('Unexpected AI provider failure, using local fallback', [
                'company_id' => $company->id,
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]);

            $result = $this->localFallbackProvider->diagnose($symptoms, $deviceInfo);
        }

        $analysis = $result->payload;
        $responseChars = mb_strlen((string) json_encode($analysis, JSON_UNESCAPED_UNICODE));
        $responseTokens = $this->tokenEstimator->estimateFromChars($responseChars);
        $totalTokens = $promptTokens + $responseTokens;

        try {
            $this->aiUsageService->commitSuccessfulUsage(
                $company,
                $order,
                $plan,
                $promptChars,
                $responseChars,
                $totalTokens
            );
        } catch (AiUsageException $exception) {
            $this->aiUsageService->registerBlocked(
                $company,
                $order,
                $plan,
                $exception->status(),
                $exception->getMessage(),
                $promptChars,
                $responseChars
            );

            throw new AiQuotaExceededException($exception->status(), $exception->getMessage());
        }

        $this->orderDiagnosticService->createFromAi(
            $order->loadMissing('equipment'),
            $actor,
            $analysis,
            $promptTokens,
            $responseTokens,
            $symptoms
        );

        $order->update([
            'symptoms' => $symptoms,
            'ai_potential_causes' => $analysis['possible_causes'] ?? [],
            'ai_estimated_time' => $analysis['estimated_time'] ?? null,
            'ai_suggested_parts' => $analysis['suggested_parts'] ?? [],
            'ai_technical_advice' => $analysis['technical_advice'] ?? null,
            'ai_diagnosed_at' => now(),
            'ai_tokens_used' => $totalTokens,
            'ai_provider' => $analysis['provider'] ?? $result->provider,
            'ai_model' => $analysis['model'] ?? null,
            'ai_requires_parts_replacement' => (bool) ($analysis['requires_parts_replacement'] ?? false),
            'ai_cost_repair_labor' => (float) ($analysis['cost_suggestion']['repair_labor_cost'] ?? 0),
            'ai_cost_replacement_parts' => (float) ($analysis['cost_suggestion']['replacement_parts_cost'] ?? 0),
            'ai_cost_replacement_total' => (float) ($analysis['cost_suggestion']['replacement_total_cost'] ?? 0),
        ]);

        return $result;
    }

    private function resolveProvider(Company $company): AiDiagnosticProvider
    {
        $override = $company->subscription?->planModel?->ai_provider_override;
        if ($override === 'aris') {
            return $this->arisProvider;
        }

        return $this->provider;
    }

    private function deviceInfo(Order $order): string
    {
        $type = (string) ($order->equipment?->type ?? '');
        $brand = (string) ($order->equipment?->brand ?? '');
        $model = (string) ($order->equipment?->model ?? '');

        return trim(sprintf('%s %s %s', $brand, $type, $model));
    }
}
