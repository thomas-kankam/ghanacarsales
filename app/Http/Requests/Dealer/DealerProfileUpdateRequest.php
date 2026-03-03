<?php
namespace App\Http\Requests\Dealer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DealerProfileUpdateRequest extends FormRequest
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
        $dealer = $this->user(); // authenticated dealer

        return [
            'phone_number'  => [
                'nullable',
                'string',
                'min:12',
                'max:12',
                'starts_with:233',
                Rule::unique('dealers', 'phone_number')->ignore($dealer->id),
            ],

            'email'         => [
                'nullable',
                'string',
                'email',
                Rule::unique('dealers', 'email')->ignore($dealer->id),
            ],

            'business_name' => ['nullable', 'string'],
            'business_type' => ['nullable', 'string'],
            'region'        => ['nullable', 'string'],
            'city'          => ['nullable', 'string'],
            'landmark'      => ['nullable', 'string'],
        ];
    }
}
