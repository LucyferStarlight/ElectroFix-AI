<?php

namespace App\Services\Exceptions;

use DomainException;

class OrderApprovalException extends DomainException
{
    public static function approvalContextRequired(): self
    {
        return new self('La orden requiere contexto de aprobación. Usa approve() o el flujo de aprobación correspondiente.');
    }

    public static function approvalRequiredForRepair(): self
    {
        return new self('La orden debe estar aprobada antes de iniciar la reparación.');
    }

    public static function invalidApprovalActor(?string $actor): self
    {
        return new self(sprintf(
            'El aprobador [%s] no es válido. Valores permitidos: customer, system.',
            (string) $actor
        ));
    }

    public static function invalidApprovalChannel(string $channel): self
    {
        return new self(sprintf(
            'El canal de aprobación [%s] no es válido.',
            $channel
        ));
    }

    public static function rejectionReasonRequired(): self
    {
        return new self('Debes indicar un motivo de rechazo para la orden.');
    }
}
