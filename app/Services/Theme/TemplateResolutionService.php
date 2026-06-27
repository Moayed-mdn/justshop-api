<?php

declare(strict_types=1);

namespace App\Services\Theme;

use App\Models\Cms\Marketing\Store\StoreMarketingPage;
use App\Models\PageTemplate;
use App\Models\PageTemplateOverride;

class TemplateResolutionService
{
    /**
     * Resolve template for a page (template + page-specific overrides)
     */
    public function resolveTemplate(StoreMarketingPage $page): ?ResolvedTemplate
    {
        // Get the base template
        $template = $page->pageTemplate;

        if (!$template) {
            // Fallback to store's default template for page type
            $template = PageTemplate::where('store_id', $page->store_id)
                ->where('type', 'page')
                ->where('is_default', true)
                ->where('is_active', true)
                ->first();
        }

        if (!$template) {
            return null;
        }

        // Get page-specific overrides
        $overrides = $page->templateOverrides->keyBy('section_id');

        // Merge template sections with overrides
        $resolvedSections = [];

        foreach ($template->sections as $sectionId => $sectionConfig) {
            $resolvedSections[$sectionId] = [
                'type' => $sectionConfig['type'],
                'settings' => $sectionConfig['settings'] ?? [],
            ];

            // Apply page-specific override if exists
            if ($overrides->has($sectionId)) {
                $override = $overrides->get($sectionId);
                $resolvedSections[$sectionId]['settings'] = array_merge(
                    $resolvedSections[$sectionId]['settings'],
                    $override->settings
                );
            }
        }

        return new ResolvedTemplate(
            id: $template->id,
            handle: $template->handle,
            name: $template->name,
            sections: $resolvedSections,
            sectionOrder: $template->section_order
        );
    }

    /**
     * Resolve template by ID with optional page overrides
     */
    public function resolveTemplateById(int $templateId, ?int $pageId = null): ?ResolvedTemplate
    {
        $template = PageTemplate::find($templateId);

        if (!$template) {
            return null;
        }

        $resolvedSections = [];

        foreach ($template->sections as $sectionId => $sectionConfig) {
            $resolvedSections[$sectionId] = [
                'type' => $sectionConfig['type'],
                'settings' => $sectionConfig['settings'] ?? [],
            ];
        }

        // Apply page-specific overrides if pageId provided
        if ($pageId) {
            $overrides = PageTemplateOverride::where('page_id', $pageId)->get()->keyBy('section_id');

            foreach ($overrides as $sectionId => $override) {
                if (isset($resolvedSections[$sectionId])) {
                    $resolvedSections[$sectionId]['settings'] = array_merge(
                        $resolvedSections[$sectionId]['settings'],
                        $override->settings
                    );
                }
            }
        }

        return new ResolvedTemplate(
            id: $template->id,
            handle: $template->handle,
            name: $template->name,
            sections: $resolvedSections,
            sectionOrder: $template->section_order
        );
    }

    /**
     * Get default template for a store and type
     */
    public function getDefaultTemplate(int $storeId, string $type = 'page'): ?PageTemplate
    {
        return PageTemplate::where('store_id', $storeId)
            ->where('type', $type)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Resolve a system page template by type (e.g. 'auth', 'legal', 'landing').
     * Looks up the template by handle convention: page.{type}
     */
    public function resolveSystemPageTemplate(int $storeId, string $type): ?ResolvedTemplate
    {
        $handle = 'page.' . $type;

        $template = PageTemplate::where('store_id', $storeId)
            ->where('handle', $handle)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return null;
        }

        $resolvedSections = [];

        foreach ($template->sections as $sectionId => $sectionConfig) {
            $resolvedSections[$sectionId] = [
                'type' => $sectionConfig['type'],
                'settings' => $sectionConfig['settings'] ?? [],
            ];
        }

        return new ResolvedTemplate(
            id: $template->id,
            handle: $template->handle,
            name: $template->name,
            sections: $resolvedSections,
            sectionOrder: $template->section_order
        );
    }
}
