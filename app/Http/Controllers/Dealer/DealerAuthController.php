<?php
namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dealer\DealerProfileUpdateRequest;
use App\Http\Requests\Dealer\DealerRegisterRequest;
use App\Http\Requests\Dealer\DealerResendotpRequest;
use App\Http\Requests\Dealer\LoginRequest;
use App\Http\Requests\Dealer\OtpVerifyRequest;
use App\Http\Requests\Dealer\RegisterDealerRequest;
use App\Http\Requests\Dealer\VerifyLoginOtpRequest;
use App\Jobs\SendEmailJob;
use App\Mail\EmailVerification;
use App\Mail\LoginVerification;
use App\Models\Dealer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DealerAuthController extends Controller
{
    public function testSms($msisdn): JsonResponse
    {
        $response = self::sendSms($msisdn, 'This is a test message from Ghana Car Sales', 'GHCARSALES');

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            status_code: self::API_SUCCESS,
            data: $response,
            reason: "Test SMS sent successfully."
        );
    }

    public function testEmail($email): JsonResponse
    {
        $otp = random_int(111111, 999999) . now();
        self::sendEmail(
            $email,
            email_class: "App\Mail\EmailVerification",
            parameters: [
                $email,
                $otp,
            ]
        );
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            status_code: self::API_SUCCESS,
            data: [],
            reason: "Test Email sent successfully."
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

        // self::sendEmail(
        //     $dealer->email,
        //     email_class: "App\Mail\EmailVerification",
        //     parameters: [
        //         $dealer->email,
        //         $otp,
        //     ]
        // );

        // self::sendSms($identifier, 'Your otp code: ' . $otp, 'GHCARSALES');

        if ($channel === 'email') {
            // SendEmailJob::dispatch(
            //     $dealer->email,
            //     [$dealer->full_name, $otp],
            //     EmailVerification::class
            // );
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

        // $message = "OTP sent to your {$channel} for verification (expires in 10 minutes)";
        $message = "OTP sent to your email and phone number for verification (expires in 10 minutes)";

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
        $dealer             = Dealer::where("dealer_slug", $data['dealer_slug'])->firstOrFail();
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
            'verified'    => true,
            'verified_at' => now(),
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

        // self::sendEmail(
        //     $identifier,
        //     email_class: "App\Mail\EmailVerification",
        //     parameters: [
        //         $identifier,
        //         $otp,
        //     ]
        // );
        // self::sendSms($identifier, 'Your otp code: ' . $otp, 'GHCARSALES');

        if ($channel === 'email') {
            SendEmailJob::dispatch(
                $dealer->email,
                [$dealer->full_name, $otp],
                EmailVerification::class
            );
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
        // $message = "OTP resent to your email and phone number for verification (expires in 10 minutes)";

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
            'terms_accepted_at' => now(),
            'is_onboarded'      => true,
            'status'      => 'active',
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

    public function OtpLogin(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $email        = $data['email'] ?? null;
        $phone_number = $data['phone_number'] ?? null;

        // $dealer = Dealer::query()
        //     ->when($email, fn($q) => $q->where('email', $email))
        //     ->when($phone_number, fn($q) => $q->orWhere('phone_number', $phone_number))
        //     ->first();

        $dealer = Dealer::where("phone_number", $phone_number)
            ->orWhere("email", $email)
            ->first();

        $identifier = $email ?? $phone_number;
        $channel    = $email ? 'email' : 'sms';

        if (! $dealer) {
            Log::info("Dealer not found for identifier: {$identifier}");

            return self::apiResponse(
                in_error: true,
                message: "Action Unsuccessful",
                reason: "Dealer cannot be found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        // -----------------------------------------
        // Generate OTP
        // -----------------------------------------
        $otp = self::generateOtp(
            type: "login",
            actor_id: $dealer->dealer_slug,
            channel: $channel,
            guard: "dealer"
        );

        // self::sendEmail(
        //     $dealer->email,
        //     email_class: "App\Mail\LoginVerification",
        //     parameters: [
        //         $dealer->email,
        //         $otp,
        //     ]
        // );

        // self::sendSms(
        //     $phone_number,
        //     'OTP Login code: ' . $otp,
        //     'GHCARSALES'
        // );

        // -----------------------------------------
        // Send OTP
        // -----------------------------------------
        if ($channel === 'email') {
            SendEmailJob::dispatch(
                $dealer->email,
                [$dealer->full_name, $otp],
                LoginVerification::class
            );
            self::sendEmail(
                $dealer->email,
                email_class: "App\Mail\LoginVerification",
                parameters: [
                    $dealer->email,
                    $otp,
                ]
            );
        } else {
            self::sendSms(
                $phone_number,
                'OTP Login code: ' . $otp,
                'GHCARSALES'
            );
        }

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            status_code: self::API_SUCCESS,
            data: $dealer?->toArray(),
            // reason: "OTP sent to your email and phone number for login (expires in 10 minutes)"
            reason: "OTP sent to your {$channel} for login (expires in 10 minutes)"
        );
    }

    public function verifyLoginOtp(VerifyLoginOtpRequest $request): JsonResponse
    {
        $data               = $request->validated();
        $dealer             = Dealer::where("dealer_slug", $data['dealer_slug'])->firstOrFail();
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
                status_code: self::API_UNAUTHORIZED,
                data: ['reason' => $verificationResult['reason']],
                reason: $verificationResult['message']
            );
        }

        $userWithToken = self::apiToken($dealer);
        $user_data     = $userWithToken->toArray();
        return self::apiResponse(
            in_error: false,
            message: "Login successful",
            status_code: self::API_SUCCESS,
            data: $user_data,
            reason: "OTP verified successfully"
        );
    }

    public function logout()
    {
        // Revoke user's API token
        request()->user()->token()->revoke();
        // Return success response
        return self::apiResponse(in_error: false, message: "Action Successful", reason: "Logout successful", status_code: self::API_SUCCESS, data: []);
    }

    public function updateProfile(DealerProfileUpdateRequest $request)
    {
        $dealer = $request->user();

        $dealer->update($request->validated());

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Dealer profile updated successfully",
            status_code: self::API_SUCCESS,
            data: $dealer->fresh()
        );
    }
}
