<?php
namespace App\Http\Requests\Dealer;

use Illuminate\Foundation\Http\FormRequest;

class DealerRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone_number' => ['nullable', 'string', 'unique:dealers,phone_number', 'min:12', 'max:12', 'starts_with:233'],
            'email'        => ['nullable', 'string', 'email', 'unique:dealers,email'],
            'full_name'    => ['required', 'string'],
        ];
    }
}
