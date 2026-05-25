<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms\Marketing\Platform\Admin;

use App\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;

class PublishPlatformMarketingPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::MARKETING_PLATFORM_PUBLISH) === true;
    }

    public function rules(): array
    {
        return [
            'published_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
