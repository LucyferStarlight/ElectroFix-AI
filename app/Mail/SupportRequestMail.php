<?php

namespace App\Mail;

use App\Models\SupportRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupportRequestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly SupportRequest $supportRequest)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Soporte ElectroFix-AI')
            ->view('emails.support-request');
    }
}
