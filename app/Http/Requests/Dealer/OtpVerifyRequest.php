<?php
namespace App\Http\Requests\Dealer;

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
            // 'phone_number' => ['required_without:email', 'nullable', 'string', 'regex:/^[0-9]{10,15}$/', 'exists:dealers,phone_number'],
            // 'email'        => ['required_without:phone_number', 'nullable', 'string', 'email', 'max:255', 'exists:dealers,email'],
            'dealer_slug' => ['required', 'nullable', 'string', 'exists:dealers,dealer_slug'],
            'token'       => ['required', 'string', 'size:5'],
        ];
    }
}
