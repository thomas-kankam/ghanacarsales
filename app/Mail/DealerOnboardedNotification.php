<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DealerOnboardedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $dealerName,
        public string $body
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Dealer account onboarded successfully - OmniCarsGH',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.dealer-onboarded-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
