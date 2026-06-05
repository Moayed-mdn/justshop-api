<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\HeroBanner;

use App\Enums\HeroBanner\HeroLinkTargetEnum;
use App\Enums\HeroBanner\HeroVisualTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateHeroBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cat_url' => ['required', 'string', 'max:255'],
            'position' => ['required', 'integer', 'min:0'],
            'visual_type' => ['required', 'string', Rule::in(HeroVisualTypeEnum::values())],
            'image_path' => ['nullable', 'string', 'max:500'],
            'gradient_from' => ['nullable', 'string', 'max:7'],
            'gradient_to' => ['nullable', 'string', 'max:7'],
            'video_url' => ['nullable', 'string', 'max:500'],
            'link_url' => ['nullable', 'string', 'max:500'],
            'link_text' => ['nullable', 'string', 'max:100'],
            'link_target' => ['nullable', 'string', Rule::in(HeroLinkTargetEnum::values())],
            'is_active' => ['required', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*.locale' => ['required', 'string', Rule::in(['en', 'ar'])],
            'translations.*.title' => ['required', 'string', 'max:255'],
            'translations.*.subtitle' => ['nullable', 'string', 'max:500'],
            'translations.*.cta_text' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'cat_url.required' => 'Category URL is required',
            'position.required' => 'Position is required',
            'visual_type.required' => 'Visual type is required',
            'is_active.required' => 'Active status is required',
            'ends_at.after' => 'End date must be after start date',
            'translations.required' => 'At least one translation is required',
            'translations.*.locale.required' => 'Translation locale is required',
            'translations.*.locale.in' => 'Translation locale must be en or ar',
            'translations.*.title.required' => 'Translation title is required',
        ];
    }
}
