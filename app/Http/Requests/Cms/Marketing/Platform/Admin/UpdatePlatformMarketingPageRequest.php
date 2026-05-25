<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms\Marketing\Platform\Admin;

use App\Enums\Cms\Marketing\MarketingPageStatusEnum;
use App\Enums\Cms\Marketing\MarketingPageTemplateEnum;
use App\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlatformMarketingPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::MARKETING_PLATFORM_UPDATE) === true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'array'],
            'title.*' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'array'],
            'slug.*' => ['required', 'string', 'max:255'],
            'excerpt' => ['sometimes', 'nullable', 'array'],
            'excerpt.*' => ['sometimes', 'nullable', 'string'],
            'content' => ['required', 'array'],
            'status' => ['required', 'string', Rule::in(MarketingPageStatusEnum::values())],
            'published_at' => ['sometimes', 'nullable', 'date'],
            'seo' => ['sometimes', 'nullable', 'array'],
            'template' => ['sometimes', 'nullable', 'string', Rule::in(MarketingPageTemplateEnum::platformTemplates())],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
