<?php
namespace App\Http\Requests\Dealer;

use Illuminate\Foundation\Http\FormRequest;

class VerifyResetPasswordOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dealer_slug' => ['required', 'string', 'exists:dealers,dealer_slug'],
            'token'       => ['required', 'string', 'exists:otp_verifications,token'],
            'new_password' => ['required', 'string', 'min:8'],
        ];
    }
}
