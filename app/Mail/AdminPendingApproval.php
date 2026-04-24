<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminPendingApproval extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $adminEmail  Recipient address (shown in greeting).
     * @param  string  $intro  Body text — must not be named $message; that name is reserved by Laravel mail views.
     */
    public function __construct(
        public string $adminEmail,
        public string $intro
    ) {
    }

    public function build()
    {
        return $this->subject('New car listing pending approval - OmniCarsGH')
            ->view('emails.admin-pending-approval', [
                'adminEmail' => $this->adminEmail,
                'intro' => $this->intro,
            ]);
    }
}

