<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

trait ValidatesMultipartImages
{
    public const MAX_IMAGE_KB = 5120; // 5MB

    protected function multipartImageMessages(string $field = 'images'): array
    {
        return [
            "{$field}.*.max"   => 'Each image must not exceed 5MB.',
            "{$field}.*.image" => 'Each file must be a valid image.',
            "{$field}.*.file"  => 'Each image must be uploaded as a multipart file.',
            'image.max'        => 'Image must not exceed 5MB.',
            'image.image'      => 'The file must be a valid image.',
            'image.file'       => 'Image must be uploaded as multipart/form-data.',
            'image.required'   => 'An image file is required (multipart field name: image).',
        ];
    }

    protected function validateMultipartImageList(Validator $validator, string $field = 'images'): void
    {
        foreach ($this->rawMultipartImages($field) as $index => $image) {
            if ($image === null || $image === '') {
                continue;
            }

            $key = "{$field}.{$index}";

            if ($image instanceof UploadedFile) {
                if ($image->getError() === UPLOAD_ERR_INI_SIZE || $image->getError() === UPLOAD_ERR_FORM_SIZE) {
                    $validator->errors()->add($key, 'Each image must not exceed 5MB.');
                    continue;
                }

                if (! $image->isValid()) {
                    $validator->errors()->add($key, 'Each image must be a valid image file.');
                    continue;
                }

                if (! str_starts_with((string) $image->getMimeType(), 'image/')) {
                    $validator->errors()->add($key, 'Each file must be a valid image.');
                    continue;
                }

                if ($image->getSize() > self::MAX_IMAGE_KB * 1024) {
                    $validator->errors()->add($key, 'Each image must not exceed 5MB.');
                }
                continue;
            }

            if (! is_string($image)) {
                $validator->errors()->add($key, 'Each image must be a multipart file or an existing image URL.');
                continue;
            }

            if (str_starts_with($image, 'data:')) {
                $validator->errors()->add(
                    $key,
                    'Base64 images are not accepted. Upload images as multipart/form-data files (max 5MB each).'
                );
                continue;
            }

            if (! str_starts_with($image, 'http://') && ! str_starts_with($image, 'https://')) {
                $validator->errors()->add($key, 'Each image must be a multipart file or an https image URL.');
            }
        }
    }

    /**
     * @return array<int, UploadedFile|string>
     */
    public function imageInputs(string $field = 'images'): array
    {
        return array_values(array_filter(
            $this->rawMultipartImages($field),
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

    public function hasImageInputs(string $field = 'images'): bool
    {
        return $this->hasFile($field)
            || $this->hasFile('image')
            || $this->filled($field);
    }

    /**
     * @return array<int, mixed>
     */
    protected function rawMultipartImages(string $field = 'images'): array
    {
        if ($this->hasFile($field)) {
            $files = $this->file($field);

            return is_array($files) ? array_values($files) : [$files];
        }

        if ($field === 'images' && $this->hasFile('image')) {
            return [$this->file('image')];
        }

        $input = $this->input($field, []);

        return is_array($input) ? array_values($input) : [];
    }
}
