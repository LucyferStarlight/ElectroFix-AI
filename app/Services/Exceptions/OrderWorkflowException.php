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
}
