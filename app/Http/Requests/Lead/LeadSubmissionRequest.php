<?php

declare(strict_types=1);

namespace App\Http\Requests\Lead;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class LeadSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $metadata = $this->input('metadata', []);

        if (!is_array($metadata)) {
            $metadata = [];
        }

        $this->merge([
            'source_page' => $this->trimInput('source_page'),
            'locale' => $this->trimInput('locale'),
            'name' => $this->trimInput('name'),
            'email' => $this->trimInput('email'),
            'company' => $this->trimInput('company'),
            'phone' => $this->trimInput('phone'),
            'message' => $this->trimInput('message'),
            'website' => $this->trimInput('website'),
            'metadata' => array_map(
                fn (mixed $value): mixed => is_string($value) ? trim($value) : $value,
                $metadata,
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'source_page' => ['sometimes', 'nullable', 'string', 'max:255'],
            'locale' => ['sometimes', 'nullable', 'string', Rule::in(config('content.editable_locales', ['en', 'ar']))],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'max:255'],
            'company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'message' => ['required', 'string', 'max:5000'],
            'website' => ['sometimes', 'nullable', 'string', 'max:255', Rule::in([''])],
            'metadata' => ['sometimes', 'array:utm_source,utm_medium,utm_campaign,utm_term,utm_content,referrer,landing_page,gclid,fbclid,company_size,industry'],
            'metadata.utm_source' => ['sometimes', 'nullable', 'string', 'max:255'],
            'metadata.utm_medium' => ['sometimes', 'nullable', 'string', 'max:255'],
            'metadata.utm_campaign' => ['sometimes', 'nullable', 'string', 'max:255'],
            'metadata.utm_term' => ['sometimes', 'nullable', 'string', 'max:255'],
            'metadata.utm_content' => ['sometimes', 'nullable', 'string', 'max:255'],
            'metadata.referrer' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'metadata.landing_page' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'metadata.gclid' => ['sometimes', 'nullable', 'string', 'max:255'],
            'metadata.fbclid' => ['sometimes', 'nullable', 'string', 'max:255'],
            'metadata.company_size' => ['sometimes', 'nullable', 'string', 'max:100'],
            'metadata.industry' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    private function trimInput(string $key): ?string
    {
        $value = $this->input($key);

        if (!is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
