<?php

namespace App\Http\Requests\Seller;

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
            'brand_id' => ['required', 'exists:brands,id'],
            'model_id' => ['required', 'exists:car_models,id'],
            'year_of_manufacture' => ['required', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'mileage' => ['required', 'integer', 'min:0'],
            'mileage_unit' => ['required', 'in:kilometers,miles'],
            'price' => ['required', 'numeric', 'min:0'],
            'swap_deals' => ['required', 'boolean'],
            'aircon' => ['required', 'boolean'],
            'registered' => ['required', 'boolean'],
            'registration_year' => ['required_if:registered,true', 'nullable', 'integer', 'min:1900', 'max:' . date('Y')],
            'fuel_type' => ['required', 'in:petrol,diesel,hybrid,electric'],
            'transmission' => ['required', 'in:manual,automatic'],
            'colour' => ['required', 'string', 'max:50'],
            'location' => ['required', 'in:Greater Accra,Ashanti,Western,Eastern,Central,Northern,Upper East,Upper West,Volta,Brong Ahafo,Western North,Ahafo,Bono,Bono East,Oti,North East'],
            'images' => ['required', 'array', 'min:5', 'max:10'],
            'images.*' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
        ];
    }
}
