<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminPendingApproval extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $adminEmail,
        public string $message
    ) {
    }

    public function build()
    {
        return $this->subject('New car listing pending approval - OmniCarsGH')
            ->view('emails.admin-pending-approval')
            ->with([
                'adminEmail' => $this->adminEmail,
                'message' => $this->message,
            ]);
    }
}

