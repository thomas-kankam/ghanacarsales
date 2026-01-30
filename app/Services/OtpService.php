<?php

namespace App\Services;

use App\Models\OtpVerification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OtpService
{
    public function generateOtp(string $mobileNumber, string $userType = 'seller'): string
    {
        // Generate 6-digit OTP
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Delete old OTPs for this mobile number
        OtpVerification::where('mobile_number', $mobileNumber)
            ->where('user_type', $userType)
            ->where('is_verified', false)
            ->delete();

        // Create new OTP
        OtpVerification::create([
            'mobile_number' => $mobileNumber,
            'otp_code' => $otp,
            'user_type' => $userType,
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        return $otp;
    }

    public function verifyOtp(string $mobileNumber, string $otpCode, string $userType = 'seller'): bool
    {
        $otp = OtpVerification::where('mobile_number', $mobileNumber)
            ->where('otp_code', $otpCode)
            ->where('user_type', $userType)
            ->where('is_verified', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($otp) {
            $otp->update([
                'is_verified' => true,
                'verified_at' => Carbon::now(),
            ]);
            return true;
        }

        return false;
    }

    public function sendOtpSms(string $mobileNumber, string $otp): void
    {
        // TODO: Integrate with SMS provider (e.g., Twilio, Nexmo, etc.)
        // For now, just log it
        Log::info("OTP for {$mobileNumber}: {$otp}");
        
        // In production, you would send SMS here
        // Example: SMS::send($mobileNumber, "Your OTP is: {$otp}");
    }
}
