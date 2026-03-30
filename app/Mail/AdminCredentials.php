<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminCredentials extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $email, public string $password)
    {
        $this->email = $email;
        $this->password = $password;
    }

    public function build()
    {
        return $this->subject('Welcome to Omni Cars Ghana Admin Panel')
            ->view('emails.admin-credentials')
            ->with([
                'email' => $this->email,
                'password' => $this->password,
                'login_url' => config('app.url') . '/admin/login',
            ]);
    }
}
