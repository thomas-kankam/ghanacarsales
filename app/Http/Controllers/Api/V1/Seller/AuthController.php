<?php

namespace App\Http\Controllers\Api\V1\Seller;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Seller\OtpVerifyRequest;
use App\Http\Requests\Seller\SellerRegisterRequest;
use App\Models\Seller;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends BaseApiController
{
    public function __construct(
        private OtpService $otpService
    ) {}

    public function sendOtp(SellerRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $mobileNumber = $data['mobile_number'];

        // Store registration data in cache for 15 minutes
        \Cache::put("seller_register_{$mobileNumber}", $data, now()->addMinutes(15));

        $otp = $this->otpService->generateOtp($mobileNumber, 'seller');
        $this->otpService->sendOtpSms($mobileNumber, $otp);

        return $this->apiResponse(
            in_error: false,
            message: "OTP sent successfully",
            data: ['mobile_number' => $mobileNumber]
        );
    }

    public function verifyOtpAndRegister(OtpVerifyRequest $request): JsonResponse
    {
        $mobileNumber = $request->mobile_number;
        $otpCode = $request->otp_code;

        if (!$this->otpService->verifyOtp($mobileNumber, $otpCode, 'seller')) {
            return $this->apiResponse(
                in_error: true,
                message: "Invalid or expired OTP",
                status_code: self::API_BAD_REQUEST
            );
        }

        $registerData = \Cache::get("seller_register_{$mobileNumber}");
        if (!$registerData) {
            return $this->apiResponse(
                in_error: true,
                message: "Registration data not found. Please start registration again.",
                status_code: self::API_BAD_REQUEST
            );
        }

        $registerData['seller_slug'] = Str::uuid();
        $registerData['mobile_verified_at'] = now();
        $registerData['terms_accepted_at'] = now();

        $seller = Seller::create($registerData);

        // Clear cached registration data
        \Cache::forget("seller_register_{$mobileNumber}");

        $token = $seller->createToken('Seller Token')->accessToken;

        return $this->apiResponse(
            in_error: false,
            message: "Registration successful",
            status_code: self::API_CREATED,
            data: [
                'seller' => $seller,
                'token' => $token,
            ]
        );
    }
}
