<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
class DealerCarNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $dealerEmail,
        public string $subjectLine,
        public string $body
    ) {
    }

    public function build()
    {
        return $this->subject($this->subjectLine)
            ->view('emails.dealer-car-notification');
    }
}
