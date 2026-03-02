<?php
namespace App\Http\Requests\Dealer;

use Illuminate\Foundation\Http\FormRequest;

class DealerResendotpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone_number' => [
                'required_without:email',
                'nullable',
                'string',
                'exists:dealers,phone_number',
                'min:12',
                'max:12', 'starts_with:233'
            ],
            'email'        => [
                'required_without:phone_number',
                'nullable',
                'string',
                'exists:dealers,email',
            ],
        ];
    }
}
