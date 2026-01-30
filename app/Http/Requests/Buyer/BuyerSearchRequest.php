<?php

namespace App\Http\Requests\Buyer;

use Illuminate\Foundation\Http\FormRequest;

class BuyerSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand_id' => ['nullable', 'exists:brands,id'],
            'model_id' => ['nullable', 'exists:car_models,id'],
            'min_year' => ['nullable', 'integer', 'min:1900'],
            'max_year' => ['nullable', 'integer', 'max:' . (date('Y') + 1)],
            'min_mileage' => ['nullable', 'integer', 'min:0'],
            'max_mileage' => ['nullable', 'integer', 'min:0'],
            'mileage_unit' => ['nullable', 'in:kilometers,miles'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'swap_deals' => ['nullable', 'boolean'],
            'aircon' => ['nullable', 'boolean'],
            'registered' => ['nullable', 'boolean'],
            'fuel_type' => ['nullable', 'in:petrol,diesel,hybrid,electric'],
            'transmission' => ['nullable', 'in:manual,automatic'],
            'colour' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'in:Greater Accra,Ashanti,Western,Eastern,Central,Northern,Upper East,Upper West,Volta,Brong Ahafo,Western North,Ahafo,Bono,Bono East,Oti,North East'],
            'sort_by' => ['nullable', 'in:price,year,mileage'],
            'sort_order' => ['nullable', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
