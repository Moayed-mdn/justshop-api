<?php

declare(strict_types=1);

namespace App\Http\Requests\Merchant\Asset;

use App\Enums\Theme\AssetTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(AssetTypeEnum::values())],
            'file' => ['required', 'file', 'max:10240'], // 10MB max
            'alt_text' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
        ];
    }
}
