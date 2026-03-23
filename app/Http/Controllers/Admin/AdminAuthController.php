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

        $data = Admin::create($data)->toArray();

        if ($data) {
            self::sendEmail(
                email: $email,
                email_class: "App\Mail\EmailPasswordChange",
                parameters: [$email, $password],
            );
            return self::apiResponse(
                in_error: false,
                message: "Action Successful",
                status_code: self::API_CREATED,
                data: $data,
                reason: "Admin created successfully"
            );
        }

        return self::apiResponse(
            in_error: true,
            message: "Action Unsuccessful",
            status_code: self::API_FAIL,
            data: $data,
            reason: "Admin not created"
        );
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return $this->apiResponse(
                in_error: true,
                message: "Invalid credentials",
                status_code: self::API_UNAUTHORIZED,
                data: null,
                reason: "Invalid credentials"
            );
        }

        $token = $admin->createToken('Admin Token')->accessToken;
        // add token to as response data
        $admin->token = $token;

        return $this->apiResponse(
            in_error: false,
            message: "Login successful",
            status_code: self::API_SUCCESS,
            data: $admin,
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

        return $this->apiResponse(
            in_error: false,
            message: "Profile updated successfully",
            status_code: self::API_SUCCESS,
            data: $admin->fresh()
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
            return $this->apiResponse(
                in_error: true,
                message: "Current password is incorrect",
                status_code: self::API_BAD_REQUEST,
                data: []
            );
        }

        $admin->update(['password' => $data['new_password']]);

        return $this->apiResponse(
            in_error: false,
            message: "Password changed successfully",
            status_code: self::API_SUCCESS,
            data: []
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

            self::sendEmail(
                $admin->email,
                email_class: "App\Mail\AdminPasswordResetMail",
                parameters: [
                    $admin->email,
                    $otp,
                ]
            );
        }

        return $this->apiResponse(
            in_error: false,
            message: "If the email exists, an OTP has been sent",
            status_code: self::API_SUCCESS,
            data: []
        );
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['nullable', 'string', 'required_without:otp'],
            'otp' => ['nullable', 'string', 'required_without:token'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $admin = Admin::where('email', $data['email'])->first();
        if (!$admin) {
            return $this->apiResponse(
                in_error: true,
                message: "Invalid credentials",
                status_code: self::API_BAD_REQUEST,
                data: []
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

            return $this->apiResponse(
                in_error: true,
                message: $message,
                status_code: self::API_BAD_REQUEST,
                data: ['reason' => $verificationResult['reason']],
                reason: $verificationResult['message']
            );
        }

        $admin->password = $data['password'];
        $admin->setRememberToken(Str::random(60));
        $admin->save();

        return $this->apiResponse(
            in_error: false,
            message: "Password reset successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }
}
