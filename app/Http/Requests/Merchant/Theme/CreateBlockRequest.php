<?php

declare(strict_types=1);

namespace App\Http\Requests\Merchant\Theme;

use App\Enums\Theme\BlockTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(BlockTypeEnum::values())],
            'handle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'settings' => ['nullable', 'array'],
            'content' => ['nullable', 'array'],
            'position' => ['nullable', 'integer'],
            'is_enabled' => ['nullable', 'boolean'],
            'is_removable' => ['nullable', 'boolean'],
        ];
    }
}
