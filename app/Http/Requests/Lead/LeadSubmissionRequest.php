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
        $metadataInput = $this->input('metadata');
        $metadata = null;

        if (is_array($metadataInput)) {
            $metadata = [];

            foreach ($metadataInput as $key => $value) {
                $metadata[$key] = is_string($value) ? trim($value) : $value;
            }
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
            'metadata' => $metadata,
        ]);
    }

    public function rules(): array
    {
        return [
            'source_page' => ['sometimes', 'nullable', 'string', 'max:255'],
            'locale' => ['sometimes', 'nullable', 'string', Rule::in(config('content.editable_locales', ['en', 'ar']))],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'message' => ['required', 'string', 'max:5000'],
            'website' => ['sometimes', 'nullable', 'string', 'max:255', Rule::in([''])],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'metadata.*' => ['nullable', 'string', 'max:2048'],
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
