<?php
namespace App\Http\Requests\Dealer;

use App\Http\Requests\Concerns\ValidatesMultipartImages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CarUploadRequest extends FormRequest
{
    use ValidatesMultipartImages;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand'               => ['nullable'],
            'model'               => ['nullable'],
            'region'              => ['nullable', 'string', 'max:120'],
            'location'            => ['nullable', 'string', 'max:255'],
            'year_of_manufacture' => ['nullable', 'integer', 'max:' . (date('Y') + 1)],
            'mileage'             => ['nullable', 'integer', 'min:0'],
            'mileage_unit'        => ['nullable', 'string'],
            'colour'              => ['nullable', 'string', 'max:50'],
            'price'               => ['nullable', 'numeric', 'min:0'],
            'swap_deals'          => ['nullable', 'boolean'],
            'aircon'              => ['nullable', 'boolean'],
            'registered'          => ['nullable', 'boolean'],
            'registration_year'   => ['required_if:registered,true', 'nullable', 'integer', 'min:1900', 'max:' . date('Y')],
            'fuel_type'           => ['nullable', 'string'],
            'transmission'        => ['nullable', 'string'],
            // Multipart files: images[] (max 5MB each). Existing https URLs also allowed.
            'images'              => ['nullable', 'array'],
            'images.*'            => ['nullable'],
            'description'         => ['nullable', 'string'],
            'status'              => ['nullable', 'string', 'in:draft,pending_payment,pending_approval'],
            'dealer_code'         => ['nullable', 'string', 'exists:dealers,dealer_code'],
            'phone_number'        => ['nullable', 'string'],
            'network'             => ['nullable', 'string'],
            'plan_name'           => ['nullable', 'string'],
            'plan_slug'           => ['nullable', 'string'],
            'plan_details'        => ['nullable', 'array'],
            'plan_price'          => ['nullable', 'numeric'],
            'payment_method'      => ['nullable', 'string'],
            'callback_url'        => ['nullable', 'url'],
        ];
    }

    public function messages(): array
    {
        return $this->multipartImageMessages();
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('plan_details') && is_string($this->input('plan_details'))) {
            $decoded = json_decode($this->input('plan_details'), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->merge(['plan_details' => $decoded]);
            }
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateMultipartImageList($validator);
        });
    }
}
