<?php

declare(strict_types=1);

namespace App\Services\Storefront\Runtime;

use App\Enums\Theme\BlockTypeEnum;
use App\Enums\Theme\TemplateTypeEnum;
use Illuminate\Support\Facades\Storage;
use App\Models\Category;
use App\Models\Cms\Marketing\Store\StoreMarketingPage;
use App\Models\Product;
use App\Models\Store;
use App\Models\Theme\ThemeTemplate;
use App\Repositories\Category\CategoryRepository;
use App\Repositories\Navigation\NavigationMenuRepository;
use App\Repositories\Theme\ThemeRepository;
use App\Services\Cms\LocalizedContentResolver;
use App\Services\Cms\Seo\SeoResolutionService;
use App\Services\Theme\SectionDataResolverService;
use App\Services\Theme\TemplateResolutionService;
use App\Support\Media\MediaUrl;
use Throwable;

class StorefrontRuntimeService
{
    public function __construct(
        private readonly RuntimeCacheService $cacheService,
        private readonly RuntimeLogger $runtimeLogger,
        private readonly RuntimePreviewTokenService $previewTokenService,
        private readonly RuntimeResponseFactory $responseFactory,
        private readonly CategoryRepository $categoryRepository,
        private readonly LocalizedContentResolver $localizedContentResolver,
        private readonly SeoResolutionService $seoResolutionService,
        private readonly ThemeRepository $themeRepository,
        private readonly NavigationMenuRepository $navigationMenuRepository,
        private readonly TemplateResolutionService $templateResolutionService,
        private readonly SectionDataResolverService $sectionDataResolverService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function resolveRoute(string $path, string $locale): array
    {
        $store = $this->currentStore();
        $locale = $this->resolveLocale($locale);
        $path = $this->normalizedPath($path);
        $lookupPath = $this->lookupPathForLocale($path, $locale);
        $previewContext = $this->previewContextForRequest($store, $path, $locale);
        $ttl = (int) config('storefront_runtime.cache_ttl.route', 300);

        $data = $this->cacheService->remember(
            store: $store,
            locale: $locale,
            artifact: 'route',
            path: $path,
            ttlSeconds: $this->routeTtl($path, $ttl),
            bypass: $previewContext !== null,
            callback: fn (): array => $this->resolveRouteData($store, $path, $lookupPath, $locale, $previewContext),
        );

        $this->runtimeLogger->info(
            $data['status'] === 'redirect' ? 'runtime.route.redirect' : 'runtime.route.resolved',
            [
                'artifact' => 'route',
                'status' => $data['status'] === 'not_found' ? 'failure' : 'success',
                'path' => $path,
            ],
        );

        return $this->responseFactory->success(
            store: $store,
            locale: $locale,
            path: $path,
            preview: $previewContext !== null,
            data: $data,
            cache: $this->responseFactory->cache(
                store: $store,
                locale: $locale,
                artifact: 'route',
                path: $path,
                ttlSeconds: $this->routeTtl($path, $ttl),
                bypassed: $previewContext !== null,
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function pagePayload(string $pageId, bool $preview = false): array
    {
        $store = $this->currentStore();
        $locale = $this->resolvedLocaleFromRequest();
        $resolved = $this->resolvePageResource($store, $pageId, $preview, $locale);
        $path = $resolved['path'];
        $ttl = (int) config('storefront_runtime.cache_ttl.page', 3600);
        $bypass = false;

        if ($preview) {
            $token = (string) request()->header('X-Preview-Token', '');

            if ($token === '') {
                throw new RuntimeContractException(
                    runtimeCode: 'runtime.preview_invalid',
                    httpStatus: 403,
                    message: 'The preview token is invalid for the requested tenant and page.',
                    details: ['pageId' => $pageId, 'reason' => 'token_missing'],
                );
            }

            $this->previewTokenService->validate($store, $token, $pageId, $path, $locale);
            $bypass = true;
        }

        $data = $this->cacheService->remember(
            store: $store,
            locale: $locale,
            artifact: 'page',
            path: $path,
            ttlSeconds: $ttl,
            bypass: $bypass,
            callback: fn (): array => ['page' => $resolved['payload']],
        );

        $this->runtimeLogger->info('runtime.page.loaded', [
            'artifact' => 'page',
            'status' => $bypass ? 'bypassed' : 'success',
            'path' => $path,
        ]);

        return $this->responseFactory->success(
            store: $store,
            locale: $locale,
            path: $path,
            preview: $preview,
            data: $data,
            cache: $this->responseFactory->cache(
                store: $store,
                locale: $locale,
                artifact: 'page',
                path: $path,
                ttlSeconds: $ttl,
                bypassed: $bypass,
                pageId: $pageId,
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function navigationPayload(): array
    {
        $store = $this->currentStore();
        $locale = $this->resolvedLocaleFromRequest();
        $path = $this->resolvedPathFromRequest();
        $previewContext = $this->previewContextForRequest($store, $path, $locale);
        $ttl = (int) config('storefront_runtime.cache_ttl.navigation', 1800);

        $data = $this->cacheService->remember(
            store: $store,
            locale: $locale,
            artifact: 'navigation',
            path: $path,
            ttlSeconds: $ttl,
            bypass: $previewContext !== null,
            callback: fn (): array => $this->resolveNavigationDataFromDatabase($store, $locale),
        );

        $this->runtimeLogger->info('runtime.navigation.loaded', [
            'artifact' => 'navigation',
            'status' => 'success',
            'path' => $path,
        ]);

        return $this->responseFactory->success(
            store: $store,
            locale: $locale,
            path: $path,
            preview: $previewContext !== null,
            data: $data,
            cache: $this->responseFactory->cache(
                store: $store,
                locale: $locale,
                artifact: 'navigation',
                path: $path,
                ttlSeconds: $ttl,
                bypassed: $previewContext !== null,
            ),
        );
    }

    /**
     * Resolve navigation data from database
     * 
     * @return array<string, mixed>
     */
    private function resolveNavigationDataFromDatabase(Store $store, string $locale): array
    {
        $fallbackNavigation = $this->resolveNavigationData($store, $locale);
        $mainMenu = $this->navigationMenuRepository->getByHandle('main-menu', $store->id);
        $footerMenu = $this->navigationMenuRepository->getByHandle('footer-menu', $store->id);

        if (!$mainMenu && !$footerMenu) {
            return $fallbackNavigation;
        }

        return [
            'header' => $mainMenu
                ? $mainMenu->rootItems
                    ->map(fn ($item) => $this->transformNavigationItem($item, $locale))
                    ->values()
                    ->all()
                : ($fallbackNavigation['header'] ?? []),
            'footer' => $footerMenu
                ? $footerMenu->rootItems
                    ->map(fn ($item) => $this->transformNavigationItem($item, $locale))
                    ->values()
                    ->all()
                : ($fallbackNavigation['footer'] ?? []),
        ];
    }

    /**
     * Transform navigation menu item
     * 
     * @return array<string, mixed>
     */
    private function transformNavigationItem($item, string $locale): array
    {
        $fallbackLocale = (string) config('app.fallback_locale', 'en');
        $label = $this->resolveNavigationItemLabel($item->label, $locale, $fallbackLocale);
        
        // Use the model's resolved URL method with resource awareness
        $path = $item->getResolvedUrl($locale);

        return [
            'id' => $item->id,
            'label' => $label,
            'path' => $path,
            'external' => $this->isExternalNavigationPath($path, $item->type),
            'children' => $item->children
                ->map(fn ($child) => $this->transformNavigationItem($child, $locale))
                ->values()
                ->all(),
        ];
    }

    private function resolveNavigationItemLabel(
        mixed $label,
        string $locale,
        string $fallbackLocale,
    ): string {
        if (is_string($label)) {
            $decoded = json_decode($label, true);

            if (is_array($decoded)) {
                $resolved = $this->localizedContentResolver->resolveLocalizedField(
                    $decoded,
                    $locale,
                    $fallbackLocale,
                );

                return is_string($resolved) && $resolved !== ''
                    ? $resolved
                    : $label;
            }

            return $label;
        }

        $resolved = $this->localizedContentResolver->resolveLocalizedField(
            $label,
            $locale,
            $fallbackLocale,
        );

        return is_string($resolved) ? $resolved : '';
    }

    private function isExternalNavigationPath(string $path, string $type): bool
    {
        return $type === 'external'
            || str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
            || str_starts_with($path, '//');
    }

    /**
     * @return array<string, mixed>
     */
    public function themePayload(): array
    {
        $store = $this->currentStore();
        $locale = $this->resolvedLocaleFromRequest();
        $path = $this->resolvedPathFromRequest();
        $previewContext = $this->previewContextForRequest($store, $path, $locale);
        $ttl = (int) config('storefront_runtime.cache_ttl.theme', 3600);

        $data = $this->cacheService->remember(
            store: $store,
            locale: $locale,
            artifact: 'theme',
            path: $path,
            ttlSeconds: $ttl,
            bypass: $previewContext !== null,
            callback: fn (): array => $this->resolveThemeDataFromDatabase($store, $locale),
        );

        $this->runtimeLogger->info('runtime.theme.loaded', [
            'artifact' => 'theme',
            'status' => 'success',
            'path' => $path,
        ]);

        return $this->responseFactory->success(
            store: $store,
            locale: $locale,
            path: $path,
            preview: $previewContext !== null,
            data: $data,
            cache: $this->responseFactory->cache(
                store: $store,
                locale: $locale,
                artifact: 'theme',
                path: $path,
                ttlSeconds: $ttl,
                bypassed: $previewContext !== null,
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function validatePreview(string $token, string $pageId, string $path, string $locale): array
    {
        $store = $this->currentStore();
        $locale = $this->resolveLocale($locale);
        $path = $this->normalizedPath($path);

        if ($token === '') {
            throw new RuntimeContractException(
                runtimeCode: 'runtime.preview_invalid',
                httpStatus: 403,
                message: 'The preview token is invalid for the requested tenant and page.',
                details: ['pageId' => $pageId, 'reason' => 'token_missing'],
            );
        }

        $validated = $this->previewTokenService->validate($store, $token, $pageId, $path, $locale);

        $this->runtimeLogger->info('runtime.preview.validated', [
            'artifact' => 'preview',
            'status' => 'bypassed',
            'path' => $path,
        ]);

        return $this->responseFactory->success(
            store: $store,
            locale: $locale,
            path: $path,
            preview: true,
            data: [
                'valid' => true,
                'previewState' => 'authorized',
                'pageId' => $validated['pageId'],
                'expiresAt' => $validated['expiresAt'],
                'cacheBypass' => true,
            ],
            cache: null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function errorPayload(RuntimeContractException $exception): array
    {
        return $this->responseFactory->errorPayload($exception);
    }

    /**
     * @return array<string, mixed>
     */
    public function unexpectedErrorPayload(Throwable $exception): array
    {
        return $this->responseFactory->unexpectedErrorPayload([
            'exception' => $exception::class,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveRouteData(
        Store $store,
        string $path,
        string $lookupPath,
        string $locale,
        ?array $previewContext = null,
    ): array
    {
        // Auth routes - resolved through template system
        $authRoutes = ['/login', '/register', '/forgot-password', '/reset-password', '/verify-email'];
        if (in_array($lookupPath, $authRoutes, true)) {
            $slug = ltrim($lookupPath, '/');

            return [
                'status' => 'matched',
                'routeType' => 'auth_page',
                'pageId' => 'auth_' . $slug,
                'resourceType' => 'auth',
                'resourceId' => $slug,
                'path' => $path,
                'locale' => $locale,
                'layout' => 'auth',
                'legacyPassthrough' => false,
            ];
        }

        if ($this->isLegacyPassthrough($lookupPath)) {
            return [
                'status' => 'matched',
                'routeType' => 'marketing_page',
                'pageId' => null,
                'resourceType' => 'none',
                'resourceId' => null,
                'path' => $path,
                'locale' => $locale,
                'layout' => null,
                'legacyPassthrough' => true,
            ];
        }

        if ($lookupPath === '/') {
            $homePage = StoreMarketingPage::query()
                ->where('store_id', $store->id)
                ->published()
                ->where(fn ($q) => $q->where('slug->en', 'home')->orWhere('slug->ar', 'home'))
                ->first();

            if ($homePage instanceof StoreMarketingPage) {
                return [
                    'status' => 'matched',
                    'routeType' => 'marketing_page',
                    'pageId' => 'mkt_' . $homePage->id,
                    'resourceType' => 'page',
                    'resourceId' => 'mkt_' . $homePage->id,
                    'path' => $path,
                    'locale' => $locale,
                    'layout' => 'marketing',
                    'legacyPassthrough' => false,
                ];
            }

            return [
                'status' => 'matched',
                'routeType' => 'home',
                'pageId' => 'home',
                'resourceType' => 'page',
                'resourceId' => 'home',
                'path' => $path,
                'locale' => $locale,
                'layout' => 'default',
                'legacyPassthrough' => false,
            ];
        }

        if ($lookupPath === '/shop') {
            return [
                'status' => 'matched',
                'routeType' => 'shop_page',
                'pageId' => 'shop',
                'resourceType' => 'page',
                'resourceId' => 'shop',
                'path' => $path,
                'locale' => $locale,
                'layout' => 'catalog',
                'legacyPassthrough' => false,
            ];
        }

        if (preg_match('#^/shop/category/(?P<slug>[^/]+)$#', $lookupPath, $matches) === 1) {
            $category = Category::findByLocalizedSlug($matches['slug'], $store->id, $locale);

            if ($category instanceof Category) {
                return [
                    'status' => 'matched',
                    'routeType' => 'category_page',
                    'pageId' => 'cat_' . $category->id,
                    'resourceType' => 'category',
                    'resourceId' => (string) $category->id,
                    'path' => $path,
                    'locale' => $locale,
                    'layout' => 'catalog',
                    'legacyPassthrough' => false,
                ];
            }

            return $this->notFoundRoute($path, $locale, 'category_page');
        }

        if (preg_match('#^/shop/product/(?P<slug>[^/]+)$#', $lookupPath, $matches) === 1) {
            $product = Product::query()
                ->where('store_id', $store->id)
                ->active()
                ->findBySlug($matches['slug'], $locale)
                ->first();

            if ($product instanceof Product) {
                return [
                    'status' => 'matched',
                    'routeType' => 'product_page',
                    'pageId' => 'prd_' . $product->id,
                    'resourceType' => 'product',
                    'resourceId' => (string) $product->id,
                    'path' => $path,
                    'locale' => $locale,
                    'layout' => 'product',
                    'legacyPassthrough' => false,
                ];
            }

            return $this->notFoundRoute($path, $locale, 'product_page');
        }

        $slug = ltrim($lookupPath, '/');
        $page = StoreMarketingPage::query()
            ->where('store_id', $store->id)
            ->published()
            ->where(function ($query) use ($locale, $slug): void {
                $query->where("slug->{$locale}", $slug)
                    ->orWhere("slug->" . config('app.fallback_locale', 'en'), $slug);
            })
            ->first();

        if ($page instanceof StoreMarketingPage) {
            return [
                'status' => 'matched',
                'routeType' => 'marketing_page',
                'pageId' => 'mkt_' . $page->id,
                'resourceType' => 'page',
                'resourceId' => 'mkt_' . $page->id,
                'path' => $path,
                'locale' => $locale,
                'layout' => 'marketing',
                'legacyPassthrough' => false,
            ];
        }

        if ($previewContext !== null && ($previewRoute = $this->resolvePreviewMarketingRoute($store, $path, $locale, $previewContext))) {
            return $previewRoute;
        }

        return $this->notFoundRoute($path, $locale, 'marketing_page');
    }

    /**
     * @return array{path: string, payload: array<string, mixed>}
     */
    private function resolvePageResource(Store $store, string $pageId, bool $preview, string $locale): array
    {
        if ($pageId === 'home') {
            return [
                'path' => '/',
                'payload' => $this->buildHomePagePayload($store, $locale),
            ];
        }

        if ($pageId === 'shop') {
            return [
                'path' => $this->shopPath($locale),
                'payload' => $this->buildShopPagePayload($store, $locale),
            ];
        }

        if (str_starts_with($pageId, 'mkt_')) {
            $page = $this->marketingPage((int) substr($pageId, 4), $store->id, $preview);

            return [
                'path' => $this->localizedStorefrontPath($page, $locale),
                'payload' => $this->buildMarketingPagePayload($page, $store, $locale, $pageId, $preview),
            ];
        }

        if (str_starts_with($pageId, 'cat_')) {
            $category = $this->category((int) substr($pageId, 4), $store->id);

            return [
                'path' => $this->categoryPath($category, $locale),
                'payload' => $this->buildCategoryPagePayload($category, $store, $locale, $pageId),
            ];
        }

        if (str_starts_with($pageId, 'prd_')) {
            $product = $this->product((int) substr($pageId, 4), $store->id);

            return [
                'path' => $this->productPath($product, $locale),
                'payload' => $this->buildProductPagePayload($product, $store, $locale, $pageId),
            ];
        }

        if (str_starts_with($pageId, 'auth_')) {
            $type = substr($pageId, 5);

            return [
                'path' => '/' . $type,
                'payload' => $this->buildAuthPagePayload($store, $locale, $type),
            ];
        }

        throw new RuntimeContractException(
            runtimeCode: 'runtime.page_not_found',
            httpStatus: 404,
            message: 'The storefront runtime page payload could not be resolved.',
            details: ['pageId' => $pageId, 'reason' => 'page_id_not_mapped'],
        );
    }

    private function marketingPage(int $pageKey, int $storeId, bool $preview): StoreMarketingPage
    {
        $query = StoreMarketingPage::query()
            ->where('store_id', $storeId)
            ->with('sections')
            ->whereKey($pageKey);

        if (!$preview) {
            $query->published();
        }

        $page = $query->first();

        if (!$page instanceof StoreMarketingPage) {
            throw new RuntimeContractException(
                runtimeCode: 'runtime.page_not_found',
                httpStatus: 404,
                message: 'The storefront runtime page payload could not be resolved.',
                details: ['pageId' => 'mkt_' . $pageKey, 'reason' => 'marketing_page_missing'],
            );
        }

        return $page;
    }

    private function category(int $categoryId, int $storeId): Category
    {
        $category = $this->categoryRepository->findById($categoryId, $storeId);

        if (!$category instanceof Category || !$category->is_active) {
            throw new RuntimeContractException(
                runtimeCode: 'runtime.page_not_found',
                httpStatus: 404,
                message: 'The storefront runtime page payload could not be resolved.',
                details: ['pageId' => 'cat_' . $categoryId, 'reason' => 'category_missing'],
            );
        }

        $category->loadMissing(['translations', 'children.translations', 'parents.translations']);

        return $category;
    }

    private function product(int $productId, int $storeId): Product
    {
        $product = Product::query()
            ->where('store_id', $storeId)
            ->whereKey($productId)
            ->active()
            ->with([
                'translations',
                'category.translations',
                'brand',
                'activeVariants.optionValues.option',
                'activeVariants.images',
                'defaultVariant.images',
                'images',
            ])
            ->first();

        if (!$product instanceof Product) {
            throw new RuntimeContractException(
                runtimeCode: 'runtime.page_not_found',
                httpStatus: 404,
                message: 'The storefront runtime page payload could not be resolved.',
                details: ['pageId' => 'prd_' . $productId, 'reason' => 'product_missing'],
            );
        }

        return $product;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMarketingPagePayload(
        StoreMarketingPage $page,
        Store $store,
        string $locale,
        string $pageId,
        bool $preview,
    ): array {
        $fallbackLocale = (string) config('app.fallback_locale', 'en');
        $slug = (string) $this->localizedContentResolver->resolveLocalizedField($page->slug, $locale, $fallbackLocale);
        $title = (string) $this->localizedContentResolver->resolveLocalizedField($page->title, $locale, $fallbackLocale);
        $excerpt = (string) $this->localizedContentResolver->resolveLocalizedField($page->excerpt, $locale, $fallbackLocale);
        $resolvedSeo = $this->seoResolutionService->resolve(
            seo: $page->getSeoMetadata(),
            locale: $locale,
            fallback: $fallbackLocale,
            slugMap: is_array($page->slug) ? $page->slug : [],
            routePrefix: '',
            isPublished: $preview ? false : $page->isPublished(),
            entityData: [
                'title' => $title,
                'excerpt' => $excerpt,
                'url' => $this->tenantUrl($store, $this->localizedStorefrontPath($page, $locale)),
            ],
        );

        // Resolve template with sections
        $resolvedTemplate = $this->templateResolutionService->resolveTemplate($page);
        $templateData = null;

        if ($resolvedTemplate) {
            $sectionsData = [];
            
            foreach ($resolvedTemplate->sectionOrder as $sectionId) {
                if ($resolvedTemplate->hasSection($sectionId)) {
                    $sectionType = $resolvedTemplate->getSectionType($sectionId);
                    $sectionSettings = $resolvedTemplate->getSectionSettings($sectionId);
                    
                    $sectionsData[$sectionId] = $this->sectionDataResolverService->resolveSectionData(
                        sectionType: $sectionType,
                        settings: $sectionSettings,
                        store: $store,
                        locale: $locale,
                        page: $page
                    );
                }
            }

            $templateData = [
                'id' => $resolvedTemplate->id,
                'handle' => $resolvedTemplate->handle,
                'sections' => $sectionsData,
                'section_order' => $resolvedTemplate->sectionOrder,
            ];
        }

        $mappedSections = $this->mapMarketingSections($page, $locale);

        if ($resolvedTemplate && !empty($resolvedTemplate->sectionOrder)) {
            $pageSectionsById = [];
            foreach ($mappedSections as $s) {
                $pageSectionsById[$s['id']] = $s;
            }

            $orderedSections = [];
            $contentInserted = false;

            foreach ($resolvedTemplate->sectionOrder as $sectionId) {
                $sectionType = $resolvedTemplate->getSectionType($sectionId);

                if ($sectionType === 'page_content' && !$contentInserted) {
                    array_push($orderedSections, ...$mappedSections);
                    $contentInserted = true;
                } elseif (isset($pageSectionsById[$sectionId])) {
                    $orderedSections[] = $pageSectionsById[$sectionId];
                }
            }

            if (!$contentInserted) {
                array_push($orderedSections, ...$mappedSections);
            }

            $mappedSections = $orderedSections;
        }

        $payload = [
            'id' => $pageId,
            'page_template_id' => $page->page_template_id,
            'pageType' => 'marketing_page',
            'title' => $title,
            'slug' => ltrim($slug, '/'),
            'locale' => $locale,
            'layout' => 'marketing',
            'layout_order' => ['header', 'content', 'footer'],
            'status' => $preview && !$page->isPublished() ? 'draft' : 'published',
            'sections' => $mappedSections,
            'seo' => $this->mapSeoPayload($store, $resolvedSeo, 'website', $this->localizedStorefrontPath($page, $locale)),
            'publishedAt' => $page->published_at?->toIso8601String(),
            'updatedAt' => $page->updated_at?->toIso8601String() ?? now()->toIso8601String(),
        ];

        // Add template data if available
        if ($templateData) {
            $payload['template'] = $templateData;
        }

        return $this->addChromeToPayload($payload, $store, TemplateTypeEnum::HOME);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAuthPagePayload(Store $store, string $locale, string $type): array
    {
        $resolvedTemplate = $this->templateResolutionService->resolveSystemPageTemplate($store->id, 'auth');
        $templateData = null;

        if ($resolvedTemplate) {
            $sectionsData = [];

            foreach ($resolvedTemplate->sectionOrder as $sectionId) {
                if ($resolvedTemplate->hasSection($sectionId)) {
                    $sectionType = $resolvedTemplate->getSectionType($sectionId);
                    $sectionSettings = $resolvedTemplate->getSectionSettings($sectionId);

                    $sectionsData[$sectionId] = $this->sectionDataResolverService->resolveSectionData(
                        sectionType: $sectionType,
                        settings: $sectionSettings,
                        store: $store,
                        locale: $locale,
                        page: null,
                    );
                }
            }

            $templateData = [
                'id' => $resolvedTemplate->id,
                'handle' => $resolvedTemplate->handle,
                'sections' => $sectionsData,
                'section_order' => $resolvedTemplate->sectionOrder,
            ];
        }

        $title = match ($type) {
            'login' => $locale === 'ar' ? 'تسجيل الدخول' : 'Login',
            'register' => $locale === 'ar' ? 'إنشاء حساب' : 'Create Account',
            'forgot-password' => $locale === 'ar' ? 'نسيت كلمة المرور' : 'Forgot Password',
            'reset-password' => $locale === 'ar' ? 'إعادة تعيين كلمة المرور' : 'Reset Password',
            'verify-email' => $locale === 'ar' ? 'تأكيد البريد الإلكتروني' : 'Verify Email',
            default => $locale === 'ar' ? 'حسابي' : 'My Account',
        };

        $payload = [
            'id' => 'auth_' . $type,
            'pageType' => 'auth_page',
            'title' => $title,
            'slug' => $type,
            'locale' => $locale,
            'layout' => 'auth',
            'status' => 'published',
            'sections' => [],
            'seo' => $this->buildBasicSeo(
                store: $store,
                path: '/' . $type,
                title: $title,
                description: $title,
                openGraphType: 'website',
            ),
            'publishedAt' => null,
            'updatedAt' => now()->toIso8601String(),
        ];

        if ($templateData) {
            $payload['template'] = $templateData;
        }

        $templateType = match ($type) {
            'login' => TemplateTypeEnum::LOGIN,
            'register' => TemplateTypeEnum::REGISTER,
            'forgot-password' => TemplateTypeEnum::FORGOT_PASSWORD,
            'reset-password' => TemplateTypeEnum::RESET_PASSWORD,
            'verify-email' => TemplateTypeEnum::VERIFY_EMAIL,
            default => TemplateTypeEnum::ACCOUNT,
        };

        return $this->addChromeToPayload($payload, $store, $templateType);
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    private function buildShopPagePayload(Store $store, string $locale): array
    {
        $path = $this->shopPath($locale);
        $title = $locale === 'ar' ? 'تسوق' : 'Shop';

        $payload = [
            'id' => 'shop',
            'pageType' => 'shop_page',
            'title' => $title,
            'slug' => 'shop',
            'locale' => $locale,
            'layout' => 'catalog',
            'status' => 'published',
            'sections' => [[
                'id' => 'shop_products',
                'type' => 'shop_grid',
                'component' => 'ShopGridSection',
                'props' => [
                    'categorySlug' => null,
                    'storeId' => $store->id,
                ],
                'version' => '1',
                'dataState' => 'ready',
            ]],
            'seo' => $this->buildBasicSeo(
                store: $store,
                path: $path,
                title: $title,
                description: $store->name . ' catalog',
                openGraphType: 'website',
            ),
            'publishedAt' => null,
            'updatedAt' => now()->toIso8601String(),
        ];

        return $this->addChromeToPayload($payload, $store, TemplateTypeEnum::SHOP);
    }

    private function buildHomePagePayload(Store $store, string $locale): array
    {
        $categoryGrid = $this->mapCategoryGridItems($store, $locale);
        $featuredProducts = $this->mapFeaturedProductsForStore($store, $locale);

        // Check if store has a designated homepage CMS page
        $homePage = StoreMarketingPage::query()
            ->where('store_id', $store->id)
            ->where('is_homepage', true)
            ->where('status', 'published')
            ->with('sections')
            ->first();

        // If homepage CMS page exists, use it
        if ($homePage) {
            return $this->buildMarketingPagePayload(
                page: $homePage,
                store: $store,
                locale: $locale,
                pageId: 'home',
                preview: false
            );
        }

        // Fallback: default homepage with category grid and featured products
        return $this->addChromeToPayload([
            'id' => 'home',
            'pageType' => 'home',
            'title' => $store->name,
            'slug' => '',
            'locale' => $locale,
            'layout' => 'default',
            'status' => 'published',
            'sections' => [
                [
                    'id' => 'home_categories',
                    'type' => 'category_grid',
                    'component' => 'CategoryGridSection',
                    'props' => [
                        'title' => $locale === 'ar' ? 'تسوق حسب القسم' : 'Shop by department',
                        'subtitle' => $locale === 'ar'
                            ? 'اكتشف آلاف المنتجات عبر أقسام الإلكترونيات والأزياء والمنزل.'
                            : 'Browse electronics, fashion, home, beauty, and sports in one place.',
                        'categories' => $categoryGrid,
                    ],
                    'version' => '1',
                    'dataState' => count($categoryGrid) > 0 ? 'ready' : 'empty',
                ],
                [
                    'id' => 'home_featured',
                    'type' => 'product_grid',
                    'component' => 'ProductGridSection',
                    'props' => [
                        'title' => $locale === 'ar' ? 'منتجات مميزة' : 'Featured picks',
                        'subtitle' => $locale === 'ar'
                            ? 'أفضل العروض المختارة لهذا الأسبوع.'
                            : 'Hand-picked bestsellers and new arrivals for this week.',
                        'products' => $featuredProducts,
                    ],
                    'version' => '1',
                    'dataState' => count($featuredProducts) > 0 ? 'ready' : 'empty',
                ],
            ],
            'seo' => $this->buildBasicSeo(
                store: $store,
                path: '/',
                title: $store->name,
                description: $store->name . ' storefront homepage',
                openGraphType: 'website',
            ),
            'publishedAt' => null,
            'updatedAt' => now()->toIso8601String(),
        ], $store, TemplateTypeEnum::HOME);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCategoryPagePayload(Category $category, Store $store, string $locale, string $pageId): array
    {
        $translation = $category->translation($locale);
        $title = (string) ($translation?->name ?? $category->slug);
        $path = $this->categoryPath($category, $locale);
        $categorySlug = (string) ($translation?->slug ?? $category->slug);

        return $this->addChromeToPayload([
            'id' => $pageId,
            'pageType' => 'category_page',
            'title' => $title,
            'slug' => $categorySlug,
            'locale' => $locale,
            'layout' => 'catalog',
            'status' => 'published',
            'sections' => [
                [
                    'id' => 'category_summary_' . $category->id,
                    'type' => 'category_summary',
                    'component' => 'CategorySummarySection',
                    'props' => [
                        'categoryId' => $category->id,
                        'name' => $title,
                        'slug' => $categorySlug,
                        'breadcrumb' => $category->breadcrumb->values()->all(),
                    ],
                    'version' => '1',
                    'dataState' => 'ready',
                ],
                [
                    'id' => 'category_products_' . $category->id,
                    'type' => 'shop_grid',
                    'component' => 'ShopGridSection',
                    'props' => [
                        'categorySlug' => $categorySlug,
                        'storeId' => $store->id,
                    ],
                    'version' => '1',
                    'dataState' => 'ready',
                ],
            ],
            'seo' => $this->buildBasicSeo(
                store: $store,
                path: $path,
                title: $title,
                description: $title . ' category page',
                openGraphType: 'website',
            ),
            'publishedAt' => null,
            'updatedAt' => $category->updated_at?->toIso8601String() ?? now()->toIso8601String(),
        ], $store, TemplateTypeEnum::CATEGORY);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildProductPagePayload(Product $product, Store $store, string $locale, string $pageId): array
    {
        $translation = $product->translation($locale);
        $title = (string) ($translation?->name ?? 'Product');
        $path = $this->productPath($product, $locale);

        $activeVariants = $product->activeVariants;
        $allImages = $this->mapProductGallery($product);
        
        $attributes = [];
        foreach ($activeVariants as $variant) {
            foreach ($variant->optionValues as $optionValue) {
                $optionName = $optionValue->option->name;
                $attributes[$optionName][] = $optionValue->value;
            }
        }
        foreach ($attributes as $key => $values) {
            $attributes[$key] = array_values(array_unique($values));
        }

        return $this->addChromeToPayload([
            'id' => $pageId,
            'pageType' => 'product_page',
            'title' => $title,
            'slug' => (string) ($translation?->slug ?? ''),
            'locale' => $locale,
            'layout' => 'product',
            'status' => 'published',
            'sections' => [[
                'id' => 'product_summary_' . $product->id,
                'type' => 'product_summary',
                'component' => 'ProductSummarySection',
                'props' => [
                    'productId' => $product->id,
                    'productVariantId' => $product->primaryVariant()?->id,
                    'name' => $title,
                    'slug' => (string) ($translation?->slug ?? ''),
                    'description' => (string) ($translation?->description ?? ''),
                    'price' => $product->primaryVariant()?->price,
                    'primaryImage' => $product->primary_image_url,
                    'maxQuantity' => $product->primaryVariant()?->quantity,
                    'brand' => $product->brand ? [
                        'id' => $product->brand->id,
                        'name' => $product->brand->name,
                        'slug' => $product->brand->slug,
                    ] : null,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->translation($locale)?->name ?? $product->category->slug,
                        'slug' => $product->category->translation($locale)?->slug ?? $product->category->slug,
                    ] : null,
                    'attributes' => $attributes,
                    'images' => $allImages,
                    'variants' => $activeVariants->map(fn ($v) => [
                        'id' => $v->id,
                        'sku' => $v->sku,
                        'price' => $v->price,
                        'stock' => $v->quantity,
                        'is_active' => $v->is_active ? 1 : 0,
                        'attribute_map' => $v->optionValues->mapWithKeys(fn ($ov) => [$ov->option->name => $ov->value])->all(),
                        'images' => $v->images->map(fn ($img) => [
                            'id' => $img->id,
                            'url' => $img->full_url,
                            'alt_text' => $img->alt_text,
                            'is_primary' => $img->is_primary ? 1 : 0,
                        ])->all(),
                    ])->all(),
                ],
                'version' => '1',
                'dataState' => 'ready',
            ]],
            'seo' => $this->buildBasicSeo(
                store: $store,
                path: $path,
                title: (string) ($translation?->seo_title ?: $title),
                description: (string) ($translation?->seo_description ?: $translation?->description ?: $title),
                openGraphType: 'product',
            ),
            'publishedAt' => null,
            'updatedAt' => $product->updated_at?->toIso8601String() ?? now()->toIso8601String(),
        ], $store, TemplateTypeEnum::PRODUCT);
    }

    private function mapProductGallery(Product $product): array
    {
        $images = collect();

        // 1. Product-level images
        if ($product->relationLoaded('images')) {
            foreach ($product->images as $img) {
                $images->push([
                    'id' => $img->id,
                    'url' => $img->full_url,
                    'alt_text' => $img->alt_text,
                    'is_primary' => $img->is_primary ? 1 : 0,
                ]);
            }
        }

        // 2. Variant-level images (if product images are empty)
        if ($images->isEmpty() && $product->relationLoaded('activeVariants')) {
            foreach ($product->activeVariants as $variant) {
                if ($variant->relationLoaded('images')) {
                    foreach ($variant->images as $img) {
                        $images->push([
                            'id' => $img->id,
                            'url' => $img->full_url,
                            'alt_text' => $img->alt_text,
                            'is_primary' => $img->is_primary ? 1 : 0,
                        ]);
                    }
                }
            }
        }

        return $images->unique('url')->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveNavigationData(Store $store, string $locale): array
    {
        $shopPath = $this->shopPath($locale);
        $shopChildren = $this->rootCategoriesForStore($store, $locale)
            ->map(function (Category $category) use ($locale): array {
                $translation = $category->translation($locale);

                return [
                    'id' => 'nav_shop_' . $category->id,
                    'label' => (string) ($translation?->name ?? $category->slug),
                    'path' => $this->categoryPath($category, $locale),
                    'external' => false,
                    'children' => [],
                ];
            })
            ->values()
            ->all();

        $header = [
            [
                'id' => 'nav_home',
                'label' => $locale === 'ar' ? 'الرئيسية' : 'Home',
                'path' => $locale === 'ar' ? '/ar' : '/',
                'external' => false,
                'children' => [],
            ],
            [
                'id' => 'nav_shop',
                'label' => $locale === 'ar' ? 'تسوق' : 'Shop',
                'path' => $shopPath,
                'external' => false,
                'children' => $shopChildren,
            ],
        ];

        $marketingPages = StoreMarketingPage::query()
            ->where('store_id', $store->id)
            ->published()
            ->orderBy('sort_order')
            ->get();

        foreach ($marketingPages as $marketingPage) {
            $path = $this->localizedStorefrontPath($marketingPage, $locale);

            if ($path === '/' || $path === '/ar') {
                continue;
            }

            $label = (string) $this->localizedContentResolver->resolveLocalizedField(
                $marketingPage->title,
                $locale,
                (string) config('app.fallback_locale', 'en'),
            );

            $header[] = [
                'id' => 'nav_page_' . $marketingPage->id,
                'label' => $label,
                'path' => $path,
                'external' => false,
                'children' => [],
            ];
        }

        $footerPage = $marketingPages->first();
        $footerPath = $footerPage instanceof StoreMarketingPage
            ? $this->localizedStorefrontPath($footerPage, $locale)
            : '/about-us';

        return [
            'header' => $header,
            'footer' => [[
                'id' => 'nav_footer_about',
                'label' => $locale === 'ar' ? 'من نحن' : 'About',
                'path' => $footerPath,
                'external' => false,
                'children' => [],
            ]],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mapMarketingSections(StoreMarketingPage $page, string $locale): array
    {
        $fallbackLocale = (string) config('app.fallback_locale', 'en');

        if ($page->relationLoaded('sections') && $page->sections->isNotEmpty()) {
            return $page->sections
                ->where('is_active', true)
                ->values()
                ->map(function ($section, int $index) use ($locale, $fallbackLocale): array {
                    $type = (string) $section->section_type;
                    $title = $this->localizedContentResolver->resolveLocalizedField($section->title, $locale, $fallbackLocale);
                    $subtitle = $this->localizedContentResolver->resolveLocalizedField($section->subtitle, $locale, $fallbackLocale);
                    $content = $this->localizedContentResolver->resolveLocalizedPayload($section->content, $locale, $fallbackLocale);
                    $settings = $this->localizedContentResolver->resolveLocalizedPayload($section->settings, $locale, $fallbackLocale);
                    
                    // Extract items from section model (for hero banners with gradient/image data)
                    $items = $this->localizedContentResolver->resolveLocalizedPayload($section->items ?? [], $locale, $fallbackLocale);

                    // Special handling for feature_list sections: extract locale-specific array if content wasn't resolved
                    if (in_array($type, ['features', 'feature_list'], true) && is_array($content)) {
                        if (isset($content[$locale]) && is_array($content[$locale])) {
                            $content = $content[$locale];
                        } elseif (isset($content[$fallbackLocale]) && is_array($content[$fallbackLocale])) {
                            $content = $content[$fallbackLocale];
                        }
                    }

                    // Transform relative image URLs to absolute URLs
                    $content = $this->transformImageUrls($content);
                    $items = $this->transformImageUrls($items);

                    $props = $this->buildSectionProps($type, $title, $subtitle, $content, $settings, $items);
                    
                    // Transform props again in case buildSectionProps wrapped content
                    $props = $this->transformImageUrls($props);

                    return [
                        'id' => $section->identifier ?: ('section_' . $section->id . '_' . $index),
                        'type' => $this->normalizeSectionType($type),
                        'component' => $this->componentForSection($type),
                        'props' => $props,
                        'version' => '1',
                        'dataState' => 'ready',
                    ];
                })
                ->all();
        }

        if (is_array($page->content) && array_is_list($page->content)) {
            return collect($page->content)
                ->values()
                ->map(function (array $section, int $index) use ($locale, $fallbackLocale): array {
                    $type = (string) ($section['type'] ?? $section['section_type'] ?? 'custom');
                    $props = $this->localizedContentResolver->resolveLocalizedPayload($section, $locale, $fallbackLocale);
                    
                    // Transform relative image URLs to absolute URLs
                    $props = $this->transformImageUrls($props);

                    return [
                        'id' => (string) ($section['identifier'] ?? $section['id'] ?? 'section_' . $index),
                        'type' => $this->normalizeSectionType($type),
                        'component' => $this->componentForSection($type),
                        'props' => is_array($props) ? $props : ['value' => $props],
                        'version' => '1',
                        'dataState' => empty($props) ? 'empty' : 'ready',
                    ];
                })
                ->all();
        }

        return [[
            'id' => 'page_content',
            'type' => 'custom',
            'component' => 'RuntimeFallbackSection',
            'props' => [
                'content' => $this->transformImageUrls($this->localizedContentResolver->resolveLocalizedPayload($page->content, $locale, $fallbackLocale)),
            ],
            'version' => '1',
            'dataState' => empty($page->content) ? 'empty' : 'ready',
        ]];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mapCategoryGridItems(Store $store, string $locale): array
    {
        return $this->rootCategoriesForStore($store, $locale)
            ->map(function (Category $category) use ($store, $locale): array {
                $translation = $category->translation($locale);

                return [
                    'id' => $category->id,
                    'name' => (string) ($translation?->name ?? $category->slug),
                    'slug' => (string) ($translation?->slug ?? $category->slug),
                    'path' => $this->categoryPath($category, $locale),
                    'productCount' => $this->productCountForCategoryBranch($store, $category),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Category>
     */
    private function rootCategoriesForStore(Store $store, string $locale): \Illuminate\Support\Collection
    {
        return Category::query()
            ->where('store_id', $store->id)
            ->whereNull('parent_id')
            ->active()
            ->with('translations')
            ->orderBy('sort_order')
            ->get();
    }

    private function shopPath(string $locale): string
    {
        return $locale === 'ar' ? '/ar/shop' : '/shop';
    }

    private function productCountForCategoryBranch(Store $store, Category $category): int
    {
        $category->loadMissing('descendants');

        return Product::query()
            ->where('store_id', $store->id)
            ->active()
            ->whereIn('category_id', $category->allDescendantIds())
            ->count();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mapRuntimeProductCardsForCategory(Store $store, Category $category, string $locale): array
    {
        $category->loadMissing('descendants');
        $categoryIds = $category->allDescendantIds();
        $previousLocale = app()->getLocale();

        app()->setLocale($locale);

        try {
            $products = Product::query()
                ->where('store_id', $store->id)
                ->whereIn('category_id', $categoryIds)
                ->active()
                ->with(['translations', 'defaultVariant', 'images'])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->limit((int) config('storefront_runtime.category_product_limit', 48))
                ->get();
        } finally {
            app()->setLocale($previousLocale);
        }

        return $this->mapRuntimeProductCards($products, $store, $locale);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mapFeaturedProductsForStore(Store $store, string $locale): array
    {
        $limit = (int) config('storefront_runtime.home_featured_product_limit', 8);
        $previousLocale = app()->getLocale();

        app()->setLocale($locale);

        try {
            $featured = Product::query()
                ->where('store_id', $store->id)
                ->active()
                ->where('is_featured', true)
                ->with(['translations', 'defaultVariant', 'images'])
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->limit($limit)
                ->get();

            if ($featured->count() < min(4, $limit)) {
                $featured = Product::query()
                    ->where('store_id', $store->id)
                    ->active()
                    ->with(['translations', 'defaultVariant.images', 'images'])
                    ->orderBy('sort_order')
                    ->orderByDesc('id')
                    ->limit($limit)
                    ->get();
            }
        } finally {
            app()->setLocale($previousLocale);
        }

        return $this->mapRuntimeProductCards($featured, $store, $locale);
    }

    /**
     * @param \Illuminate\Support\Collection<int, Product>|\Illuminate\Database\Eloquent\Collection<int, Product> $products
     * @return array<int, array<string, mixed>>
     */
    private function mapRuntimeProductCards($products, Store $store, string $locale): array
    {
        return $products->map(fn (Product $product): array => $this->mapRuntimeProductCard($product, $store, $locale))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapRuntimeProductCard(Product $product, Store $store, string $locale): array
    {
        $translation = $product->translation($locale);
        $variant = $product->primaryVariant();

        return [
            'id' => $product->id,
            'variantId' => $variant?->id ?? 0,
            'name' => (string) ($translation?->name ?? ''),
            'slug' => (string) ($translation?->slug ?? ''),
            'image' => $this->resolvePublicImageUrl((string) ($product->primary_image_url ?? '')),
            'price' => (float) ($variant?->price ?? 0),
            'currency' => (string) ($store->currency ?? 'USD'),
            'description' => (string) ($translation?->description ?? ''),
            'categoryId' => $product->category_id,
        ];
    }

    /**
     * Inject header section data (with blocks) from the active theme's HOME template
     * into the page payload. Block image URLs are resolved to absolute paths.
     */
    private function injectChromeHeaderSection(array $payload, Store $store): array
    {
        $activeTheme = $this->themeRepository->getActiveForStore($store->id);
        if ($activeTheme) {
            $chromeTemplate = ThemeTemplate::where('theme_id', $activeTheme->id)
                ->where('type', TemplateTypeEnum::HOME)
                ->with(['sections' => fn ($q) => $q->where('type', 'header'), 'sections.blocks'])
                ->first();

            if ($chromeTemplate && $chromeTemplate->sections->isNotEmpty()) {
                $header = $chromeTemplate->sections->first();
                $payload['themeHeaderSection'] = [
                    'id' => (string) $header->id,
                    'type' => $header->type?->value ?? (string) $header->type,
                    'settings' => $this->resolveBlockImageUrls($header->settings ?? []),
                    'blocks' => $header->blocks->map(fn ($b) => [
                        'id' => (string) $b->id,
                        'type' => $b->type?->value ?? (string) $b->type,
                        'name' => $b->name,
                        'is_enabled' => (bool) ($b->is_enabled ?? true),
                        'settings' => tap(
                            $this->resolveBlockImageUrls($b->settings ?? []),
                            function (array &$settings) use ($b) {
                                if ($b->type === BlockTypeEnum::LOGO && empty($settings['logo_url'])) {
                                    $settings['logo_url'] = Storage::disk('public')->url('cms/default-logo.png');
                                }
                            }
                        ),
                        'content' => $this->resolveBlockImageUrls($b->content ?? []),
                        'position' => (int) $b->position,
                    ])->values()->toArray(),
                ];
            }
        }

        return $payload;
    }

    /**
     * Add default chrome sections (layout_order + themeHeaderSection) to any page payload.
     * Checks the given template type for header/footer enabled/disabled state.
     */
    private function addChromeToPayload(array $payload, Store $store, ?TemplateTypeEnum $templateType = null): array
    {
        $payload['layout_order'] ??= ['header', 'content', 'footer'];
        $locale = is_string($payload['locale'] ?? null)
            ? (string) $payload['locale']
            : $this->resolvedLocaleFromRequest();

        $activeTheme = $this->themeRepository->getActiveForStore($store->id);
        if ($activeTheme) {
            // First try to use SHOP template for consistent chrome across all pages
            $template = ThemeTemplate::where('theme_id', $activeTheme->id)
                ->where('type', TemplateTypeEnum::SHOP)
                ->with(['sections' => fn ($q) => $q->orderBy('position')])
                ->first();
            
            // If no SHOP template, fall back to original template type
            if (!$template && $templateType) {
                $template = ThemeTemplate::where('theme_id', $activeTheme->id)
                    ->where('type', $templateType)
                    ->with(['sections' => fn ($q) => $q->orderBy('position')])
                    ->first();
            }

            if ($template && $template->sections->isNotEmpty()) {
                $chromeOrder = [];
                $chromeSections = [];
                foreach ($template->sections as $section) {
                    $type = $section->type->value;
                    $enabled = (bool) ($section->pivot->is_enabled ?? true);
                    if ($enabled && in_array($type, ['header', 'announcement_bar', 'content', 'footer', 'copyright_bar'], true)) {
                        $chromeOrder[] = $type;
                        $settings = $this->resolveBlockImageUrls($this->resolveTemplateSectionSettings($section));
                        
                        if (in_array($type, ['announcement_bar', 'copyright_bar'], true)) {
                            // Normalize localized settings like SectionDataResolverService does
                            $fields = $type === 'announcement_bar' 
                                ? ['text', 'offer_text', 'shop_now_text'] 
                                : ['text'];
                                
                            $normalizedSettings = $this->normalizeLocalizedSettings($settings, $fields);
                            $chromeSections[$type] = $this->localizedContentResolver->resolveLocalizedPayload($normalizedSettings, $locale);
                        } else {
                            $chromeSections[$type] = $settings;
                        }
                    }
                }
                if (!empty($chromeOrder)) {
                    $payload['layout_order'] = $chromeOrder;
                }
                if (!empty($chromeSections)) {
                    $payload['chrome_sections'] = $chromeSections;
                }
            }
        }

        return $this->injectChromeHeaderSection($payload, $store);
    }

    /**
     * Merge template pivot overrides into the section's base settings so
     * storefront chrome honors dashboard edits.
     *
     * @return array<string, mixed>
     */
    private function resolveTemplateSectionSettings(object $section): array
    {
        $settings = is_array($section->settings ?? null) ? $section->settings : [];
        $overrides = $section->pivot->overrides ?? [];

        if (is_string($overrides)) {
            $decoded = json_decode($overrides, true);
            $overrides = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($overrides) || $overrides === []) {
            return $settings;
        }

        return array_replace_recursive($settings, $overrides);
    }

    /**
     * Normalize settings to ensure specified fields are in localized {en: '', ar: ''} format.
     *
     * @param array<string, mixed> $settings
     * @param array<string> $fields
     * @return array<string, mixed>
     */
    private function normalizeLocalizedSettings(array $settings, array $fields): array
    {
        foreach ($fields as $field) {
            if (isset($settings[$field]) && is_string($settings[$field])) {
                $settings[$field] = ['en' => $settings[$field], 'ar' => ''];
            }
        }

        return $settings;
    }

    /**
     * Resolve image-like values in a settings/content array to absolute URLs.
     * Any string value that looks like a relative storage path (e.g. "cms/xxx.jpg")
     * is resolved via Storage::url().
     */
    public function resolveBlockImageUrls(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value) && MediaUrl::shouldResolve($value)) {
                $data[$key] = $this->resolvePublicImageUrl($value);
            }
        }
        return $data;
    }

    public function resolvePublicImageUrl(string $path): string
    {
        return (string) MediaUrl::resolve($path);
    }

    /**
     * Recursively transform relative image URLs to absolute URLs in arrays
     * 
     * @param mixed $data
     * @return mixed
     */
    private function transformImageUrls(mixed $data): mixed
    {
        if (!is_array($data)) {
            return $data;
        }

        foreach ($data as $key => $value) {
            // Check for common image URL field names
            if (is_string($key) && in_array($key, ['imageUrl', 'image', 'url', 'src', 'thumbnail', 'avatar', 'icon'], true)) {
                if (is_string($value) && MediaUrl::shouldResolve($value)) {
                    $data[$key] = $this->resolvePublicImageUrl($value);
                }
            }
            // Recursively process nested arrays
            elseif (is_array($value)) {
                $data[$key] = $this->transformImageUrls($value);
            }
        }

        return $data;
    }

    private function normalizeSectionType(string $type): string
    {
        return match ($type) {
            'hero' => 'hero_banner',
            'features' => 'feature_list',
            'category_grid' => 'category_grid',
            default => $type,
        };
    }

    private function componentForSection(string $type): string
    {
        return match ($type) {
            'hero', 'hero_banner'           => 'HeroSection',
            'features', 'feature_list'      => 'FeatureListSection',
            'content', 'rich_text'          => 'ContentSection',
            'cta', 'call_to_action'         => 'CtaSection',
            'category_grid'                 => 'CategoryGridSection',
            'products', 'product_grid'      => 'ProductGridSection',
            'faq'                           => 'FaqSection',
            'gallery'                       => 'GallerySection',
            'video'                         => 'VideoSection',
            'testimonials'                  => 'TestimonialsSection',
            'pricing'                       => 'PricingSection',
            default                         => 'RuntimeFallbackSection',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSectionProps(string $type, ?string $title, ?string $subtitle, ?array $content, ?array $settings, ?array $items = null): array
    {
        $props = array_filter([
            'title' => $title,
            'subtitle' => $subtitle,
        ], static fn (mixed $value): bool => $value !== null);

        if (in_array($type, ['category_grid', 'category_summary'], true)) {
            // If content has hardcoded categories, use them; otherwise resolve dynamically
            if (isset($content['categories']) && is_array($content['categories']) && !empty($content['categories'])) {
                $props['categories'] = $content['categories'];
            } else {
                // Dynamically resolve categories from database with calculated product counts
                $props['categories'] = $this->resolveCategoriesForGrid($settings);
            }
            $props['settings'] = $settings ?? [];
            return $props;
        }

        if (in_array($type, ['product_grid', 'product_summary'], true)) {
            // If content has products already (hand-built), use them
            if (isset($content['products']) && is_array($content['products'])) {
                $props['products'] = $content['products'];
                return $props;
            }

            // Otherwise, resolve product_ids (from CMS)
            if (isset($content['product_ids']) && is_array($content['product_ids'])) {
                $props['products'] = $this->resolveProductsFromIds($content['product_ids']);
                if (isset($settings['columns'])) {
                    $props['settings']['columns'] = $settings['columns'];
                }
                if (isset($settings['style'])) {
                    $props['settings']['style'] = $settings['style'];
                }
                if (isset($settings['show_prices'])) {
                    $props['settings']['show_prices'] = $settings['show_prices'];
                }
                if (isset($settings['show_add_to_cart'])) {
                    $props['settings']['show_add_to_cart'] = $settings['show_add_to_cart'];
                }
                return $props;
            }

            $props['products'] = [];
            return $props;
        }

        // For feature_list sections, the content is already resolved by LocalizedContentResolver
        // and should be an array of items. Pass it as 'items' to match component expectations.
        if ($type === 'feature_list' || $type === 'features') {
            if (is_array($content) && array_is_list($content)) {
                $props['items'] = $content;
            } else {
                $props['items'] = [];
            }
            $props['settings'] = $settings ?? [];
            return $props;
        }

        // For hero_banner sections, use items from section model if present
        if ($type === 'hero' || $type === 'hero_banner') {
            // Priority: $items parameter > content['items'] > empty array
            if (is_array($items) && !empty($items)) {
                $props['items'] = $items;
            } elseif (isset($content['items']) && is_array($content['items'])) {
                $props['items'] = $content['items'];
            } else {
                $props['items'] = [];
            }
            $props['content'] = $content ?? [];
            $props['settings'] = $settings ?? [];
            return $props;
        }

        $props['content'] = $content ?? [];
        $props['settings'] = $settings ?? [];

        return $props;
    }

    /**
     * Resolve product IDs to product DTOs for the storefront.
     * Returns products in the format expected by RuntimeProductGridSection.vue
     */
    private function resolveProductsFromIds(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $store = $this->currentStore();

        // Load products with their primary variants
        $products = Product::query()
            ->where('store_id', $store->id)
            ->whereIn('id', $productIds)
            ->where('status', 'active')
            ->with(['variants' => function ($query) {
                $query->where('is_active', true)
                    ->with('images')
                    ->orderBy('sort_order');
            }])
            ->get();

        return $products->map(function (Product $product) {
            $variant = $product->variants->first();

            if (!$variant) {
                return null;
            }

            return [
                'id' => $product->id,
                'variantId' => $variant->id,
                'product_variant_id' => $variant->id, // Alias for compatibility
                'name' => $product->name,
                'slug' => $product->slug,
                'image' => $variant->primary_image_url ?? '',
                'primary_image' => $variant->primary_image_url ?? '', // Alias
                'price' => $variant->price,
                'currency' => $store->default_currency ?? 'USD',
                'description' => $product->description ?? '',
                'categoryId' => $product->category_id,
                'category_id' => $product->category_id, // Alias
            ];
        })
        ->filter() // Remove null entries (products without variants)
        ->values()
        ->all();
    }

    /**
     * Resolve categories dynamically with calculated product counts for category grid sections.
     * Returns categories in the format expected by RuntimeCategoryGridSection.vue
     */
    private function resolveCategoriesForGrid(?array $settings): array
    {
        $store = $this->currentStore();
        $locale = app()->getLocale();
        $limit = min((int) ($settings['limit'] ?? 8), 24);

        // Get root categories with dynamic product counts
        $categories = $this->categoryRepository
            ->getRootCategories($store->id)
            ->take($limit)
            ->map(function (Category $category) use ($locale): array {
                $translation = $category->translations->firstWhere('locale', $locale);
                $fallbackTranslation = $category->translations->firstWhere('locale', config('app.fallback_locale', 'en'));
                
                $name = $translation?->name ?? $fallbackTranslation?->name ?? $category->slug;
                $slug = $translation?->slug ?? $fallbackTranslation?->slug ?? $category->slug;

                return [
                    'id' => $category->id,
                    'name' => ['en' => $name, 'ar' => $name], // Simplified for now
                    'slug' => $slug,
                    'path' => ['en' => "/shop/category/{$slug}", 'ar' => "/ar/shop/category/{$slug}"],
                    'productCount' => $category->products_count ?? 0, // Dynamic count from repository
                    'image' => null,
                ];
            })
            ->values()
            ->all();

        return $categories;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapSeoPayload(Store $store, object $resolvedSeo, string $openGraphType, string $path): array
    {
        return [
            'title' => (string) ($resolvedSeo->metaTitle ?? ''),
            'description' => (string) ($resolvedSeo->metaDescription ?? ''),
            'canonicalUrl' => $this->tenantUrl($store, $path),
            'robots' => (string) ($resolvedSeo->robots ?? 'index,follow'),
            'hreflang' => collect((array) ($resolvedSeo->alternates ?? []))
                ->reject(fn (string $_, string $locale): bool => $locale === 'x-default')
                ->map(fn (string $url, string $locale): array => [
                    'locale' => $locale,
                    'url' => str_replace((string) config('app.frontend_url'), 'https://' . $store->domain, $url),
                ])
                ->values()
                ->all(),
            'openGraph' => [
                'title' => (string) ($resolvedSeo->ogTitle ?? $resolvedSeo->metaTitle ?? ''),
                'description' => (string) ($resolvedSeo->ogDescription ?? $resolvedSeo->metaDescription ?? ''),
                'type' => $openGraphType,
                'imageUrl' => $resolvedSeo->ogImage ?? null,
            ],
            'twitter' => [
                'card' => (string) (($resolvedSeo->twitterCard ?? 'summary_large_image') ?: 'summary_large_image'),
                'title' => (string) ($resolvedSeo->ogTitle ?? $resolvedSeo->metaTitle ?? ''),
                'description' => (string) ($resolvedSeo->ogDescription ?? $resolvedSeo->metaDescription ?? ''),
                'imageUrl' => $resolvedSeo->ogImage ?? null,
            ],
            'jsonLd' => $this->normalizeJsonLd($resolvedSeo->structuredData ?? []),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBasicSeo(Store $store, string $path, string $title, string $description, string $openGraphType): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'canonicalUrl' => $this->tenantUrl($store, $path),
            'robots' => app()->environment('production') ? 'index,follow' : 'noindex,nofollow',
            'hreflang' => $this->basicHreflang($store, $path),
            'openGraph' => [
                'title' => $title,
                'description' => $description,
                'type' => $openGraphType,
                'imageUrl' => null,
            ],
            'twitter' => [
                'card' => 'summary',
                'title' => $title,
                'description' => $description,
                'imageUrl' => null,
            ],
            'jsonLd' => [[
                '@context' => 'https://schema.org',
                '@type' => $openGraphType === 'product' ? 'Product' : 'WebPage',
                'name' => $title,
                'description' => $description,
                'url' => $this->tenantUrl($store, $path),
            ]],
        ];
    }

    /**
     * @return array<int, array{locale: string, url: string}>
     */
    private function basicHreflang(Store $store, string $path): array
    {
        return [
            ['locale' => 'en', 'url' => $this->tenantUrl($store, $path)],
            ['locale' => 'ar', 'url' => $this->tenantUrl($store, $path === '/' ? '/ar' : '/ar' . $path)],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeJsonLd(mixed $structuredData): array
    {
        if ($structuredData === null) {
            return [];
        }

        if (is_array($structuredData) && array_is_list($structuredData)) {
            return array_values(array_filter($structuredData, 'is_array'));
        }

        return is_array($structuredData) ? [$structuredData] : [];
    }

    private function localizedStorefrontPath(StoreMarketingPage $page, string $locale): string
    {
        $fallbackLocale = (string) config('app.fallback_locale', 'en');
        $slug = (string) $this->localizedContentResolver->resolveLocalizedField($page->slug, $locale, $fallbackLocale);

        if ($slug === '') {
            return $locale === 'ar' ? '/ar' : '/';
        }

        return $locale === 'ar' ? '/ar/' . ltrim($slug, '/') : '/' . ltrim($slug, '/');
    }

    private function categoryPath(Category $category, string $locale): string
    {
        $translation = $category->translation($locale);
        $slug = $translation?->slug ?? $category->slug;
        $prefix = $locale === 'ar' ? '/ar' : '';

        return $prefix . '/shop/category/' . ltrim($slug, '/');
    }

    private function productPath(Product $product, string $locale): string
    {
        $translation = $product->translation($locale);
        $slug = $translation?->slug ?? '';
        $prefix = $locale === 'ar' ? '/ar' : '';

        return $prefix . '/shop/product/' . ltrim($slug, '/');
    }

    private function tenantUrl(Store $store, string $path): string
    {
        return 'https://' . $store->domain . $this->normalizedPath($path);
    }

    private function currentStore(): Store
    {
        /** @var Store|null $store */
        $store = app()->bound('currentStore') ? app('currentStore') : null;

        if (!$store instanceof Store) {
            throw new RuntimeContractException(
                runtimeCode: 'runtime.tenant_not_found',
                httpStatus: 404,
                message: 'The requested tenant could not be resolved from the storefront domain.',
            );
        }

        return $store;
    }

    private function resolveLocale(?string $locale): string
    {
        $candidate = (string) ($locale ?: app()->getLocale());
        $supported = (array) config('storefront_runtime.supported_locales', ['en', 'ar']);

        if (!in_array($candidate, $supported, true)) {
            throw new RuntimeContractException(
                runtimeCode: 'runtime.invalid_locale',
                httpStatus: 422,
                message: 'The requested storefront locale is not supported.',
                details: ['locale' => $candidate],
            );
        }

        request()->attributes->set('storefront_runtime_locale', $candidate);

        return $candidate;
    }

    private function normalizedPath(string $path): string
    {
        return $this->responseFactory->normalizePath($path);
    }

    private function resolvedLocaleFromRequest(): string
    {
        return $this->resolveLocale((string) (request()->attributes->get('storefront_runtime_locale') ?? app()->getLocale()));
    }

    private function resolvedPathFromRequest(): string
    {
        return $this->responseFactory->resolveRequestPath(request());
    }

    private function routeTtl(string $path, int $default): int
    {
        return $path === '/missing-page' ? 60 : $default;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function previewContextForRequest(Store $store, string $path, string $locale): ?array
    {
        $token = (string) request()->header('X-Preview-Token', '');

        if ($token === '') {
            return null;
        }

        return $this->previewTokenService->authorize($store, $token, $path, $locale);
    }

    private function isLegacyPassthrough(string $path): bool
    {
        foreach ((array) config('storefront_runtime.legacy_passthrough_prefixes', []) as $prefix) {
            $prefix = $this->normalizedPath((string) $prefix);

            if ($path === $prefix || str_starts_with($path . '/', rtrim($prefix, '/') . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function notFoundRoute(string $path, string $locale, string $routeType): array
    {
        return [
            'status' => 'not_found',
            'routeType' => $routeType,
            'pageId' => null,
            'resourceType' => 'none',
            'resourceId' => null,
            'path' => $path,
            'locale' => $locale,
            'layout' => null,
            'legacyPassthrough' => false,
        ];
    }

    private function lookupPathForLocale(string $path, string $locale): string
    {
        $prefix = '/' . $locale;

        if ($path === $prefix) {
            return '/';
        }

        if (str_starts_with($path, $prefix . '/')) {
            return substr($path, strlen($prefix)) ?: '/';
        }

        return $path;
    }

    private function localizedRuntimePath(string $path, string $locale): string
    {
        $normalized = $this->normalizedPath($path);

        if ($locale === 'en' || $normalized === '/') {
            return $locale === 'ar' && $normalized === '/' ? '/ar' : $normalized;
        }

        return '/' . $locale . ($normalized === '/' ? '' : $normalized);
    }

    /**
     * @param array{pageId: string, expiresAt: string, cacheBypass: bool} $previewContext
     * @return array<string, mixed>|null
     */
    private function resolvePreviewMarketingRoute(Store $store, string $path, string $locale, array $previewContext): ?array
    {
        $pageId = (string) ($previewContext['pageId'] ?? '');

        if (!str_starts_with($pageId, 'mkt_')) {
            return null;
        }

        $page = $this->marketingPage((int) substr($pageId, 4), $store->id, true);
        $expectedPath = $this->localizedStorefrontPath($page, $locale);

        if ($expectedPath !== $path) {
            throw new RuntimeContractException(
                runtimeCode: 'runtime.preview_invalid',
                httpStatus: 403,
                message: 'The preview token is invalid for the requested tenant and page.',
                details: [
                    'pageId' => $pageId,
                    'reason' => 'page_path_scope_mismatch',
                ],
            );
        }

        return [
            'status' => 'matched',
            'routeType' => 'marketing_page',
            'pageId' => $pageId,
            'resourceType' => 'page',
            'resourceId' => $pageId,
            'path' => $path,
            'locale' => $locale,
            'layout' => 'marketing',
            'legacyPassthrough' => false,
        ];
    }

    /**
     * Resolve theme data from database
     * 
     * @return array<string, mixed>
     */
    private function resolveThemeDataFromDatabase(Store $store, string $locale): array
    {
        // Try to get published theme first (for live storefront)
        // If no published theme exists, fall back to active_theme_id (for development/preview)
        $theme = $this->themeRepository->getPublishedForStore($store->id);
        
        if (!$theme && $store->active_theme_id) {
            // Fallback to the theme set via active_theme_id
            $theme = $store->activeTheme()->with(['sections.blocks', 'templates'])->first();
        }
        
        if ($theme) {
            $settings = $theme->settings ?? [];
            
            return [
                'themeKey' => $theme->slug ?? 'default',
                'name' => $theme->name,
                'version' => $theme->version,
                'branding' => [
                    'storeName' => $store->name,
                    'tagline' => $settings['tagline'] ?? ($locale === 'ar'
                        ? 'تسوق الإلكترونيات والأزياء والمنزل — توصيل سريع وأسعار واضحة.'
                        : 'Electronics, fashion, and home essentials — curated for everyday shopping.'),
                ],
                'tokens' => [
                    'colorPrimary' => $settings['colors']['primary'] ?? (string) config('storefront_runtime.theme.tokens.color_primary'),
                    'colorSecondary' => $settings['colors']['secondary'] ?? (string) config('storefront_runtime.theme.tokens.color_primary'),
                    'colorAccent' => $settings['colors']['accent'] ?? (string) config('storefront_runtime.theme.tokens.color_primary'),
                    'colorSurface' => $settings['colors']['background'] ?? (string) config('storefront_runtime.theme.tokens.color_surface'),
                    'colorText' => $settings['colors']['text'] ?? (string) config('storefront_runtime.theme.tokens.color_text'),
                    // Support both old fonts structure and new typography structure
                    'fontBody' => $settings['typography']['bodyFont'] ?? $settings['fonts']['body'] ?? (string) config('storefront_runtime.theme.tokens.font_body'),
                    'fontHeading' => $settings['typography']['headingFont'] ?? $settings['fonts']['heading'] ?? (string) config('storefront_runtime.theme.tokens.font_heading'),
                ],
                'assets' => [
                    'logoUrl' => $this->resolvePublicImageUrl((string) ($store->logo_url ?? '')),
                    'faviconUrl' => $this->resolvePublicImageUrl((string) ($store->favicon_url ?? '')),
                ],
                'settings' => [
                    'radius' => $settings['radius'] ?? (string) config('storefront_runtime.theme.radius', 'md'),
                    'direction' => $locale === 'ar' ? 'rtl' : 'ltr',
                    // Include complete settings for frontend consumption
                    'colors' => $settings['colors'] ?? [],
                    'color_schemes' => $settings['color_schemes'] ?? [],
                    'typography' => $settings['typography'] ?? [],
                    'buttons' => $settings['buttons'] ?? [],
                ],
            ];
        }

        // Fallback to config-based theme if no database theme exists
        return [
            'themeKey' => 'default-light',
            'branding' => [
                'storeName' => $store->name,
                'tagline' => $locale === 'ar'
                    ? 'تسوق الإلكترونيات والأزياء والمنزل — توصيل سريع وأسعار واضحة.'
                    : 'Electronics, fashion, and home essentials — curated for everyday shopping.',
            ],
            'tokens' => [
                'colorPrimary' => (string) config('storefront_runtime.theme.tokens.color_primary'),
                'colorSecondary' => (string) config('storefront_runtime.theme.tokens.color_primary'),
                'colorAccent' => (string) config('storefront_runtime.theme.tokens.color_primary'),
                'colorSurface' => (string) config('storefront_runtime.theme.tokens.color_surface'),
                'colorText' => (string) config('storefront_runtime.theme.tokens.color_text'),
                'fontBody' => (string) config('storefront_runtime.theme.tokens.font_body'),
                'fontHeading' => (string) config('storefront_runtime.theme.tokens.font_heading'),
            ],
            'assets' => [
                'logoUrl' => $store->logo_url,
                'faviconUrl' => $store->favicon_url,
            ],
            'settings' => [
                'radius' => (string) config('storefront_runtime.theme.radius', 'md'),
            'direction' => $locale === 'ar' ? 'rtl' : 'ltr',
        ],
    ];
}


}
