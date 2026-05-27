<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms\Marketing\Store\Admin;

use App\Enums\Cms\Marketing\MarketingPageStatusEnum;
use App\Enums\Cms\Marketing\MarketingPageTemplateEnum;
use App\Enums\Cms\Marketing\MarketingSectionTypeEnum;
use App\Enums\PermissionEnum;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateStoreMarketingPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::MARKETING_STORE_CREATE) === true;
    }

    public function rules(): array
    {
        /** @var Store $store */
        $store = $this->route('store');

        return [
            // ── Core localized fields ──────────────────────────
            'title'   => ['required', 'array'],
            'title.*' => ['required', 'string', 'max:255'],

            'slug'   => ['required', 'array'],
            'slug.*' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            ],

            'excerpt'   => ['sometimes', 'nullable', 'array'],
            'excerpt.*' => ['sometimes', 'nullable', 'string', 'max:500'],

            // content is nullable — sections carry the structured content
            'content' => ['sometimes', 'nullable', 'array'],

            // ── Publishing ─────────────────────────────────────
            'status'       => ['required', 'string', Rule::in(MarketingPageStatusEnum::values())],
            'published_at' => ['sometimes', 'nullable', 'date'],

            // ── Metadata ───────────────────────────────────────
            'template'   => ['sometimes', 'nullable', 'string', Rule::in(MarketingPageTemplateEnum::storeTemplates())],
            'sort_order' => ['sometimes', 'integer', 'min:0'],

            // ── SEO ────────────────────────────────────────────
            'seo'                    => ['sometimes', 'nullable', 'array'],
            'seo.meta_title'         => ['sometimes', 'nullable', 'array'],
            'seo.meta_title.*'       => ['sometimes', 'nullable', 'string', 'max:255'],
            'seo.meta_description'   => ['sometimes', 'nullable', 'array'],
            'seo.meta_description.*' => ['sometimes', 'nullable', 'string', 'max:500'],
            'seo.canonical_url'      => ['sometimes', 'nullable', 'url', 'max:2048'],
            'seo.robots'             => ['sometimes', 'nullable', 'string', 'max:100'],
            'seo.og_image'           => ['sometimes', 'nullable', 'string', 'max:2048'],

            // ── Sections ───────────────────────────────────────
            'sections'                    => ['sometimes', 'nullable', 'array'],
            'sections.*'                  => ['array'],
            // Accept either 'section_type' (canonical) or 'type' (frontend alias).
            // At least one of the two must be present when sections are provided.
            'sections.*.section_type'     => [
                'nullable',
                'string',
                Rule::in(MarketingSectionTypeEnum::values()),
                'required_without:sections.*.type',
            ],
            'sections.*.type'             => [
                'nullable',
                'string',
                Rule::in(MarketingSectionTypeEnum::values()),
                'required_without:sections.*.section_type',
            ],
            'sections.*.identifier'       => ['sometimes', 'nullable', 'string', 'max:100'],
            'sections.*.sort_order'       => ['sometimes', 'integer', 'min:0'],
            'sections.*.title'            => ['sometimes', 'nullable', 'array'],
            'sections.*.title.*'          => ['sometimes', 'nullable', 'string', 'max:255'],
            'sections.*.subtitle'         => ['sometimes', 'nullable', 'array'],
            'sections.*.subtitle.*'       => ['sometimes', 'nullable', 'string'],
            'sections.*.content'          => ['sometimes', 'nullable', 'array'],
            'sections.*.settings'         => ['sometimes', 'nullable', 'array'],
            'sections.*.is_active'        => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $v): void {
            $this->validateScheduledPublishing($v);
            $this->validateStoreSlugUniqueness($v);
        });
    }

    private function validateScheduledPublishing(\Illuminate\Validation\Validator $v): void
    {
        $status      = $this->input('status', '');
        $publishedAt = $this->input('published_at');

        if ($status === MarketingPageStatusEnum::SCHEDULED->value) {
            if (!$publishedAt || strtotime((string) $publishedAt) <= now()->getTimestamp()) {
                $v->errors()->add('published_at', __('cms.scheduled_publish_requires_future_date'));
            }
        }
    }

    private function validateStoreSlugUniqueness(\Illuminate\Validation\Validator $v): void
    {
        /** @var Store|null $store */
        $store = $this->route('store');

        if (!$store) {
            return;
        }

        $slugs = $this->input('slug', []);

        if (!is_array($slugs)) {
            return;
        }

        foreach ($slugs as $locale => $slug) {
            if (!is_string($slug) || $slug === '') {
                continue;
            }

            $exists = \App\Models\Cms\Marketing\Store\StoreMarketingPage::query()
                ->where('store_id', $store->id)
                ->where("slug->{$locale}", $slug)
                ->exists();

            if ($exists) {
                $v->errors()->add("slug.{$locale}", __('cms.slug_already_exists'));
            }
        }
    }
}
