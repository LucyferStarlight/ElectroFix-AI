<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\User;
use App\Services\Exceptions\AiQuotaExceededException;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\DB;

class OrderCreationService
{
    public function __construct(
        private readonly AiDiagnosticService $aiDiagnosticService,
        private readonly TechnicianAssignmentService $technicianAssignmentService
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

            $company = $order->company()->with('subscription.planModel')->firstOrFail();
            $symptoms = (string) ($payload['symptoms'] ?? '');
            try {
                $this->aiDiagnosticService->diagnose($order, $company, $actor, $symptoms);
            } catch (AiQuotaExceededException $exception) {
                return [
                    'order' => $order,
                    'ai_applied' => false,
                    'ai_warning' => $exception->getMessage(),
                ];
            }

            return [
                'order' => $order->fresh(['latestDiagnostic', 'technicianProfile']),
                'ai_applied' => true,
                'ai_warning' => null,
            ];
        });
    }
}
