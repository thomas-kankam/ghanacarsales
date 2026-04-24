<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Content;

class DealerCarNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $dealerEmail,
        public string $subjectLine,
        public string $body
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New car listing notification - OmniCarsGH',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.dealer-car-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }

    // public function build()
    // {
    //     return $this->subject($this->subjectLine)
    //         ->view('emails.dealer-car-notification', [
    //             'dealerEmail' => $this->dealerEmail,
    //             'subjectLine' => $this->subjectLine,
    //             'body' => $this->body,
    //         ]);
    // }
}
