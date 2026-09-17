<?php
namespace App\Http\Requests\Dealer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->imageInputs() as $index => $image) {
                if ($image instanceof UploadedFile) {
                    if (! $image->isValid() || ! str_starts_with((string) $image->getMimeType(), 'image/')) {
                        $validator->errors()->add("images.$index", 'Each image must be a valid image file.');
                    }
                    continue;
                }

                if (! is_string($image) || $image === '') {
                    $validator->errors()->add("images.$index", 'Each image must be a file, URL, or base64 data URI.');
                    continue;
                }

                if (
                    ! str_starts_with($image, 'data:')
                    && ! str_starts_with($image, 'http://')
                    && ! str_starts_with($image, 'https://')
                ) {
                    $validator->errors()->add("images.$index", 'Each image must start with data:, http://, or https://.');
                }
            }
        });
    }

    /**
     * Images from JSON (base64/URLs) and/or multipart files.
     *
     * @return array<int, mixed>
     */
    public function imageInputs(): array
    {
        if ($this->hasFile('images')) {
            $files = $this->file('images');
            return is_array($files) ? array_values($files) : [$files];
        }

        $images = $this->input('images', []);
        return is_array($images) ? array_values($images) : [];
    }
}
