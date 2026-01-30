<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class SellerRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mobile_number' => ['required', 'string', 'regex:/^[0-9]{10,15}$/', 'unique:sellers,mobile_number'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:sellers,email'],
            'seller_type' => ['required', 'in:individual,dealer'],
            'business_name' => ['required_if:seller_type,dealer', 'nullable', 'string', 'max:255'],
            'business_location' => ['required_if:seller_type,dealer', 'nullable', 'string', 'max:255'],
            'terms_accepted' => ['required', 'accepted'],
        ];
    }
}
