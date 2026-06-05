<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Media;

use App\Enums\MediaContextEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'context' => [
                'required',
                'string',
                Rule::in(MediaContextEnum::values()),
            ],
            'path' => [
                'required',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'context.required' => __('media.context_required'),
            'context.in' => __('media.invalid_context'),
            'path.required' => __('media.path_required'),
        ];
    }
}
