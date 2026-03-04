<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;
    protected $parameters;
    protected $emailClass;

    /**
     * Create a new job instance.
     */
    public function __construct(string $email, array $parameters, string $emailClass)
    {
        $this->email      = $email;
        $this->parameters = $parameters;
        $this->emailClass = $emailClass;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Mail::to($this->email)->send(new $this->emailClass(...$this->parameters));
        } catch (\Throwable $e) {
            Log::error("Failed to send email to {$this->email}: " . $e->getMessage());
        }
    }
}
