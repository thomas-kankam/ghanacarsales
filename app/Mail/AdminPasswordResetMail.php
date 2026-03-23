<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminPasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $email, public string $otp)
    {
    }

    public function build()
    {
        return $this->subject('Reset Your Admin Password - Ghana Car Sales')
            ->view('emails.admin-password-reset')
            ->with([
                'email' => $this->email,
                'otp' => $this->otp,
            ]);
    }
}

