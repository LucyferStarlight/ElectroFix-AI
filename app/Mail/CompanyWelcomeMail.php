<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CompanyWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Company $company,
        public readonly string $plan
    ) {
    }

    public function build(): self
    {
        return $this->subject('Bienvenido a ElectroFix-AI')
            ->view('emails.company-welcome', [
                'company' => $this->company,
                'plan' => $this->plan,
                'supportEmail' => config('support.email'),
                'supportWhatsapp' => config('support.whatsapp_url'),
            ]);
    }
}
