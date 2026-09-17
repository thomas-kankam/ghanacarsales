<?php
namespace App\Http\Requests\Dealer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class CarUploadRequest extends FormRequest
{
    /** Max image size in kilobytes (5MB). */
    public const MAX_IMAGE_KB = 5120;

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
            // Multipart files: images[] / images[0], images[1], ...
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
        return [
            'images.*.max'   => 'Each image must not exceed 5MB.',
            'images.*.image' => 'Each file must be a valid image.',
            'images.*.file'  => 'Each image must be uploaded as a multipart file.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Allow plan_details as JSON string in multipart forms.
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
            foreach ($this->rawImageInputs() as $index => $image) {
                if ($image === null || $image === '') {
                    continue;
                }

                if ($image instanceof UploadedFile) {
                    if ($image->getError() === UPLOAD_ERR_INI_SIZE || $image->getError() === UPLOAD_ERR_FORM_SIZE) {
                        $validator->errors()->add("images.$index", 'Each image must not exceed 5MB.');
                        continue;
                    }

                    if (! $image->isValid()) {
                        $validator->errors()->add("images.$index", 'Each image must be a valid image file.');
                        continue;
                    }

                    if (! str_starts_with((string) $image->getMimeType(), 'image/')) {
                        $validator->errors()->add("images.$index", 'Each file must be a valid image.');
                        continue;
                    }

                    if ($image->getSize() > self::MAX_IMAGE_KB * 1024) {
                        $validator->errors()->add("images.$index", 'Each image must not exceed 5MB.');
                    }
                    continue;
                }

                if (! is_string($image)) {
                    $validator->errors()->add("images.$index", 'Each image must be a multipart file or an existing image URL.');
                    continue;
                }

                // Base64 in JSON/multipart text fields is rejected (causes ModSecurity 413).
                if (str_starts_with($image, 'data:')) {
                    $validator->errors()->add(
                        "images.$index",
                        'Base64 images are not accepted. Upload images as multipart/form-data files (max 5MB each).'
                    );
                    continue;
                }

                if (! str_starts_with($image, 'http://') && ! str_starts_with($image, 'https://')) {
                    $validator->errors()->add(
                        "images.$index",
                        'Each image must be a multipart file or an https image URL.'
                    );
                }
            }
        });
    }

    /**
     * Normalized images for storage: uploaded files and/or existing https URLs.
     *
     * @return array<int, UploadedFile|string>
     */
    public function imageInputs(): array
    {
        return array_values(array_filter(
            $this->rawImageInputs(),
            static function ($image) {
                if ($image instanceof UploadedFile) {
                    return true;
                }
                return is_string($image)
                    && $image !== ''
                    && ! str_starts_with($image, 'data:')
                    && (str_starts_with($image, 'http://') || str_starts_with($image, 'https://'));
            }
        ));
    }

    /**
     * @return array<int, mixed>
     */
    protected function rawImageInputs(): array
    {
        $images = [];

        if ($this->hasFile('images')) {
            $files = $this->file('images');
            $images = is_array($files) ? array_values($files) : [$files];
        } elseif ($this->hasFile('image')) {
            // Allow a single "image" field as well.
            $images = [$this->file('image')];
        } else {
            $input = $this->input('images', []);
            $images = is_array($input) ? array_values($input) : [];
        }

        return $images;
    }
}
