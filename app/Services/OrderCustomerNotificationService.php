<?php

namespace App\Services;

use App\Mail\OrderStatusUpdatedMail;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderCustomerNotificationService
{
    public function sendCreated(Order $order): void
    {
        $this->send(
            $order,
            'Recibimos tu orden de servicio',
            sprintf(
                'Tu orden #%s fue registrada correctamente y ya se encuentra en proceso.',
                str_pad((string) $order->id, 6, '0', STR_PAD_LEFT)
            )
        );
    }

    public function sendStatusChanged(Order $order, string $oldStatus, string $newStatus): void
    {
        $this->send(
            $order,
            'Tu orden cambió de estado',
            sprintf(
                'La orden #%s cambió de "%s" a "%s".',
                str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
                \App\Support\OrderStatus::label($oldStatus),
                \App\Support\OrderStatus::label($newStatus)
            )
        );
    }

    public function sendDelivered(Order $order): void
    {
        $this->send(
            $order,
            'Tu equipo está listo para entrega',
            sprintf(
                'La orden #%s ya fue marcada como entregada. Si tienes dudas, contáctanos por este medio o por nuestros canales de soporte.',
                str_pad((string) $order->id, 6, '0', STR_PAD_LEFT)
            )
        );
    }

    private function send(Order $order, string $headline, string $message): void
    {
        $order->loadMissing(['customer', 'equipment', 'company']);

        $email = trim((string) $order->customer?->email);
        if ($email === '') {
            return;
        }

        try {
            Mail::to($email)->send(new OrderStatusUpdatedMail($order, $headline, $message));
        } catch (\Throwable $exception) {
            Log::warning('Order customer notification failed', [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'email' => $email,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
