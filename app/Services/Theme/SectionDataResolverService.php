<?php

declare(strict_types=1);

namespace App\Services\Theme;

use App\Models\Category;
use App\Models\Cms\Marketing\Store\StoreMarketingPage;
use App\Models\Navigation\NavigationMenu;
use App\Models\Product;
use App\Models\Store;
use App\Repositories\Category\CategoryRepository;
use App\Repositories\Navigation\NavigationMenuRepository;
use App\Services\Cms\LocalizedContentResolver;

class SectionDataResolverService
{
    public function __construct(
        private readonly NavigationMenuRepository $navigationMenuRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly LocalizedContentResolver $localizedContentResolver,
    ) {}

    /**
     * Resolve data for a specific section
     */
    public function resolveSectionData(
        string $sectionType,
        array $settings,
        Store $store,
        string $locale,
        ?StoreMarketingPage $page = null
    ): array {
        return match ($sectionType) {
            'header' => $this->resolveHeaderData($settings, $store, $locale),
            'footer' => $this->resolveFooterData($settings, $store, $locale),
            'footer-minimal' => $this->resolveFooterMinimalData($settings, $store, $locale),
            'footer-legal' => $this->resolveFooterLegalData($settings, $store, $locale),
            'page_content' => $this->resolvePageContentData($settings, $page, $locale),
            'hero' => $this->resolveHeroData($settings, $locale),
            'product-grid' => $this->resolveProductGridData($settings, $store, $locale),
            'category-grid' => $this->resolveCategoryGridData($settings, $store, $locale),
            default => $this->resolveGenericSectionData($sectionType, $settings, $locale),
        };
    }

    /**
     * Resolve header section data
     */
    private function resolveHeaderData(array $settings, Store $store, string $locale): array
    {
        $menuHandle = $settings['menu'] ?? 'main-menu';
        $navigation = $this->resolveNavigation($menuHandle, $store, $locale);

        return [
            'type' => 'header',
            'settings' => $settings,
            'data' => [
                'navigation' => $navigation,
                'logo_url' => $store->logo_url ?? null,
                'store_name' => $store->name,
            ],
        ];
    }

    /**
     * Resolve footer section data
     */
    private function resolveFooterData(array $settings, Store $store, string $locale): array
    {
        $menuHandle = $settings['menu'] ?? 'footer-menu';
        $navigation = $this->resolveNavigation($menuHandle, $store, $locale);

        return [
            'type' => 'footer',
            'settings' => $settings,
            'data' => [
                'navigation' => $navigation,
                'store_name' => $store->name,
                'copyright_year' => date('Y'),
            ],
        ];
    }

    /**
     * Resolve minimal footer section data
     */
    private function resolveFooterMinimalData(array $settings, Store $store, string $locale): array
    {
        $menuHandle = $settings['menu'] ?? 'footer-menu';
        $navigation = $this->resolveNavigation($menuHandle, $store, $locale);

        return [
            'type' => 'footer-minimal',
            'settings' => $settings,
            'data' => [
                'navigation' => $navigation,
            ],
        ];
    }

    /**
     * Resolve legal footer section data
     */
    private function resolveFooterLegalData(array $settings, Store $store, string $locale): array
    {
        $menuHandle = $settings['menu'] ?? 'legal-footer';
        $navigation = $this->resolveNavigation($menuHandle, $store, $locale);

        return [
            'type' => 'footer-legal',
            'settings' => $settings,
            'data' => [
                'navigation' => $navigation,
            ],
        ];
    }

    /**
     * Resolve page content section data
     */
    private function resolvePageContentData(array $settings, ?StoreMarketingPage $page, string $locale): array
    {
        $content = '';
        $title = '';

        if ($page) {
            $content = $page->getLocalized('content', $locale) ?? '';
            $title = $page->getLocalized('title', $locale) ?? '';
        }

        return [
            'type' => 'page_content',
            'settings' => $settings,
            'data' => [
                'content' => $content,
                'title' => $title,
            ],
        ];
    }

    /**
     * Resolve hero section data
     */
    private function resolveHeroData(array $settings, string $locale): array
    {
        return [
            'type' => 'hero',
            'settings' => $settings,
            'data' => [
                'heading' => $settings['heading'] ?? 'Welcome',
                'text' => $settings['text'] ?? '',
                'image_url' => $settings['image_url'] ?? null,
            ],
        ];
    }

    /**
     * Resolve product grid section data
     */
    private function resolveProductGridData(array $settings, Store $store, string $locale): array
    {
        $limit = min((int) ($settings['limit'] ?? 8), 24);

        $products = Product::where('store_id', $store->id)
            ->active()
            ->with(['translations', 'defaultVariant.primaryImage', 'activeVariants'])
            ->when($settings['category_id'] ?? null, fn ($q, $catId) => $q->where('category_id', (int) $catId))
            ->inRandomOrder()
            ->limit($limit)
            ->get()
            ->map(function (Product $product) use ($locale): array {
                $translation = $product->translations->firstWhere('locale', $locale);
                $variant = $product->defaultVariant;

                return [
                    'id' => $product->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'product_name' => $translation?->name ?? $product->slug,
                    'slug' => $translation?->slug ?? $product->slug,
                    'primary_image' => $variant?->primaryImage?->url ?? $variant?->images->first()?->url ?? null,
                    'price' => $variant?->price ?? 0,
                    'description' => $translation?->description ?? '',
                    'category_id' => $product->category_id,
                ];
            })
            ->values()
            ->all();

        return [
            'type' => 'product-grid',
            'settings' => $settings,
            'data' => [
                'heading' => $settings['heading'] ?? 'Featured Products',
                'subtitle' => $settings['subtitle'] ?? '',
                'viewAllLink' => $settings['view_all_link'] ?? ($settings['viewAllLink'] ?? ''),
                'products' => $products,
            ],
        ];
    }

    /**
     * Resolve category grid section data
     */
    private function resolveCategoryGridData(array $settings, Store $store, string $locale): array
    {
        $limit = min((int) ($settings['limit'] ?? 8), 24);

        $categories = $this->categoryRepository
            ->getRootCategories($store->id)
            ->take($limit)
            ->map(function (Category $category) use ($locale): array {
                $translation = $category->translations->firstWhere('locale', $locale);
                $slug = $translation?->slug ?? $category->slug;

                return [
                    'id' => $category->id,
                    'name' => $translation?->name ?? $category->slug,
                    'slug' => $slug,
                    'path' => "/shop/category/{$slug}",
                    'productCount' => $category->products_count ?? null,
                    'image' => null,
                ];
            })
            ->values()
            ->all();

        return [
            'type' => 'category-grid',
            'settings' => $settings,
            'data' => [
                'heading' => $settings['heading'] ?? 'Shop by Category',
                'subtitle' => $settings['subtitle'] ?? '',
                'categories' => $categories,
            ],
        ];
    }

    /**
     * Generic section data resolver for unknown types
     */
    private function resolveGenericSectionData(string $sectionType, array $settings, string $locale): array
    {
        return [
            'type' => $sectionType,
            'settings' => $settings,
            'data' => [],
        ];
    }

    /**
     * Resolve navigation menu by handle
     */
    private function resolveNavigation(string $menuHandle, Store $store, string $locale): array
    {
        $menu = $this->navigationMenuRepository->getByHandle($menuHandle, $store->id);

        if (!$menu) {
            return [];
        }

        return $menu->rootItems
            ->where('is_active', true)
            ->sortBy('position')
            ->map(function ($item) use ($locale) {
                return $this->transformNavigationItem($item, $locale);
            })
            ->values()
            ->all();
    }

    /**
     * Transform navigation item to API format
     */
    private function transformNavigationItem($item, string $locale): array
    {
        $children = $item->children()
            ->where('is_active', true)
            ->orderBy('position')
            ->get()
            ->map(fn ($child) => $this->transformNavigationItem($child, $locale))
            ->values()
            ->all();

        $label = $item->label;
        $decoded = json_decode($label, true);
        if (is_array($decoded)) {
            $label = $this->localizedContentResolver->resolveLocalizedField($decoded, $locale) ?? $label;
        }

        return [
            'id' => (string) $item->id,
            'label' => $label,
            'path' => $item->getResolvedUrl($locale),
            'type' => $item->type,
            'external' => $item->target === '_blank',
            'children' => $children,
        ];
    }
}
