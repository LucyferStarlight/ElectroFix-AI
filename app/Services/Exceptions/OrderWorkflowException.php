<?php

namespace App\Services\Exceptions;

use DomainException;

class OrderWorkflowException extends DomainException
{
    public static function cannotDiagnose(): self
    {
        return new self('La orden no puede diagnosticarse en su estado actual.');
    }

    public static function cannotQuote(): self
    {
        return new self('La orden no puede cotizarse en su estado actual.');
    }

    public static function cannotApprove(): self
    {
        return new self('La orden no puede aprobarse sin una cotización válida y activa.');
    }

    public static function cannotRepair(): self
    {
        return new self('La orden no puede repararse si no fue aprobada o si ya salió del flujo de reparación.');
    }

    public static function cannotDeliver(): self
    {
        return new self('La orden no puede entregarse hasta que la reparación esté cerrada y completada.');
    }

    public static function cannotCloseUntilPaid(): self
    {
        return new self('La orden no puede cerrarse mientras mantenga saldo pendiente.');
    }

    public static function orderNotFoundForTransition(): self
    {
        return new self('La orden no existe o ya no está disponible para transición de estado.');
    }

    public static function orderRelationsIncomplete(): self
    {
        return new self('La orden no tiene relaciones completas de cliente/equipo para una operación crítica.');
    }

    public static function orderCustomerCompanyMismatch(): self
    {
        return new self('La orden tiene un cliente que no pertenece a la misma empresa.');
    }

    public static function orderEquipmentCompanyMismatch(): self
    {
        return new self('La orden tiene un equipo que no pertenece a la misma empresa.');
    }

    public static function orderEquipmentCustomerMismatch(): self
    {
        return new self('La orden tiene un equipo que no coincide con el cliente asignado.');
    }

    public static function invalidTransitionTarget(): self
    {
        return new self('No se definió un estado de destino válido para la transición.');
    }
}
