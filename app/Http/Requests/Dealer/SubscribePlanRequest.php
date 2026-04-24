<?php
namespace App\Http\Requests\Dealer;

use Illuminate\Foundation\Http\FormRequest;

class SubscribePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_slug'     => ['required', 'string', 'exists:plans,plan_slug'],
            'phone_number'  => ['required', 'string', 'max:20'],
            'payment_method'=> ['nullable', 'string', 'in:momo'],
            'network'       => ['nullable', 'string', 'max:50'],
        ];
    }
}

