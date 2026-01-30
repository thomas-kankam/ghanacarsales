<?php

namespace App\Http\Controllers\Api\V1\Buyer;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Buyer\BuyerAlertRequest;
use App\Models\BuyerAlert;
use App\Services\AlertService;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends BaseApiController
{
    public function __construct(
        private AlertService $alertService,
        private OtpService $otpService
    ) {}

    public function create(BuyerAlertRequest $request): JsonResponse
    {
        $buyer = $request->user();
        $data = $request->validated();

        $alert = $this->alertService->createAlert($buyer, $data);

        return $this->apiResponse(
            in_error: false,
            message: "Alert created successfully",
            status_code: self::API_CREATED,
            data: $alert
        );
    }

    public function deactivate(Request $request): JsonResponse
    {
        $request->validate([
            'mobile_number' => 'required|string',
            'otp_code' => 'required|string|size:6',
        ]);

        if (!$this->otpService->verifyOtp($request->mobile_number, $request->otp_code, 'buyer')) {
            return $this->apiResponse(
                in_error: true,
                message: "Invalid or expired OTP",
                status_code: self::API_BAD_REQUEST
            );
        }

        BuyerAlert::where('mobile_number', $request->mobile_number)
            ->update(['is_active' => false]);

        return $this->apiResponse(
            in_error: false,
            message: "Alerts deactivated successfully"
        );
    }
}
