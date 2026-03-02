<?php
namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dealer\DealerRegisterRequest;
use App\Http\Requests\Dealer\DealerResendotpRequest;
use App\Http\Requests\Dealer\OtpVerifyRequest;
use App\Http\Requests\Dealer\RegisterDealerRequest;
use App\Models\Dealer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class DealerAuthController extends Controller
{
    public function testSms(): JsonResponse
    {
        self::sendSms('233556906969', 'This is a test message from Ghana Car Sales', 'GHCARSALES');

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            status_code: self::API_SUCCESS,
            data: [],
            reason: "Test SMS sent successfully."
        );
    }

    public function sendingOtp(DealerRegisterRequest $http_request): JsonResponse
    {
        // Get data from request
        $data = $http_request->validated();

        // Get the identifier (either phone_number or email)
        $identifier = $data['phone_number'] ?? $data['email'];

        // Determine the channel
        $channel = isset($data['email']) ? 'email' : 'sms';

        if ($identifier == "phone_number") {
            $data['phone_number'] = $data['phone_number'];
        }

        if ($identifier == "email") {
            $data['email'] = $data['email'];
        }

        $data['full_name']   = $data['full_name'];
        $data['dealer_slug'] = Str::uuid();

        $dealer = Dealer::create($data);

        // Generate and send OTP through appropriate channel
        $otp = self::generateOtp(
            type: "verification",
            actor_id: $dealer->dealer_slug,
            channel: $channel,
            guard: "dealer"
        );

        if ($channel === 'email') {
            self::sendEmail(
                $dealer->email,
                email_class: "App\Mail\EmailVerification",
                parameters: [
                    $dealer->email,
                    $otp,
                ]
            );
        } else {
            self::sendSms($identifier, 'Your otp code: ' . $otp, 'GHCARSALES');
        }

        $message = "OTP sent to your {$channel} for verification (expires in 10 minutes)";

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            status_code: self::API_SUCCESS,
            data: $dealer->fresh()->toArray(),
            reason: "Dealer created successfully. $message"
        );
    }

    public function verifyToken(OtpVerifyRequest $request): JsonResponse
    {
        // Get data from request
        $data               = $request->validated();
        $dealer             = Dealer::where("dealer_slug", $data['dealer_slug'])->first();
        $token              = $data['token'];
        $verificationResult = self::verifyOtp(identifier: $dealer->dealer_slug, token: $token, guard: 'dealer');
        if (! $verificationResult['success']) {
            $message = match ($verificationResult['reason']) {
                'not_found'        => 'Invalid OTP',
                'expired'          => 'OTP has expired',
                'already_verified' => 'OTP has already been used',
                default            => 'Action Unsuccessful'
            };

            return self::apiResponse(
                in_error: true,
                message: $message,
                status_code: self::API_SUCCESS,
                data: ['reason' => $verificationResult['reason']],
                reason: $verificationResult['message']
            );
        }

        $dealer->update([
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            // 'is_active'         => true,
        ]);

        $userWithToken = self::apiToken($dealer);
        $user_data     = $userWithToken->toArray();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            status_code: self::API_SUCCESS,
            data: $user_data,
            reason: "Account verified successfully."
        );
    }

    public function reSendOtp(DealerResendotpRequest $http_request): JsonResponse
    {
        // Get data from request
        $data = $http_request->validated();

        // Get the identifier (either phone_number or email)
        $identifier = $data['phone_number'] ?? $data['email'];

        // Determine the channel
        $channel = isset($data['email']) ? 'email' : 'sms';

        $dealer = Dealer::where("phone_number", $identifier)
            ->orWhere("email", $identifier)
            ->first();

        // Generate and send OTP through appropriate channel
        $otp = self::generateOtp(
            type: "verification",
            actor_id: $dealer->dealer_slug,
            channel: $channel,
            guard: "dealer"
        );

        if ($channel === 'email') {
            self::sendEmail(
                $identifier,
                email_class: "App\Mail\EmailVerification",
                parameters: [
                    $identifier,
                    $otp,
                ]
            );
        } else {
            self::sendSms($identifier, 'Your otp code: ' . $otp, 'GHCARSALES');
        }

        $message = "OTP resent to your {$channel} for verification (expires in 10 minutes)";

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            status_code: self::API_SUCCESS,
            data: $dealer->fresh()->toArray(),
            reason: $message
        );
    }

    public function registerDealer(RegisterDealerRequest $request): JsonResponse
    {
        // Get data from request
        $data = $request->validated();
        // Get the authenticated dealer
        $dealer = auth('dealer')->user();

        // Update dealer with additional registration data
        $dealer->update(array_merge($data, [
            'terms_accepted'    => true,
            'is_active'         => true,
            'is_onboarded'      => true,
            'terms_accepted_at' => now(),
        ]));

        $userWithToken = self::apiToken($dealer);
        $user_data     = $userWithToken->toArray();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            status_code: self::API_SUCCESS,
            data: $user_data,
            reason: "Dealer registered successfully."
        );
    }
}
