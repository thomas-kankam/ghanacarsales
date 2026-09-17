<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class AdminUpdateCarRequest extends FormRequest
{
    public const MAX_IMAGE_KB = 5120; // 5MB

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
        return [
            'images.*.max' => 'Each image must not exceed 5MB.',
        ];
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
                    if (! $image->isValid() || ! str_starts_with((string) $image->getMimeType(), 'image/')) {
                        $validator->errors()->add("images.$index", 'Each image must be a valid image file.');
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

                if (str_starts_with($image, 'data:')) {
                    $validator->errors()->add(
                        "images.$index",
                        'Base64 images are not accepted. Upload images as multipart/form-data files (max 5MB each).'
                    );
                    continue;
                }

                if (! str_starts_with($image, 'http://') && ! str_starts_with($image, 'https://')) {
                    $validator->errors()->add("images.$index", 'Each image must be a multipart file or an https image URL.');
                }
            }
        });
    }

    /**
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
        if ($this->hasFile('images')) {
            $files = $this->file('images');
            return is_array($files) ? array_values($files) : [$files];
        }

        $input = $this->input('images', []);
        return is_array($input) ? array_values($input) : [];
    }
}
