<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailPasswordChange extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $email,
        public string $password
    ) {}

    public function build()
    {
        return $this->subject('Your Admin Account Credentials - Ghana Car Sales')
            ->view('emails.admin-credentials')
            ->with([
                'email' => $this->email,
                'password' => $this->password,
            ]);
    }
}
