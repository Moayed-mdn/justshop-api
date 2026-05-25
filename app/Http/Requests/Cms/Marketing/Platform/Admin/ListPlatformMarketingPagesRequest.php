<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms\Marketing\Platform\Admin;

use App\Enums\Cms\Marketing\MarketingPageStatusEnum;
use App\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPlatformMarketingPagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::MARKETING_PLATFORM_VIEW) === true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', 'string', Rule::in([...MarketingPageStatusEnum::values(), 'all'])],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
