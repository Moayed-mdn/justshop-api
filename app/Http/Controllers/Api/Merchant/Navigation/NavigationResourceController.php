<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant\Navigation;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Cms\Marketing\Store\StoreMarketingPage;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Navigation Resource Controller
 * 
 * Provides endpoints to fetch available resources (pages, categories, products)
 * for linking in navigation menu items.
 */
class NavigationResourceController extends Controller
{
    /**
     * Get all available pages for the store
     */
    public function pages(Request $request, Store $store): JsonResponse
    {
        $query = StoreMarketingPage::query()
            ->where('store_id', $store->id)
            ->published()
            ->select('id', 'slug', 'title', 'status', 'published_at', 'created_at');

        // Optional search filter
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title->en', 'like', "%{$search}%")
                  ->orWhere('title->ar', 'like', "%{$search}%");
            });
        }

        $pages = $query->orderBy('created_at', 'desc')->get();

        // Transform for frontend
        $data = $pages->map(function ($page) {
            $titleEn = is_array($page->title) ? ($page->title['en'] ?? '') : $page->title;
            $titleAr = is_array($page->title) ? ($page->title['ar'] ?? '') : '';
            $slugEn = is_array($page->slug) ? ($page->slug['en'] ?? '') : $page->slug;

            return [
                'id' => $page->id,
                'title' => [
                    'en' => $titleEn,
                    'ar' => $titleAr,
                ],
                'slug' => [
                    'en' => $slugEn,
                    'ar' => is_array($page->slug) ? ($page->slug['ar'] ?? $slugEn) : $slugEn,
                ],
                'url' => '/' . ltrim($slugEn, '/'),
                'status' => $page->status,
                'publishedAt' => $page->published_at?->toIso8601String(),
            ];
        });

        return $this->success($data, 'Pages retrieved successfully');
    }

    /**
     * Get all available categories for the store
     */
    public function categories(Request $request, Store $store): JsonResponse
    {
        $query = Category::query()
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->with('translations')
            ->select('id', 'parent_id', 'is_active', 'position');

        // Optional search filter
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $categories = $query->orderBy('position')->get();

        // Transform for frontend
        $data = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => [
                    'en' => $category->getTranslation('en')?->name ?? '',
                    'ar' => $category->getTranslation('ar')?->name ?? '',
                ],
                'slug' => $category->getSlug('en'),
                'url' => '/shop/category/' . $category->getSlug('en'),
                'parentId' => $category->parent_id,
            ];
        });

        return $this->success($data, 'Categories retrieved successfully');
    }

    /**
     * Get all available products for the store
     */
    public function products(Request $request, Store $store): JsonResponse
    {
        $query = Product::query()
            ->where('store_id', $store->id)
            ->active()
            ->with('translations')
            ->select('id', 'category_id', 'brand_id', 'status');

        // Optional search filter
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Limit results for performance
        $products = $query->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        // Transform for frontend
        $data = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => [
                    'en' => $product->getTranslation('en')?->name ?? '',
                    'ar' => $product->getTranslation('ar')?->name ?? '',
                ],
                'slug' => $product->getSlug('en'),
                'url' => '/shop/product/' . $product->getSlug('en'),
                'categoryId' => $product->category_id,
            ];
        });

        return $this->success($data, 'Products retrieved successfully');
    }

    /**
     * Get a specific resource by type and ID
     */
    public function show(Request $request, Store $store, string $type, int $id): JsonResponse
    {
        $resource = match($type) {
            'page' => StoreMarketingPage::where('store_id', $store->id)->find($id),
            'category' => Category::where('store_id', $store->id)->with('translations')->find($id),
            'product' => Product::where('store_id', $store->id)->with('translations')->find($id),
            default => null
        };

        if (!$resource) {
            return $this->error('Resource not found', 404);
        }

        // Transform based on type
        $data = match($type) {
            'page' => [
                'id' => $resource->id,
                'title' => $resource->title,
                'slug' => $resource->slug,
                'url' => '/' . ltrim(is_array($resource->slug) ? ($resource->slug['en'] ?? '') : $resource->slug, '/'),
            ],
            'category' => [
                'id' => $resource->id,
                'name' => [
                    'en' => $resource->getTranslation('en')?->name ?? '',
                    'ar' => $resource->getTranslation('ar')?->name ?? '',
                ],
                'slug' => $resource->getSlug('en'),
                'url' => '/shop/category/' . $resource->getSlug('en'),
            ],
            'product' => [
                'id' => $resource->id,
                'name' => [
                    'en' => $resource->getTranslation('en')?->name ?? '',
                    'ar' => $resource->getTranslation('ar')?->name ?? '',
                ],
                'slug' => $resource->getSlug('en'),
                'url' => '/shop/product/' . $resource->getSlug('en'),
            ],
            default => []
        };

        return $this->success($data, 'Resource retrieved successfully');
    }

    /**
     * Validate if a URL exists
     */
    public function validateUrl(Request $request, Store $store): JsonResponse
    {
        $url = $request->input('url');
        
        if (empty($url)) {
            return $this->success(['exists' => false], 'URL is empty');
        }

        // Check if it's an external URL
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $this->success(['exists' => true], 'External URL');
        }

        $url = '/' . ltrim($url, '/');
        
        // Check if page exists with this slug
        $pageExists = StoreMarketingPage::where('store_id', $store->id)
            ->published()
            ->where(function ($query) use ($url) {
                $slug = ltrim($url, '/');
                $query->where('slug->en', $slug)
                      ->orWhere('slug->ar', $slug);
            })
            ->exists();

        if ($pageExists) {
            return $this->success(['exists' => true], 'Page exists');
        }

        // Check if it matches a category URL pattern
        if (preg_match('#^/shop/category/(.+)$#', $url, $matches)) {
            $categoryExists = Category::where('store_id', $store->id)
                ->where('is_active', true)
                ->whereHas('translations', function ($query) use ($matches) {
                    $query->where('slug', $matches[1]);
                })
                ->exists();

            if ($categoryExists) {
                return $this->success(['exists' => true], 'Category exists');
            }
        }

        // Check if it matches a product URL pattern
        if (preg_match('#^/shop/product/(.+)$#', $url, $matches)) {
            $productExists = Product::where('store_id', $store->id)
                ->active()
                ->whereHas('translations', function ($query) use ($matches) {
                    $query->where('slug', $matches[1]);
                })
                ->exists();

            if ($productExists) {
                return $this->success(['exists' => true], 'Product exists');
            }
        }

        // URL doesn't exist - suggest alternatives
        $suggestion = null;
        
        // Suggest creating a page
        if (!str_starts_with($url, '/shop/')) {
            $suggestion = ltrim($url, '/');
        }

        return $this->success([
            'exists' => false,
            'suggestion' => $suggestion,
        ], 'URL does not exist');
    }
}

