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

    public static function stripeAmountMismatch(float $received, float $expected): self
    {
        return new self(sprintf(
            'Monto de Stripe inválido para la orden. Recibido: %.2f, esperado: %.2f.',
            $received,
            $expected
        ));
    }

    public static function stripePaymentAlreadySettled(): self
    {
        return new self('La orden ya no tiene saldo pendiente para aplicar este pago de Stripe.');
    }

    public static function invalidPaymentContext(string $field): self
    {
        return new self(sprintf('Falta información obligatoria para registrar el pago: %s.', $field));
    }

    public static function inconsistentOrderRelations(): self
    {
        return new self('La orden tiene relaciones inconsistentes y no puede registrar pagos.');
    }
}
