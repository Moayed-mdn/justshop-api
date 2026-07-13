<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms\Marketing\Store\Admin;

use App\Enums\Cms\Marketing\MarketingPageStatusEnum;
use App\Enums\Cms\Marketing\MarketingPageTemplateEnum;
use App\Enums\Cms\Marketing\MarketingSectionTypeEnum;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateStoreMarketingPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'template'        => ['sometimes', 'nullable', 'string', Rule::in(MarketingPageTemplateEnum::storeTemplates())],
            'page_template_id' => ['sometimes', 'nullable', 'integer', 'exists:page_templates,id'],
            'sort_order'      => ['sometimes', 'integer', 'min:0'],
            'is_homepage'     => ['sometimes', 'boolean'],

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
            $this->validateSectionContent($v);
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

    /**
     * Validate section content based on section type.
     * Per-type content validation ensures required fields are present.
     * Custom sections are unconstrained.
     */
    private function validateSectionContent(\Illuminate\Validation\Validator $v): void
    {
        $sections = $this->input('sections', []);

        if (!is_array($sections)) {
            return;
        }

        foreach ($sections as $index => $section) {
            if (!is_array($section)) {
                continue;
            }

            // Get section type (accept both 'section_type' and 'type')
            $type = $section['section_type'] ?? $section['type'] ?? null;
            $content = $section['content'] ?? [];

            if (!is_string($type) || !is_array($content)) {
                continue;
            }

            // Validate based on type
            match ($type) {
                'video' => $this->validateVideoContent($v, $index, $content),
                'cta' => $this->validateCtaContent($v, $index, $content),
                'features' => $this->validateFeaturesContent($v, $index, $content),
                'faq' => $this->validateFaqContent($v, $index, $content),
                'hero' => $this->validateHeroContent($v, $index, $content),
                'testimonials' => $this->validateTestimonialsContent($v, $index, $content),
                'pricing' => $this->validatePricingContent($v, $index, $content),
                'products' => $this->validateProductsContent($v, $index, $content),
                'gallery' => $this->validateGalleryContent($v, $index, $content),
                'content' => $this->validateContentSectionContent($v, $index, $content),
                'custom' => null, // Custom sections are unconstrained
                default => null,
            };
        }
    }

    private function validateVideoContent(\Illuminate\Validation\Validator $v, int $index, array $content): void
    {
        // Only validate when a video_url key is present and non-empty string
        $url = $content['video_url'] ?? null;
        if ($url !== null && $url !== '' && !is_string($url)) {
            $v->errors()->add("sections.{$index}.content.video_url", 'Video URL must be a string.');
        }
    }

    private function validateCtaContent(\Illuminate\Validation\Validator $v, int $index, array $content): void
    {
        // Only validate individual CTA structure when items are present
        $ctas = $content['ctas'] ?? [];
        if (!is_array($ctas)) {
            return;
        }
        foreach ($ctas as $ctaIndex => $cta) {
            if (!is_array($cta)) {
                $v->errors()->add("sections.{$index}.content.ctas.{$ctaIndex}", 'Each CTA must have a label and URL.');
                continue;
            }
            $label = $cta['label'] ?? null;
            $hasLabel = is_string($label) ? $label !== '' : (is_array($label) && array_filter($label, fn ($val) => $val !== '' && $val !== null));
            $url = $cta['url'] ?? null;
            $hasUrl = is_string($url) && $url !== '';
            if (!$hasLabel || !$hasUrl) {
                $v->errors()->add("sections.{$index}.content.ctas.{$ctaIndex}", 'Each CTA must have a label and URL.');
            }
        }
    }

    private function validateFeaturesContent(\Illuminate\Validation\Validator $v, int $index, array $content): void
    {
        // Empty items array is allowed (draft state); no minimum count enforced
    }

    private function validateFaqContent(\Illuminate\Validation\Validator $v, int $index, array $content): void
    {
        // Empty items array is allowed (draft state); no minimum count enforced
    }

    private function validateHeroContent(\Illuminate\Validation\Validator $v, int $index, array $content): void
    {
        // Empty items array is allowed (draft state); no minimum count enforced
    }

    private function validateTestimonialsContent(\Illuminate\Validation\Validator $v, int $index, array $content): void
    {
        // Empty testimonials array is allowed (draft state); no minimum count enforced
    }

    private function validatePricingContent(\Illuminate\Validation\Validator $v, int $index, array $content): void
    {
        // Empty plans array is allowed (draft state); no minimum count enforced
    }

    private function validateProductsContent(\Illuminate\Validation\Validator $v, int $index, array $content): void
    {
        // Empty product_ids array is allowed (draft state); no minimum count enforced
    }

    private function validateGalleryContent(\Illuminate\Validation\Validator $v, int $index, array $content): void
    {
        // Empty members array is allowed (draft state); no minimum count enforced
    }

    private function validateContentSectionContent(\Illuminate\Validation\Validator $v, int $index, array $content): void
    {
        // body can be a string or a localized array { en, ar } — require at least one non-empty locale
        $body = $content['body'] ?? null;
        $hasBody = is_string($body)
            ? $body !== ''
            : (is_array($body) && array_filter($body, fn ($val) => $val !== '' && $val !== null));
        if (!$hasBody) {
            $v->errors()->add("sections.{$index}.content.body", 'Body text is required for content sections.');
        }
    }
}
