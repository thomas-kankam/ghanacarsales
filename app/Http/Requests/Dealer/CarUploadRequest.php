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
            'year_of_manufacture' => ['nullable', 'integer', 'max:' . (date('Y') + 1)],
            'mileage'             => ['nullable', 'integer', 'min:0'],
            'mileage_unit'        => ['nullable', 'in:kilometers,miles'],
            'colour'              => ['nullable', 'string', 'max:50'],
            'price'               => ['nullable', 'numeric', 'min:0'],
            'swap_deals'          => ['nullable', 'boolean'],
            'registered'          => ['nullable', 'boolean'],
            'aircon'              => ['nullable', 'boolean'],
            'registration_year'   => ['required_if:registered,true', 'nullable', 'integer', 'min:1900', 'max:' . date('Y')],
            'fuel_type'           => ['nullable', 'string'],
            // 'fuel_type'           => ['nullable', 'in:petrol,diesel,hybrid,electric'],
            'transmission'        => ['nullable', 'string'],
            'description'         => ['nullable', 'string'],
            // 'transmission'        => ['nullable', 'in:manual,automatic'],
            // 'location'            => ['nullable', 'in:Greater Accra,Ashanti,Western,Eastern,Central,Northern,Upper East,Upper West,Volta,Brong Ahafo,Western North,Ahafo,Bono,Bono East,Oti,North East'],
            "images"              => ["nullable", "array"],
            // 'images.*'            => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
            "images.*"            => ["string", "starts_with:data:,http://,https://"],

        ];
    }
}
