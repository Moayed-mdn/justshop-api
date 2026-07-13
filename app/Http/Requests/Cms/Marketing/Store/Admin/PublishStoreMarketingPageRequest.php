<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms\Marketing\Store\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PublishStoreMarketingPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'published_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
