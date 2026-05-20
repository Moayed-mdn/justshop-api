<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms\MarketingPage\Admin;

use App\Enums\Cms\MarketingPage\MarketingPageStatusEnum;
use App\Enums\Cms\MarketingPage\MarketingPageTypeEnum;
use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListMarketingPagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleEnum::SUPER_ADMIN->value) === true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'nullable', 'string', Rule::in(MarketingPageTypeEnum::values())],
            'status' => ['sometimes', 'nullable', 'string', Rule::in([...MarketingPageStatusEnum::values(), 'all'])],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
