<?php
namespace App\Http\Requests\Dealer;

use Illuminate\Foundation\Http\FormRequest;

class CarUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand'               => ['nullable'],
            'model'               => ['nullable'],
            'region'              => ['required', 'string', 'max:120'],
            'location'            => ['required', 'string', 'max:255'],
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
            "images"              => ["nullable", "array"],
            "images.*"            => ["string", "starts_with:data:,http://,https://"],
            'description'         => ['nullable', 'string'],
            "status"              => ['nullable', 'string', 'in:draft,pending_payment,pending_approval'],
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
}
