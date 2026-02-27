<?php
namespace App\Traits;

use App\Models\Actor;
use App\Models\OtpVerification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

trait AppNotifications
{
    use Helpers;

    // protected static function sendSms(string $phone_number, string $msg, string $from): array
    // {
    //     $endpoint = "https://api.txtconnect.net/dev/api/sms/send";

    //     $payload = [
    //         "to"      => $phone_number,
    //         "from"    => $from,
    //         "unicode" => 0,
    //         "sms"     => $msg,
    //     ];

    //     try {
    //         $response = Http::withHeaders([
    //             "Authorization" => "Bearer 2p6iDItRUfCFxjVBXbm9cGQ5eAYln0NZPzEqsLKrJvWy8hgou3",
    //             'Accept'        => 'application/json',
    //             "Content-Type"  => "application/json",
    //         ])->post($endpoint, $payload);

    //         $responseData = $response->json();

    //         Log::channel("sent_sms")->info("SMS API Response", [
    //             'phone_number' => $phone_number,
    //             'response'     => $responseData,
    //             'status_code'  => $response->status(),
    //         ]);

    //         return [
    //             'success'     => $response->successful(),
    //             'data'        => $responseData,
    //             'status_code' => $response->status(),
    //         ];

    //     } catch (\Exception $e) {
    //         Log::channel("sent_sms")->error("SMS API Exception", [
    //             'phone_number' => $phone_number,
    //             'error'        => $e->getMessage(),
    //             'trace'        => $e->getTraceAsString(),
    //         ]);

    //         return [
    //             'success' => false,
    //             'error'   => $e->getMessage(),
    //             'data'    => null,
    //         ];
    //     }
    // }

    protected static function sendSms(string $phone_number, string $msg, string $from): bool
    {
        $endpoint = "https://api.txtconnect.net/dev/api/sms/send";

        $payload = [
            "to"      => $phone_number,
            "from"    => $from,
            "unicode" => 0,
            "sms"     => $msg,
        ];

        try {
            $response = Http::withHeaders([
                "Authorization" => "Bearer 2p6iDItRUfCFxjVBXbm9cGQ5eAYln0NZPzEqsLKrJvWy8hgou3",
                'Accept'        => 'application/json',
                "Content-Type"  => "application/json",
            ])->post($endpoint, $payload);

            Log::channel("sent_sms")->info("SMS API Response", [
                'phone_number' => $phone_number,
                'response'     => $response->json(),
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::channel("sent_sms")->error("SMS API Exception", [
                'phone_number' => $phone_number,
                'error'        => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    protected static function sendOtp(Actor $actor, string $type, string $msg, string $channel, string $guard): void
    {
        $otp = self::otpCode(type: $type, actor_id: $actor->id, channel: $channel, guard: $guard);
        $msg = $msg . " " . $otp;
        self::sendSms(phone_number: $actor->phone_number, msg: $msg, from: "DEALBOXX");
    }

    public function generateOtp(string $type, string $actor_id, string $channel, string $guard): int
    {
        // Generate 5-digit OTP
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

        return (int) $token;
    }

    protected static function sendEmail(string $email, string $email_class, array $parameters = []): void
    {
        try {
            Mail::to($email)->send(new $email_class(...$parameters));
        } catch (\Exception $e) {
            Log::error("Failed to send email to {$email}: " . $e->getMessage());
        }
    }

    public function verifyOtp(string $identifier, string $token, string $guard): array
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

    // protected static function sendEmail(string $email, array $parameters, string $email_class): void
    // {
    //     Mail::to($email)->send(new $email_class(...$parameters));
    // }

    // public static function sendActorResetPasswordNotification(?Actor $actor = null, string $guard)
    // {
    //     if ($actor) {
    //         // $bool      = Str::contains(request("emailOrPhone"), '.');
    //         $full_name = "";
    //         if (in_array($guard, ["admin", "user"])) {
    //             $otp = self::otpCode(
    //                 channel: "email",
    //                 type: "password_reset",
    //                 actor_id: $actor->id,
    //                 guard: $guard
    //             );

    //             date_default_timezone_set("UTC");
    //             $date = date("D, d M Y H:i:s") . " UTC";

    //             if ($guard == "admin" || $guard == "user") {
    //                 $full_name = $actor->full_name;
    //             }

    //             // if ($guard == "vendor") {
    //             //     $full_name = $actor->brand_name;
    //             // }

    //             self::sendEmail(
    //                 email: $actor->email,
    //                 email_class: "App\Mail\PasswordResetEmail",
    //                 parameters: [
    //                     $otp,
    //                     $full_name,
    //                     $date,
    //                 ],
    //             );

    //             return self::apiResponse(in_error: false, message: "Action Successful", reason: "Otp sent to email successfully", status_code: self::API_SUCCESS, data: $actor->toArray());
    //         }

    //         if (in_array($guard, ["affiliate", "vendor"])) {
    //             self::sendOtp(actor: $actor, type: "password_reset", channel: "phone", msg: "Your password verification code is", guard: $guard);
    //             return self::apiResponse(in_error: false, message: "Action Successful", reason: "Otp sent to phone number successfully", status_code: self::API_SUCCESS, data: $actor->toArray());
    //         }
    //     }

    //     return self::apiResponse(in_error: true, message: "Action Unsuccessful", reason: "User cannot be found", status_code: self::API_NOT_FOUND, data: []);
    // }

    // protected static function verifyActorAccount(string $otp, ?Actor $actor = null, string $guard)
    // {
    //     if ($actor) {
    //         $channel = self::verifyOtp(guard: $guard, otp: $otp, actor_id: $actor->id);

    //         if ($channel == "phone") {
    //             $actor->phone_number_verified_at = now();
    //             $actor->save();

    //             $actor = self::apiToken($actor);
    //             if ($guard == "vendor") {
    //                 $actor->subscription = [
    //                     "status"            => $actor->subscription?->status,
    //                     "start_date"        => $actor->subscription?->start_date,
    //                     "end_date"          => $actor->subscription?->end_date,
    //                     "subscription_plan" => $actor->subscription?->subscriptionPlan,
    //                 ];

    //                 $actor->role        = null;
    //                 $actor->permissions = [];
    //             }

    //             return self::apiResponse(in_error: false, message: "Action Successful", reason: "Phone number verified successfully", status_code: self::API_SUCCESS, data: $actor->toArray());
    //         }

    //         if ($channel == "email") {
    //             $actor->email_verified_at = now();
    //             $actor->save();

    //             $actor = self::apiToken($actor);

    //             if ($guard == "vendor") {
    //                 $actor->subscription = [
    //                     "status"            => $actor->subscription?->status,
    //                     "start_date"        => $actor->subscription?->start_date,
    //                     "end_date"          => $actor->subscription?->end_date,
    //                     "subscription_plan" => $actor->subscription?->subscriptionPlan,
    //                 ];
    //                 $actor->role        = null;
    //                 $actor->permissions = [];
    //             }
    //             return self::apiResponse(in_error: false, message: "Action Successful", reason: "Email Verified successfully", status_code: self::API_SUCCESS, data: $actor->toArray());
    //         }

    //         if ($channel == self::API_FAIL) {
    //             return self::apiResponse(in_error: true, message: "Action Unsuccessful", reason: "Otp expired", status_code: self::API_FAIL, data: []);
    //         }

    //         return self::apiResponse(in_error: true, message: "Action Unsuccessful", reason: "Otp not found", status_code: self::API_NOT_FOUND, data: []);
    //     }

    //     return self::apiResponse(in_error: true, message: "Action Unsuccessful", reason: "User account cannot be found", status_code: self::API_NOT_FOUND, data: []);
    // }

    // protected static function verifyAffiliateAccount(string $otp, ?Actor $actor = null, string $guard)
    // {
    //     if ($actor) {
    //         $channel = self::verifyOtp(guard: $guard, otp: $otp, actor_id: $actor->id);

    //         if ($channel == "phone") {
    //             $actor->phone_number_verified_at = now();
    //             $actor->save();

    //             $actor = self::apiToken($actor);

    //             return self::apiResponse(in_error: false, message: "Action Successful", reason: "Phone number verified successfully", status_code: self::API_SUCCESS, data: $actor->toArray());
    //         }

    //         if ($channel == "email") {
    //             $actor->email_verified_at = now();
    //             $actor->save();

    //             $actor = self::apiToken($actor);

    //             return self::apiResponse(in_error: false, message: "Action Successful", reason: "Email Verified successfully", status_code: self::API_SUCCESS, data: $actor->toArray());
    //         }

    //         if ($channel == self::API_FAIL) {
    //             return self::apiResponse(in_error: true, message: "Action Unsuccessful", reason: "Otp expired", status_code: self::API_FAIL, data: []);
    //         }

    //         return self::apiResponse(in_error: true, message: "Action Unsuccessful", reason: "Otp not found", status_code: self::API_NOT_FOUND, data: []);
    //     }

    //     return self::apiResponse(in_error: true, message: "Action Unsuccessful", reason: "User account cannot be found", status_code: self::API_NOT_FOUND, data: []);
    // }

    // protected static function resetActorPassword(string $otp, ?Actor $actor = null, string $guard, string $password)
    // {
    //     if ($actor) {
    //         $status = self::verifyOtp(guard: $guard, otp: $otp, actor_id: $actor->id);

    //         if ($status == self::API_NOT_FOUND) {
    //             return self::apiResponse(in_error: true, message: "Action Unsuccessful", reason: "Otp not found", status_code: self::API_NOT_FOUND, data: []);
    //         }

    //         if ($status == self::API_FAIL) {
    //             return self::apiResponse(in_error: true, message: "Action Unsuccessful", reason: "Otp expired", status_code: self::API_FAIL, data: []);
    //         }

    //         $actor->password = $password;
    //         $actor->save();

    //         return self::apiResponse(in_error: false, message: "Action Successful", reason: "Password reset succcessfully", status_code: self::API_SUCCESS, data: []);
    //     }

    //     return self::apiResponse(in_error: true, message: "Action Unsuccessful", reason: "User account cannot be found", status_code: self::API_NOT_FOUND, data: []);
    // }
}
