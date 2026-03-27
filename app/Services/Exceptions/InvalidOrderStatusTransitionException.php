<?php

namespace App\Services\Exceptions;

use App\Enums\OrderStatus;
use App\Models\Order;
use DomainException;

class InvalidOrderStatusTransitionException extends DomainException
{
    public function __construct(
        public readonly ?OrderStatus $from,
        public readonly OrderStatus $to,
        public readonly ?Order $order = null,
        public readonly array $allowedTransitions = []
    ) {
        parent::__construct(self::buildMessage($from, $to, $allowedTransitions));
    }

    private static function buildMessage(?OrderStatus $from, OrderStatus $to, array $allowedTransitions): string
    {
        if ($from === null) {
            return sprintf('No se pudo inicializar la orden en estado "%s".', $to->label());
        }

        if ($allowedTransitions === []) {
            return sprintf(
                'La orden no puede cambiar de "%s" a "%s".',
                $from->label(),
                $to->label()
            );
        }

        return sprintf(
            'La orden no puede cambiar de "%s" a "%s". Estados permitidos: %s.',
            $from->label(),
            $to->label(),
            implode(', ', array_map(
                static fn (OrderStatus $status): string => $status->label(),
                $allowedTransitions
            ))
        );
    }
}
