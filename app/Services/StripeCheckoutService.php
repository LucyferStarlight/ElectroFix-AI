<?php

namespace App\Services;

use Stripe\StripeClient;

class StripeCheckoutService
{
    public function __construct(private readonly StripeClient $stripeClient)
    {
    }

    public function createCustomer(string $name, string $email, ?string $phone = null): string
    {
        $customer = $this->stripeClient->customers->create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
        ]);

        return (string) $customer->id;
    }

    public function createCheckoutSession(array $payload): array
    {
        $session = $this->stripeClient->checkout->sessions->create($payload);

        return [
            'id' => (string) $session->id,
            'url' => (string) $session->url,
        ];
    }
}
