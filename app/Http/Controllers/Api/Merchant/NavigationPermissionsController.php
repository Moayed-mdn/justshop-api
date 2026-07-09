<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NavigationPermissionsController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();

        $navigation = [
            'dashboard' => $user->can('dashboard.view'),
            'products' => $user->can('product.view'),
            'orders' => $user->can('order.view'),
            'customers' => $user->can('user.view'),
            'catalog' => [
                'categories' => $user->can('category.view'),
                'brands' => $user->can('brand.view'),
                'tags' => $user->can('tag.view'),
            ],
            'sales' => [
                'orders' => $user->can('order.view'),
                'shipping' => $user->can('shipping.view'),
            ],
            'storefront' => [
                'themes' => $user->can('theme.view'),
                'navigation' => $user->can('navigation.view'),
                'templates' => $user->can('template.view'),
            ],
            'marketing' => [
                'pages' => $user->can('marketing.store.view'),
            ],
            'settings' => [
                'store' => $user->can('store.view'),
                'users' => $user->can('user.view'),
            ],
        ];

        return $this->success($navigation);
    }
}
