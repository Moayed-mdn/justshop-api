<?php

namespace App\Services;

use App\Models\Category;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * HomePageService - Legacy service for best sellers
 * 
 * Note: hero() method removed - use CMS pages with is_homepage flag instead
 */
class HomePageService
{
    public function index()
    {
        $categoryId = 1;
        $limit = 12;
        $category = Category::findOrFail($categoryId);
        $locale = app()->getLocale() ?: 'en';

        $allSales = OrderItem::query()
            ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->join('product_translations', function ($join) use ($locale) {       // ← ADDED
                $join->on('products.id', '=', 'product_translations.product_id')
                    ->where('product_translations.locale', '=', $locale);
            })
            ->select(
                'products.id as product_id',
                'products.category_id',
                'product_translations.name as product_name',    // ← FIXED: was products.name
                DB::raw('SUM(order_items.quantity) as total_sold')
            )
            ->groupBy(
                'products.id',
                'products.category_id',
                'product_translations.name'                     // ← FIXED: was products.name
            )
            ->get();

        $descendants = $this->getCategoryDescendantMap();
        $sales = $allSales;

        $result = [];

        foreach ($descendants as $categoryId => $childIds) {
            $result[$categoryId] = $sales->whereIn('category_id', $childIds)
                ->sortByDesc('total_sold')
                ->values()
                ->take(20);
        }

        return $result;
    }

    function getCategoryDescendantMap()
    {
        $categories = Category::with('children')->get();

        $childrenMap = [];
        foreach ($categories as $cat) {
            $childrenMap[$cat->id] = $cat->children->pluck('id')->toArray();
        }

        $result = [];

        foreach ($categories as $cat) {
            $stack = $childrenMap[$cat->id];
            $all = [$cat->id];

            while (!empty($stack)) {
                $child = array_shift($stack);
                $all[] = $child;

                if (!empty($childrenMap[$child])) {
                    $stack = array_merge($stack, $childrenMap[$child]);
                }
            }

            $result[$cat->id] = $all;
        }

        return $result;
    }
}
