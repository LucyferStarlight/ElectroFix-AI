<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\User;
use App\Services\Exceptions\AiUsageException;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\DB;

class OrderCreationService
{
    private const DEFAULT_PLAN = 'starter';

    public function __construct(
        private readonly AiDiagnosticService $aiDiagnosticService,
        private readonly AiUsageService $aiUsageService,
        private readonly AiTokenEstimator $tokenEstimator,
        private readonly TechnicianAssignmentService $technicianAssignmentService,
        private readonly OrderDiagnosticService $orderDiagnosticService
    ) {
    }

    public function create(User $actor, array $payload): array
    {
        return DB::transaction(function () use ($actor, $payload): array {
            $customer = Customer::query()->findOrFail((int) $payload['customer_id']);
            $equipment = Equipment::query()->findOrFail((int) $payload['equipment_id']);

            if ($customer->company_id !== $equipment->company_id) {
                abort(422, 'Cliente y equipo no pertenecen a la misma empresa.');
            }

            if ($actor->role !== 'developer' && $customer->company_id !== $actor->company_id) {
                abort(403, 'No puedes crear órdenes fuera de tu empresa.');
            }

            $technician = $this->technicianAssignmentService
                ->resolveForOrderCreation($actor, $customer->company_id, $payload);

            $order = Order::query()->create([
                'company_id' => $customer->company_id,
                'customer_id' => $customer->id,
                'equipment_id' => $equipment->id,
                'technician_profile_id' => $technician->id,
                'technician' => $technician->display_name,
                'symptoms' => $payload['symptoms'] ?? null,
                'status' => $payload['status'] ?? OrderStatus::RECEIVED,
                'estimated_cost' => $payload['estimated_cost'] ?? 0,
            ]);

            $this->technicianAssignmentService->assign(
                $order,
                $technician,
                $actor,
                'Asignación inicial al crear orden'
            );

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
            $symptoms = (string) ($payload['symptoms'] ?? '');
            $promptChars = $this->promptChars($equipment, $symptoms);
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
                $symptoms,
                ['company_id' => $company->id, 'order_id' => $order->id]
            );

            if (($analysis['success'] ?? true) === false) {
                $message = (string) ($analysis['error_message'] ?? 'No fue posible completar el diagnóstico IA en este momento.');
                $this->aiUsageService->registerBlocked(
                    $company,
                    $order,
                    $plan,
                    'error',
                    $message,
                    $promptChars
                );

                return [
                    'order' => $order,
                    'ai_applied' => false,
                    'ai_warning' => $message,
                ];
            }

            $responseChars = mb_strlen((string) json_encode($analysis, JSON_UNESCAPED_UNICODE));
            $promptTokens = $this->tokenEstimator->estimateFromChars($promptChars);
            $completionTokens = $this->tokenEstimator->estimateFromChars($responseChars);
            $totalTokens = $promptTokens + $completionTokens;

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

                return [
                    'order' => $order,
                    'ai_applied' => false,
                    'ai_warning' => $exception->getMessage(),
                ];
            }

            $this->orderDiagnosticService->createFromAi(
                $order->loadMissing('equipment'),
                $actor,
                $analysis,
                $promptTokens,
                $completionTokens,
                $symptoms
            );

            $order->update([
                'ai_potential_causes' => $analysis['possible_causes'] ?? [],
                'ai_estimated_time' => $analysis['estimated_time'] ?? null,
                'ai_suggested_parts' => $analysis['suggested_parts'] ?? [],
                'ai_technical_advice' => $analysis['technical_advice'] ?? null,
                'ai_diagnosed_at' => now(),
                'ai_tokens_used' => $totalTokens,
                'ai_provider' => $analysis['provider'] ?? 'local_stub',
                'ai_model' => $analysis['model'] ?? 'heuristic-v2',
                'ai_requires_parts_replacement' => (bool) ($analysis['requires_parts_replacement'] ?? false),
                'ai_cost_repair_labor' => (float) ($analysis['cost_suggestion']['repair_labor_cost'] ?? 0),
                'ai_cost_replacement_parts' => (float) ($analysis['cost_suggestion']['replacement_parts_cost'] ?? 0),
                'ai_cost_replacement_total' => (float) ($analysis['cost_suggestion']['replacement_total_cost'] ?? 0),
            ]);

            return [
                'order' => $order->fresh(['latestDiagnostic', 'technicianProfile']),
                'ai_applied' => true,
                'ai_warning' => null,
            ];
        });
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
}
