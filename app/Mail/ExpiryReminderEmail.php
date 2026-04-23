<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Content;

class ExpiryReminderEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $email, public string $message)
    {
        $this->email = $email;
        $this->message = $message;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Car Listing Expiring Soon - OmniCarsGH',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.expiry-reminder-email',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
