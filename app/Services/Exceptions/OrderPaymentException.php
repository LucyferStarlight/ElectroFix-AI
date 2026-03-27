<?php

namespace App\Services\Exceptions;

use DomainException;

class OrderPaymentException extends DomainException
{
    public static function amountMustBePositive(): self
    {
        return new self('El monto del pago debe ser mayor a cero.');
    }

    public static function refundExceedsPaidAmount(): self
    {
        return new self('El monto del reembolso no puede superar el total pagado de la orden.');
    }

    public static function stripeOrderReferenceMissing(): self
    {
        return new self('El evento de Stripe no contiene una referencia válida a la orden.');
    }
}
