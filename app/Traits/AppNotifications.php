<?php
namespace App\Traits;

use App\Jobs\SendEmailJob;
use App\Models\Actor;
use App\Models\OtpVerification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

trait AppNotifications
{
    use Helpers;

    /**
     * Send SMS via Mnotify.
     *
     * @return array|false Returns provider response data on success, false on failure.
     */
    protected static function sendSms(string $phone_number, string $msg): array|false
    {
        $apiKey = (string) config('services.mnotify.api_key', '');
        if ($apiKey === '') {
            Log::channel('sent_sms')->warning('Mnotify SMS: missing api key');
            return false;
        }

        $sender = trim((string) config('services.mnotify.from', 'OmniCars'));
        if (mb_strlen($sender) > 11) {
            $sender = mb_substr($sender, 0, 11);
        }

        $recipients = [$phone_number];
        $payload = [
            'recipient'     => $recipients,
            'sender'        => $sender,
            'message'       => $msg,
            'is_schedule'   => false,
            'schedule_date' => '',
            // 'sms_type'      => 'otp',
        ];

        try {
            $response = Http::acceptJson()
                ->timeout(15)
                ->post("https://api.mnotify.com/api/sms/quick?key={$apiKey}", $payload);

            $json = $response->json();

            Log::channel('sent_sms')->info('SMS API Response', [
                'recipients' => $recipients,
                'status'     => $response->status(),
                'response'   => $json,
            ]);

            if ($response->successful() && ($json['status'] ?? null) === 'success') {
                return $json['data'] ?? $json['summary'] ?? $json;
            }

            return false;
        } catch (\Throwable $e) {
            Log::channel('sent_sms')->error('Mnotify SMS: request failed', [
                'recipients' => $recipients,
                'message'    => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function generateOtp(string $type, string $actor_id, string $channel, string $guard): string
    {
        // Generate 5-digit OTP with leading zeros preserved
        $token     = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(10);

        // Delete any expired OTPs for this actor
        OtpVerification::where("actor_id", $actor_id)
            ->where("guard", $guard)
            ->where("channel", $channel)
            ->where("expires_at", "<=", now())
            ->delete();

        // Check for existing non-expired OTP
        $existingOtp = OtpVerification::where("actor_id", $actor_id)
            ->where("guard", $guard)
            ->where("channel", $channel)
            ->where("expires_at", ">", now())
            ->first();

        if ($existingOtp) {
            // Update existing non-expired OTP
            $existingOtp->token       = $token;
            $existingOtp->expires_at  = $expiresAt;
            $existingOtp->is_verified = false;
            $existingOtp->save();
        } else {
            // Create new OTP record
            OtpVerification::create([
                "token"       => $token,
                "actor_id"    => $actor_id,
                "guard"       => $guard,
                "type"        => $type,
                "channel"     => $channel,
                "expires_at"  => $expiresAt,
                "is_verified" => false,
            ]);
        }

        return $token;
    }

    protected static function sendEmail(string $email, array $parameters, string $email_class): void
    {
        try {
            Mail::to($email)->send(new $email_class(...$parameters));
            // SendEmailJob::dispatch($email, $parameters, $email_class);

        } catch (\Exception $e) {
            Log::error("Failed to send email to {$email}: " . $e->getMessage());
        }
    }

    public static function verifyOtp(string $identifier, string $token, string $guard): array
    {
        $otp = OtpVerification::where('actor_id', $identifier)
            ->where('token', $token)
            ->where('guard', $guard)
            ->first();

        // Check if OTP exists
        if (! $otp) {
            return [
                'success' => false,
                'reason'  => 'not_found',
                'message' => 'OTP not found',
            ];
        }

        // Check if already verified
        if ($otp->is_verified) {
            return [
                'success' => false,
                'reason'  => 'already_verified',
                'message' => 'OTP has already been verified',
            ];
        }

        // Check if expired
        if ($otp->expires_at <= Carbon::now()) {
            return [
                'success' => false,
                'reason'  => 'expired',
                'message' => 'OTP has expired',
            ];
        }

        // All checks passed, verify the OTP
        $otp->update([
            'is_verified' => true,
            'verified_at' => Carbon::now(),
        ]);

        return [
            'success' => true,
            'reason'  => 'verified',
            'message' => 'OTP verified successfully',
        ];
    }
}
