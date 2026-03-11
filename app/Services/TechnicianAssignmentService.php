<?php

namespace App\Services;

use App\Models\EquipmentEvent;
use App\Models\Order;
use App\Models\OrderAssignmentLog;
use App\Models\TechnicianProfile;
use App\Models\User;
use App\Support\TechnicianStatus;

class TechnicianAssignmentService
{
    public function resolveForOrderCreation(User $actor, int $companyId, array $payload): TechnicianProfile
    {
        if ($actor->role === 'worker') {
            $profile = $actor->technicianProfile;

            if (! $profile || $profile->company_id !== $companyId) {
                abort(422, 'Tu perfil técnico no está configurado para recibir órdenes.');
            }

            return $this->assertAssignable($profile);
        }

        if ($actor->role === 'admin') {
            $profileId = (int) ($payload['technician_profile_id'] ?? 0);
            $profile = TechnicianProfile::query()
                ->where('id', $profileId)
                ->where('company_id', $companyId)
                ->first();

            if (! $profile) {
                abort(422, 'Debes seleccionar un técnico válido de tu empresa.');
            }

            return $this->assertAssignable($profile);
        }

        $profileId = (int) ($payload['technician_profile_id'] ?? 0);
        if ($profileId > 0) {
            $profile = TechnicianProfile::query()
                ->where('id', $profileId)
                ->where('company_id', $companyId)
                ->first();

            if (! $profile) {
                abort(422, 'El técnico seleccionado no pertenece a la empresa.');
            }

            return $this->assertAssignable($profile);
        }

        if ($actor->technicianProfile && $actor->technicianProfile->company_id === $companyId) {
            return $this->assertAssignable($actor->technicianProfile);
        }

        abort(422, 'No se pudo resolver un técnico asignable para la orden.');
    }

    public function assign(Order $order, TechnicianProfile $toTechnician, User $changedBy, ?string $reason = null): void
    {
        $fromTechnicianId = $order->technician_profile_id;

        $order->update([
            'technician_profile_id' => $toTechnician->id,
            'technician' => $toTechnician->display_name,
        ]);

        OrderAssignmentLog::query()->create([
            'company_id' => $order->company_id,
            'order_id' => $order->id,
            'from_technician_profile_id' => $fromTechnicianId,
            'to_technician_profile_id' => $toTechnician->id,
            'changed_by_user_id' => $changedBy->id,
            'reason' => $reason,
        ]);

        EquipmentEvent::query()->create([
            'company_id' => $order->company_id,
            'equipment_id' => $order->equipment_id,
            'order_id' => $order->id,
            'created_by_user_id' => $changedBy->id,
            'event_type' => 'order.assignment',
            'title' => 'Asignación de técnico',
            'description' => sprintf('Técnico asignado: %s', $toTechnician->display_name),
            'payload' => [
                'from_technician_profile_id' => $fromTechnicianId,
                'to_technician_profile_id' => $toTechnician->id,
            ],
        ]);

        if ($toTechnician->status === TechnicianStatus::AVAILABLE) {
            $toTechnician->update(['status' => TechnicianStatus::ASSIGNED]);
        }
    }

    private function assertAssignable(TechnicianProfile $profile): TechnicianProfile
    {
        if (! $profile->is_assignable || $profile->status === TechnicianStatus::INACTIVE) {
            abort(422, 'El técnico seleccionado no está disponible para recibir órdenes.');
        }

        return $profile;
    }
}

