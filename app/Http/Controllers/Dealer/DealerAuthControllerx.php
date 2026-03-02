<?php
namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dealer\DealerRegisterRequest;
use App\Http\Requests\Dealer\OtpVerifyRequest;
use App\Models\Dealer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class DealerAuthController extends Controller
{
    // public function sendOtp(Request $request): JsonResponse
    public function sendingOtp(DealerRegisterRequest $http_request): JsonResponse
    {
        // Get data from request
        $data = $http_request->validated();

        // Get the identifier (either phone_number or email)
        $identifier = $data['phone_number'] ?? $data['email'];

        // Determine the channel
        $channel = isset($data['email']) ? 'email' : 'sms';

        // Store ONLY serializable data in cache for 15 minutes
        // $cacheKey = "dealer_register_{$identifier}";

        // Extract only the data you need (avoid storing the entire Request object)
        // $cacheData = [
        //     'phone_number' => $data['phone_number'] ?? null,
        //     'email'         => $data['email'] ?? null,
        //     'business_name' => $data['business_name'] ?? null,
        // ];

        // Cache::put($cacheKey, $cacheData, now()->addMinutes(15));

        // Log::info('Cache stored', [
        //     'key'       => $cacheKey,
        //     'data'      => $cacheData,
        //     'retrieved' => Cache::get($cacheKey),
        // ]);

        if ($identifier == "phone_number") {
            $data['phone_number'] = $data['phone_number'];
        }

        if ($identifier == "email") {
            $data['email'] = $data['email'];
        }

        $data['business_name'] = $data['business_name'];
        $data['dealer_slug']   = Str::uuid();

        $dealer = Dealer::create($data);

        // Generate and send OTP through appropriate channel
        $otp = self::generateOtp(
            type: "verification",
            actor_id: $identifier,
            channel: $channel,
            guard: "dealer"
        );

        if ($channel === 'email') {
            self::sendEmail($identifier, $otp);
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
        $data        = $request->validated();
        $identifier  = $data['phone_number'] ?? $data['email'];
        $token       = $data['token'];
        $is_verified = self::verifyOtp(identifier: $identifier, token: $token, guard: 'dealer');
        if (! $is_verified) {
            return self::apiResponse(
                in_error: true,
                message: "Action Unsuccessful",
                status_code: self::API_SUCCESS,
                data: [],
                reason: "Invalid or expired OTP"
            );
        }

        // $registerData = \Cache::get("seller_register_{$mobileNumber}");
        // if (! $registerData) {
        //     return $this->apiResponse(
        //         in_error: true,
        //         message: "Registration data not found. Please start registration again.",
        //         status_code: self::API_BAD_REQUEST
        //     );
        // }

        // if ($identifier == "phone_number") {
        //     $data['mobile_verified_at'] = now();
        // }

        // if ($identifier == "email") {
        //     $data['email_verified_at'] = now();
        // }

        // $data['terms_accepted']    = true;
        // $data['terms_accepted_at'] = now();

        $dealer = Dealer::where("phone_number", $identifier)
            ->orWhere
            ->first();

        if (! $dealer) {
            return self::apiResponse(
                in_error: true,
                message: "Action Unsuccessful",
                status_code: self::API_SUCCESS,
                data: [],
                reason: "Account not found."
            );
        }

        // Clear cached registration data
        // \Cache::forget("seller_register_{$mobileNumber}");
        $userWithToken = self::apiToken($dealer);
        $user_data     = $userWithToken->toArray();

        dd($user_data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            status_code: self::API_SUCCESS,
            data: $user_data,
            reason: "Account verified successfully."
        );
    }
}
