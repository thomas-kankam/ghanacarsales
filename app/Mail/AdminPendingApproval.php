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
        public string $body
    ) {
    }

    public function build()
    {
        return $this->subject('New payment pending approval - Ghana Car Sales')
            ->view('emails.admin-pending-approval')
            ->with([
                'adminEmail' => $this->adminEmail,
                'body' => $this->body,
            ]);
    }
}

