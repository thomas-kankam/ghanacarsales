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
}
