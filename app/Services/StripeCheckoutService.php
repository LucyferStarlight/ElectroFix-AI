<?php

namespace App\Services;

use App\Services\Exceptions\StripeCheckoutException;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripeCheckoutService
{
    public function __construct(private readonly StripeClient $stripeClient)
    {
    }

    public function createCustomer(string $name, string $email, ?string $phone = null): string
    {
        try {
            $customer = $this->stripeClient->customers->create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
            ]);
        } catch (ApiErrorException $exception) {
            throw new StripeCheckoutException(
                'En este momento no fue posible preparar el pago. Verifica la configuracion de facturacion o intenta nuevamente en unos minutos.',
                previous: $exception
            );
        }

        return (string) $customer->id;
    }

    public function createCheckoutSession(array $payload): array
    {
        try {
            $session = $this->stripeClient->checkout->sessions->create($payload);
        } catch (ApiErrorException $exception) {
            $message = 'En este momento no fue posible iniciar el pago. Intenta nuevamente en unos minutos o contacta a soporte.';

            if (str_contains(mb_strtolower($exception->getMessage()), 'no such price')) {
                $message = 'En este momento el plan seleccionado no esta disponible para pago. Intenta nuevamente en unos minutos o contacta a soporte.';
            }

            throw new StripeCheckoutException($message, previous: $exception);
        }

        return [
            'id' => (string) $session->id,
            'url' => (string) $session->url,
        ];
    }
}
