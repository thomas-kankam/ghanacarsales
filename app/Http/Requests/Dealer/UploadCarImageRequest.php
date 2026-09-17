<?php
namespace App\Http\Requests\Dealer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class UploadCarImageRequest extends FormRequest
{
    public const MAX_IMAGE_KB = 5120; // 5MB

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => [
                'required',
                'file',
                'image',
                'max:' . self::MAX_IMAGE_KB,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'An image file is required (multipart field name: image).',
            'image.file'     => 'Image must be uploaded as multipart/form-data.',
            'image.image'    => 'The file must be a valid image.',
            'image.max'      => 'Image must not exceed 5MB.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $image = $this->file('image');

            if (! $image instanceof UploadedFile) {
                if ($this->filled('image') && is_string($this->input('image')) && str_starts_with($this->input('image'), 'data:')) {
                    $validator->errors()->add(
                        'image',
                        'Base64 images are not accepted. Upload the file as multipart/form-data (max 5MB).'
                    );
                }
                return;
            }

            if ($image->getError() === UPLOAD_ERR_INI_SIZE || $image->getError() === UPLOAD_ERR_FORM_SIZE) {
                $validator->errors()->add('image', 'Image must not exceed 5MB.');
                return;
            }

            if ($image->isValid() && $image->getSize() > self::MAX_IMAGE_KB * 1024) {
                $validator->errors()->add('image', 'Image must not exceed 5MB.');
            }
        });
    }
}
