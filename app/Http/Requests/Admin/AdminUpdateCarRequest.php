<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateCarRequest extends FormRequest
{
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
            'images.*'            => ['string', 'starts_with:data:,http://,https://'],
            'description'         => ['sometimes', 'nullable', 'string'],
        ];
    }
}
