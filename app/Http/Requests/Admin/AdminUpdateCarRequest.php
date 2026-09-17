<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\ValidatesMultipartImages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AdminUpdateCarRequest extends FormRequest
{
    use ValidatesMultipartImages;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand'               => ['sometimes', 'nullable', 'string', 'max:255'],
            'model'               => ['sometimes', 'nullable', 'string', 'max:255'],
            'region'              => ['sometimes', 'nullable', 'string', 'max:120'],
            'location'            => ['sometimes', 'nullable', 'string', 'max:255'],
            'year_of_manufacture' => ['sometimes', 'nullable', 'integer', 'max:' . (date('Y') + 1)],
            'mileage'             => ['sometimes', 'nullable', 'integer', 'min:0'],
            'mileage_unit'        => ['sometimes', 'nullable', 'string', 'max:20'],
            'colour'              => ['sometimes', 'nullable', 'string', 'max:50'],
            'price'               => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'swap_deals'          => ['sometimes', 'nullable', 'boolean'],
            'aircon'              => ['sometimes', 'nullable', 'boolean'],
            'registered'          => ['sometimes', 'nullable', 'boolean'],
            'registration_year'   => ['required_if:registered,true', 'nullable', 'integer', 'min:1900', 'max:' . date('Y')],
            'fuel_type'           => ['sometimes', 'nullable', 'string', 'max:50'],
            'transmission'        => ['sometimes', 'nullable', 'string', 'max:50'],
            'images'              => ['sometimes', 'nullable', 'array'],
            'images.*'            => ['nullable'],
            'description'         => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return $this->multipartImageMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateMultipartImageList($validator);
        });
    }
}
