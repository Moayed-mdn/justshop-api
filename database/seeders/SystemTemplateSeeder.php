<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Theme\TemplateTypeEnum;
use App\Models\Theme\Theme;
use App\Models\Theme\ThemeTemplate;
use Illuminate\Database\Seeder;

class SystemTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $themes = Theme::all();

        if ($themes->isEmpty()) {
            $this->command?->warn('No themes found. Skipping system template seeder.');
            return;
        }

        foreach ($themes as $theme) {
            $this->seedSystemTemplatesForTheme($theme);
        }

        $this->command?->info("System templates seeded for {$themes->count()} themes.");
    }

    private function seedSystemTemplatesForTheme(Theme $theme): void
    {
        foreach (TemplateTypeEnum::cases() as $type) {
            if ($type->isSectionGroup()) {
                continue;
            }

            $sectionTypes = $this->getSectionTypesForPageType($type);
            $handle = $type->isSystemPage() ? 'system.' . $type->value : 'page.' . $type->value;

            ThemeTemplate::updateOrCreate(
                [
                    'theme_id' => $theme->id,
                    'type' => $type->value,
                ],
                [
                    'name' => $type->label(),
                    'handle' => $handle,
                    'description' => "Default {$type->label()} layout for the current theme.",
                    'settings' => [
                        'section_types' => $sectionTypes,
                    ],
                    'is_default' => true,
                ]
            );
        }
    }

    /**
     * Return the list of suggested section types for each system page template.
     * These are hints for the admin UI; actual section instances are assigned separately.
     */
    private function getSectionTypesForPageType(TemplateTypeEnum $type): array
    {
        return match ($type) {
            TemplateTypeEnum::HOME => [
                'header', 'announcement_bar', 'hero', 'content', 'footer', 'copyright_bar',
            ],
            TemplateTypeEnum::PAGE,
            TemplateTypeEnum::CUSTOM => [
                'header', 'announcement_bar', 'content', 'footer', 'copyright_bar',
            ],
            TemplateTypeEnum::CART => [
                'header', 'announcement_bar', 'content',
                'cart_items', 'cart_summary',
                'footer', 'copyright_bar',
            ],
            TemplateTypeEnum::CHECKOUT,
            TemplateTypeEnum::CHECKOUT_SUCCESS,
            TemplateTypeEnum::CHECKOUT_CANCEL => [
                'header', 'announcement_bar', 'content', 'footer', 'copyright_bar',
            ],
            TemplateTypeEnum::SEARCH => [
                'header', 'announcement_bar',
                'search_form', 'search_results', 'search_filters',
                'footer', 'copyright_bar',
            ],
            TemplateTypeEnum::LOGIN => [
                'header', 'announcement_bar', 'content', 'footer', 'copyright_bar',
            ],
            TemplateTypeEnum::REGISTER => [
                'header', 'announcement_bar', 'content', 'footer', 'copyright_bar',
            ],
            TemplateTypeEnum::FORGOT_PASSWORD => [
                'header', 'announcement_bar', 'content', 'footer', 'copyright_bar',
            ],
            TemplateTypeEnum::RESET_PASSWORD => [
                'header', 'announcement_bar', 'content', 'footer', 'copyright_bar',
            ],
            TemplateTypeEnum::VERIFY_EMAIL => [
                'header', 'announcement_bar', 'content', 'footer', 'copyright_bar',
            ],
            TemplateTypeEnum::ACCOUNT => [
                'header', 'announcement_bar',
                'content',
                'footer', 'copyright_bar',
            ],
            TemplateTypeEnum::ORDERS => [
                'header', 'announcement_bar',
                'content',
                'footer', 'copyright_bar',
            ],
            TemplateTypeEnum::ORDER,
            TemplateTypeEnum::ORDER_TRACK,
            TemplateTypeEnum::CATEGORIES => [
                'header', 'announcement_bar',
                'content',
                'footer', 'copyright_bar',
            ],
            TemplateTypeEnum::BLOG => [
                'header', 'announcement_bar',
                'content',
                'footer', 'copyright_bar',
            ],
            TemplateTypeEnum::BLOG_POST => [
                'header', 'announcement_bar',
                'content',
                'footer', 'copyright_bar',
            ],
            TemplateTypeEnum::SHOP => [
                'header', 'announcement_bar', 'content', 'footer', 'copyright_bar',
            ],
            TemplateTypeEnum::ERROR_404 => [
                'header', 'announcement_bar', 'error_404', 'footer', 'copyright_bar',
            ],
            TemplateTypeEnum::ERROR_500 => [
                'header', 'announcement_bar', 'error_500', 'footer', 'copyright_bar',
            ],
            TemplateTypeEnum::CATEGORY,
            TemplateTypeEnum::PRODUCT,
            TemplateTypeEnum::COLLECTION => [
                'header', 'announcement_bar', 'content', 'footer', 'copyright_bar',
            ],
            default => ['header', 'content', 'footer'],
        };
    }
}
