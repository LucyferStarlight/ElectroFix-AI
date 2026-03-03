<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\User;
use App\Services\Exceptions\AiUsageException;
use App\Support\OrderStatus;

class OrderCreationService
{
    private const DEFAULT_PLAN = 'starter';

    public function __construct(
        private readonly AiDiagnosticService $aiDiagnosticService,
        private readonly AiUsageService $aiUsageService,
        private readonly AiTokenEstimator $tokenEstimator
    ) {
    }

    public function create(User $actor, array $payload): array
    {
        $customer = Customer::query()->findOrFail((int) $payload['customer_id']);
        $equipment = Equipment::query()->findOrFail((int) $payload['equipment_id']);

        if ($customer->company_id !== $equipment->company_id) {
            abort(422, 'Cliente y equipo no pertenecen a la misma empresa.');
        }

        if ($actor->role !== 'developer' && $customer->company_id !== $actor->company_id) {
            abort(403, 'No puedes crear órdenes fuera de tu empresa.');
        }

        $order = Order::query()->create([
            'company_id' => $customer->company_id,
            'customer_id' => $customer->id,
            'equipment_id' => $equipment->id,
            'technician' => $this->resolveTechnician($actor, $customer->company_id, $payload),
            'symptoms' => $payload['symptoms'] ?? null,
            'status' => $payload['status'] ?? OrderStatus::RECEIVED,
            'estimated_cost' => $payload['estimated_cost'] ?? 0,
        ]);

        if (empty($payload['request_ai_diagnosis'])) {
            return [
                'order' => $order,
                'ai_applied' => false,
                'ai_warning' => null,
            ];
        }

        if ($order->ai_diagnosed_at) {
            return [
                'order' => $order,
                'ai_applied' => false,
                'ai_warning' => 'Esta orden ya utilizó su diagnóstico IA disponible.',
            ];
        }

        $company = $order->company()->with('subscription')->firstOrFail();
        $plan = (string) ($company->subscription?->plan ?? self::DEFAULT_PLAN);
        $promptChars = $this->promptChars($equipment, (string) ($payload['symptoms'] ?? ''));
        $projectedPromptTokens = $this->tokenEstimator->estimateFromChars($promptChars);

        try {
            $this->aiUsageService->validateBeforeUsage($company, $plan, $projectedPromptTokens);
        } catch (AiUsageException $exception) {
            $this->aiUsageService->registerBlocked(
                $company,
                $order,
                $plan,
                $exception->status(),
                $exception->getMessage(),
                $promptChars
            );

            return [
                'order' => $order,
                'ai_applied' => false,
                'ai_warning' => $exception->getMessage(),
            ];
        }

        $analysis = $this->aiDiagnosticService->analyze(
            $equipment->type,
            $equipment->brand,
            $equipment->model,
            (string) ($payload['symptoms'] ?? '')
        );

        $responseChars = mb_strlen((string) json_encode($analysis, JSON_UNESCAPED_UNICODE));
        $totalTokens = $this->tokenEstimator->estimateFromChars($promptChars)
            + $this->tokenEstimator->estimateFromChars($responseChars);

        try {
            $this->aiUsageService->validateAfterUsage($company, $plan, $totalTokens);
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

            return [
                'order' => $order,
                'ai_applied' => false,
                'ai_warning' => $exception->getMessage(),
            ];
        }

        $order->update([
            'ai_potential_causes' => $analysis['potential_causes'] ?? [],
            'ai_estimated_time' => $analysis['estimated_time'] ?? null,
            'ai_suggested_parts' => $analysis['suggested_parts'] ?? [],
            'ai_technical_advice' => $analysis['technical_advice'] ?? null,
            'ai_diagnosed_at' => now(),
            'ai_tokens_used' => $totalTokens,
            'ai_provider' => 'local_stub',
            'ai_model' => 'heuristic-v1',
            'ai_requires_parts_replacement' => (bool) ($analysis['requires_parts_replacement'] ?? false),
            'ai_cost_repair_labor' => (float) ($analysis['cost_suggestion']['repair_labor_cost'] ?? 0),
            'ai_cost_replacement_parts' => (float) ($analysis['cost_suggestion']['replacement_parts_cost'] ?? 0),
            'ai_cost_replacement_total' => (float) ($analysis['cost_suggestion']['replacement_total_cost'] ?? 0),
        ]);

        $this->aiUsageService->registerSuccess($company, $order, $plan, $promptChars, $responseChars);

        return [
            'order' => $order->fresh(),
            'ai_applied' => true,
            'ai_warning' => null,
        ];
    }

    private function promptChars(Equipment $equipment, string $symptoms): int
    {
        $prompt = sprintf(
            'Equipo: %s %s %s. Síntomas: %s',
            $equipment->type,
            $equipment->brand,
            $equipment->model ?? '',
            $symptoms
        );

        return mb_strlen($prompt);
    }

    private function resolveTechnician(User $actor, int $companyId, array $payload): string
    {
        if ($actor->role === 'worker') {
            return $actor->name;
        }

        if ($actor->role === 'admin') {
            $technicianId = (int) ($payload['technician_user_id'] ?? 0);

            $technician = User::query()
                ->where('id', $technicianId)
                ->where('company_id', $companyId)
                ->whereIn('role', ['worker', 'admin'])
                ->where('is_active', true)
                ->first();

            if (! $technician) {
                abort(422, 'Debes seleccionar un técnico activo (worker o admin) de tu empresa.');
            }

            return $technician->name;
        }

        $fromField = trim((string) ($payload['technician'] ?? ''));
        if ($fromField !== '') {
            return $fromField;
        }

        return $actor->name;
    }
}
