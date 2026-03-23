<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminRegisterRequest;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminAuthController extends Controller
{
    public function register(AdminRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['admin_slug'] = Str::uuid();
        $password = $data['password'];
        $email = $data['email'];
        $phone_number = $data['phone_number'];
        $admin = Admin::create($data);

        if ($admin) {
            $otp = self::generateOtp(
                type: 'password_reset',
                actor_id: $admin->admin_slug,
                channel: 'sms',
                guard: 'admin'
            );
            self::sendSms($phone_number, 'OTP Login code: ' . $otp);
            return self::apiResponse(
                in_error: false,
                message: "Action Successful",
                status_code: self::API_CREATED,
                data: $admin->toArray(),
                reason: "Admin created successfully"
            );
        }

        return self::apiResponse(in_error: true, message: "Action Unsuccessful", status_code: self::API_FAIL, data: [], reason: "Admin not created");
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return self::apiResponse(
                in_error: true,
                message: "Invalid credentials",
                status_code: self::API_UNAUTHORIZED,
                data: [],
                reason: "Invalid credentials"
            );
        }

        $token = $admin->createToken('Admin Token')->accessToken;
        // add token to as response data
        $admin->token = $token;

        return self::apiResponse(
            in_error: false,
            message: "Login successful",
            status_code: self::API_SUCCESS,
            data: $admin->toArray(),
            reason: "Login successful"
        );
    }

    public function profileUpdate(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:admins,email,' . $admin->id],
        ]);

        $admin->update($data);

        return self::apiResponse(
            in_error: false,
            message: "Profile updated successfully",
            status_code: self::API_SUCCESS,
            data: $admin->fresh()->toArray()
        );
    }

    public function changePassword(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($data['current_password'], $admin->password)) {
            return self::apiResponse(
                in_error: true,
                message: "Current password is incorrect",
                status_code: self::API_BAD_REQUEST,
                data: []
            );
        }

        $admin->update(['password' => $data['new_password']]);

        return self::apiResponse(
            in_error: false,
            message: "Password changed successfully",
            status_code: self::API_SUCCESS,
            data: [],
            reason: "Password changed successfully"
        );
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $admin = Admin::where('email', $data['email'])->first();
        if ($admin) {
            $otp = self::generateOtp(
                type: 'password_reset',
                actor_id: $admin->admin_slug,
                channel: 'email',
                guard: 'admin'
            );

            // self::sendEmail(
            //     $admin->email,
            //     email_class: "App\Mail\AdminPasswordResetMail",
            //     parameters: [
            //         $admin->email,
            //         $otp,
            //     ]
            // );

            self::sendSms($admin->phone_number, 'OTP Login code: ' . $otp);
            return self::apiResponse(
                in_error: false,
                message: "Action Successful",
                status_code: self::API_SUCCESS,
                data: $admin->toArray(),
                reason: "If the email exists, an OTP has been sent to your phone number for password reset"
            );
        }

        return self::apiResponse(
            in_error: true,
            message: "Action Unsuccessful",
            status_code: self::API_NOT_FOUND,
            data: [],
            reason: "Admin not found"
        );
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'admin_slug' => ['required', 'string'],
            // 'token' => ['nullable', 'string', 'required_without:otp'],
            'otp' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $admin = Admin::where('admin_slug', $data['admin_slug'])->first();
        if (!$admin) {
            return self::apiResponse(
                in_error: true,
                message: "Action Unsuccessful",
                status_code: self::API_NOT_FOUND,
                data: [],
                reason: "Admin not found"
            );
        }

        $verificationResult = self::verifyOtp(
            identifier: $admin->admin_slug,
            token: $data['token'] ?? $data['otp'],
            guard: 'admin'
        );

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
                status_code: self::API_BAD_REQUEST,
                data: ['reason' => $verificationResult['reason']],
                reason: $message
            );
        }

        $admin->password = $data['password'];
        $admin->setRememberToken(Str::random(60));
        $admin->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            status_code: self::API_SUCCESS,
            data: $admin->toArray(),
            reason: "Password reset successfully. Please login with your new password"
        );
    }
}
