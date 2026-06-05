<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Media;

use App\Enums\MediaContextEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare data for validation.
     * This runs before validation and helps detect PHP upload errors.
     */
    protected function prepareForValidation(): void
    {
        // Check if file upload failed due to PHP limits
        if ($this->hasFile('image') === false && $this->has('image') === false) {
            // Check PHP's file upload error codes
            $uploadErrors = $_FILES['image']['error'] ?? null;
            
            if ($uploadErrors === UPLOAD_ERR_INI_SIZE || $uploadErrors === UPLOAD_ERR_FORM_SIZE) {
                // File exceeded PHP's upload_max_filesize or form MAX_FILE_SIZE
                $phpMaxSize = ini_get('upload_max_filesize');
                
                // Add a custom error that will be caught by validation
                $this->merge([
                    'php_upload_error' => "File size exceeds server limit of {$phpMaxSize}. Please upload a smaller file.",
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'context' => [
                'required',
                'string',
                Rule::in(MediaContextEnum::values()),
            ],
            'image' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,gif,webp',
                'max:5120', // 5MB
            ],
            // Custom rule to show PHP upload errors
            'php_upload_error' => [
                'prohibited', // This will always fail if present
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'context.required' => __('media.context_required'),
            'context.in' => __('media.invalid_context'),
            'image.required' => __('media.image_required'),
            'image.image' => __('media.must_be_image'),
            'image.mimes' => __('media.invalid_image_type'),
            'image.max' => __('media.image_too_large'),
            'php_upload_error.prohibited' => $this->input('php_upload_error', __('media.php_upload_limit_exceeded')),
        ];
    }
}
