<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly string $headline,
        public readonly string $message,
    ) {
    }

    public function build(): self
    {
        return $this->subject('Actualización de tu orden en ElectroFix-AI')
            ->view('emails.order-status-updated', [
                'order' => $this->order,
                'headline' => $this->headline,
                'messageBody' => $this->message,
                'supportEmail' => config('support.email'),
                'supportWhatsapp' => config('support.whatsapp_url'),
            ]);
    }
}
