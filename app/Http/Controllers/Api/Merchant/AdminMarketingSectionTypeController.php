<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant;

use App\Enums\Cms\Marketing\MarketingSectionTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Cms\Marketing\Store\StoreMarketingPage;
use App\Models\Store;
use Illuminate\Http\JsonResponse;

class AdminMarketingSectionTypeController extends Controller
{
    public function index(Store $store): JsonResponse
    {
        $this->authorize('viewAny', [StoreMarketingPage::class, $store]);

        return $this->success(
            MarketingSectionTypeEnum::options(),
        );
    }
}
