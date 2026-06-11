<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Repositories\Theme\ThemeRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class StorefrontThemeController extends Controller
{
    public function __construct(
        private ThemeRepository $themeRepository
    ) {
    }

    /**
     * Get active theme for the storefront
     */
    public function show(): JsonResponse
    {
        $store = request()->attributes->get('store');
        
        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Store not found',
            ], 404);
        }

        // Cache the theme for 1 hour
        $cacheKey = "storefront:theme:store:{$store->id}";
        $ttl = 3600;

        $theme = Cache::remember($cacheKey, $ttl, function () use ($store) {
            return $this->themeRepository->getActiveForStore($store->id);
        });

        if (!$theme) {
            // Return default theme structure
            return response()->json([
                'success' => true,
                'data' => [
                    'themeKey' => 'default-light',
                    'branding' => [
                        'storeName' => $store->name,
                        'tagline' => 'Your one-stop shop',
                    ],
                    'tokens' => [
                        'colorPrimary' => config('storefront_runtime.theme.tokens.color_primary', '#000000'),
                        'colorSecondary' => config('storefront_runtime.theme.tokens.color_primary', '#6366f1'),
                        'colorAccent' => config('storefront_runtime.theme.tokens.color_primary', '#ec4899'),
                        'colorSurface' => config('storefront_runtime.theme.tokens.color_surface', '#ffffff'),
                        'colorText' => config('storefront_runtime.theme.tokens.color_text', '#000000'),
                        'fontBody' => config('storefront_runtime.theme.tokens.font_body', 'Inter'),
                        'fontHeading' => config('storefront_runtime.theme.tokens.font_heading', 'Inter'),
                    ],
                    'assets' => [
                        'logoUrl' => $store->logo_url,
                        'faviconUrl' => $store->favicon_url,
                    ],
                    'settings' => [
                        'radius' => 'md',
                        'direction' => 'ltr',
                    ],
                ],
            ]);
        }

        // Extract theme settings
        $settings = $theme->settings ?? [];

        return response()->json([
            'success' => true,
            'data' => [
                'themeKey' => $theme->slug ?? 'default',
                'name' => $theme->name,
                'version' => $theme->version,
                'branding' => [
                    'storeName' => $store->name,
                    'tagline' => $settings['tagline'] ?? 'Your one-stop shop',
                ],
                'tokens' => [
                    'colorPrimary' => $settings['colors']['primary'] ?? '#000000',
                    'colorSecondary' => $settings['colors']['secondary'] ?? '#6366f1',
                    'colorAccent' => $settings['colors']['accent'] ?? '#ec4899',
                    'colorSurface' => $settings['colors']['background'] ?? '#ffffff',
                    'colorText' => $settings['colors']['text'] ?? '#000000',
                    'fontBody' => $settings['fonts']['body'] ?? 'Inter',
                    'fontHeading' => $settings['fonts']['heading'] ?? 'Inter',
                ],
                'assets' => [
                    'logoUrl' => $store->logo_url,
                    'faviconUrl' => $store->favicon_url,
                ],
                'settings' => [
                    'radius' => $settings['radius'] ?? 'md',
                    'direction' => $settings['direction'] ?? 'ltr',
                ],
            ],
        ]);
    }
}
