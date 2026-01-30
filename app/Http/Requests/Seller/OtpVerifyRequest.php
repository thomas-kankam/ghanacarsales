<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class OtpVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mobile_number' => ['required', 'string', 'regex:/^[0-9]{10,15}$/'],
            'otp_code' => ['required', 'string', 'size:6'],
        ];
    }
}
