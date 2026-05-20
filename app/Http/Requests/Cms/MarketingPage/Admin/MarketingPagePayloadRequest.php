<?php

declare(strict_types=1);

namespace App\Http\Requests\Cms\MarketingPage\Admin;

use App\Enums\Cms\MarketingPage\MarketingPageStatusEnum;
use App\Enums\Cms\MarketingPage\MarketingPageTypeEnum;
use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class MarketingPagePayloadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleEnum::SUPER_ADMIN->value) === true;
    }

    public function rules(): array
    {
        $rules = [
            'type' => ['required', Rule::in(MarketingPageTypeEnum::values())],
            'status' => ['required', Rule::in(MarketingPageStatusEnum::values())],
            'published_at' => ['sometimes', 'nullable', 'date'],
            'slug' => ['required', 'array:' . $this->localeKeyList()],
            'title' => ['required', 'array:' . $this->localeKeyList()],
            'sections' => ['required', 'array:hero,stats,faq,testimonials,cta,company_info,footer,social_links'],
            'seo' => ['required', 'array:meta_title,meta_description,canonical_url,robots,og_image'],

            'sections.hero' => ['required', 'array:title,subtitle,cta_primary,cta_secondary,badge,image'],
            'sections.stats' => ['sometimes', 'array'],
            'sections.faq' => ['sometimes', 'array'],
            'sections.testimonials' => ['sometimes', 'array'],
            'sections.cta' => ['sometimes', 'array:title,subtitle,primary_label,secondary_label,primary_url,secondary_url'],
            'sections.company_info' => ['sometimes', 'array:email,phone,address,hours,map_url'],
            'sections.footer' => ['sometimes', 'array:copyright,tagline,links'],
            'sections.social_links' => ['sometimes', 'array'],

            'sections.hero.image' => ['sometimes', 'nullable', 'string', 'max:2048'],

            'sections.stats.*' => ['array:label,value'],
            'sections.stats.*.value' => ['required', 'string', 'max:100'],

            'sections.faq.*' => ['array:question,answer'],
            'sections.testimonials.*' => ['array:quote,author,role,company'],

            'sections.cta.primary_url' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'sections.cta.secondary_url' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'sections.company_info.email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'sections.company_info.phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'sections.company_info.map_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'sections.footer.links' => ['sometimes', 'array'],
            'sections.footer.links.*' => ['array:label,url'],
            'sections.footer.links.*.url' => ['required', 'string', 'max:2048'],

            'sections.social_links.*' => ['array:platform,label,url'],
            'sections.social_links.*.platform' => ['required', 'string', 'max:50'],
            'sections.social_links.*.url' => ['required', 'url', 'max:2048'],

            'seo.canonical_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'seo.robots' => ['sometimes', 'nullable', 'string', 'max:255'],
            'seo.og_image' => ['sometimes', 'nullable', 'string', 'max:2048'],
        ];

        foreach ($this->locales() as $locale) {
            $rules["slug.{$locale}"] = ['required', 'string', 'max:255'];
            $rules["title.{$locale}"] = ['required', 'string', 'max:255'];

            $rules["sections.hero.title.{$locale}"] = ['required', 'string', 'max:255'];
            $rules["sections.hero.subtitle.{$locale}"] = ['sometimes', 'nullable', 'string'];
            $rules["sections.hero.cta_primary.{$locale}"] = ['sometimes', 'nullable', 'string', 'max:100'];
            $rules["sections.hero.cta_secondary.{$locale}"] = ['sometimes', 'nullable', 'string', 'max:100'];
            $rules["sections.hero.badge.{$locale}"] = ['sometimes', 'nullable', 'string', 'max:100'];

            $rules["sections.stats.*.label.{$locale}"] = ['required_with:sections.stats', 'string', 'max:255'];
            $rules["sections.faq.*.question.{$locale}"] = ['required_with:sections.faq', 'string', 'max:255'];
            $rules["sections.faq.*.answer.{$locale}"] = ['required_with:sections.faq', 'string'];
            $rules["sections.testimonials.*.quote.{$locale}"] = ['required_with:sections.testimonials', 'string'];
            $rules["sections.testimonials.*.author.{$locale}"] = ['required_with:sections.testimonials', 'string', 'max:255'];
            $rules["sections.testimonials.*.role.{$locale}"] = ['sometimes', 'nullable', 'string', 'max:255'];
            $rules["sections.testimonials.*.company.{$locale}"] = ['sometimes', 'nullable', 'string', 'max:255'];

            $rules["sections.cta.title.{$locale}"] = ['sometimes', 'nullable', 'string', 'max:255'];
            $rules["sections.cta.subtitle.{$locale}"] = ['sometimes', 'nullable', 'string'];
            $rules["sections.cta.primary_label.{$locale}"] = ['sometimes', 'nullable', 'string', 'max:100'];
            $rules["sections.cta.secondary_label.{$locale}"] = ['sometimes', 'nullable', 'string', 'max:100'];

            $rules["sections.company_info.address.{$locale}"] = ['sometimes', 'nullable', 'string', 'max:500'];
            $rules["sections.company_info.hours.{$locale}"] = ['sometimes', 'nullable', 'string', 'max:500'];
            $rules["sections.footer.copyright.{$locale}"] = ['sometimes', 'nullable', 'string', 'max:255'];
            $rules["sections.footer.tagline.{$locale}"] = ['sometimes', 'nullable', 'string', 'max:255'];
            $rules["sections.footer.links.*.label.{$locale}"] = ['required_with:sections.footer.links', 'string', 'max:255'];
            $rules["sections.social_links.*.label.{$locale}"] = ['required_with:sections.social_links', 'string', 'max:255'];

            $rules["seo.meta_title.{$locale}"] = ['required', 'string', 'max:255'];
            $rules["seo.meta_description.{$locale}"] = ['required', 'string', 'max:500'];
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $status = (string) $this->input('status', '');
            $publishedAt = $this->input('published_at');

            if ($status === MarketingPageStatusEnum::SCHEDULED->value) {
                if (!$publishedAt || strtotime((string) $publishedAt) <= now()->getTimestamp()) {
                    $validator->errors()->add('published_at', __('cms.scheduled_publish_requires_future_date'));
                }
            }
        });
    }

    /**
     * @return array<int, string>
     */
    protected function locales(): array
    {
        $locales = config('content.editable_locales', ['en', 'ar']);

        return is_array($locales) ? array_values($locales) : ['en', 'ar'];
    }

    protected function localeKeyList(): string
    {
        return implode(',', $this->locales());
    }
}
