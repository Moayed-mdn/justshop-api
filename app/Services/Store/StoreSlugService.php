<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Models\Store;
use Illuminate\Support\Str;

class StoreSlugService
{
    private const RESERVED_SLUGS = [
        'admin',
        'api',
        'app',
        'support',
        'billing',
        'login',
        'register',
        'account',
        'dashboard',
        'docs',
        'help',
        'status',
        'shop',
        'store',
        'root',
        'sys',
        'system',
        'webmaster',
        'security',
    ];

    public function isReserved(string $slug): bool
    {
        return in_array(strtolower($slug), self::RESERVED_SLUGS, true);
    }

    public function isAvailable(string $slug, ?int $ignoreStoreId = null): bool
    {
        if ($this->isReserved($slug)) {
            return false;
        }

        $query = Store::where('slug', $slug);

        if ($ignoreStoreId) {
            $query->where('id', '!=', $ignoreStoreId);
        }

        return !$query->exists();
    }

    public function normalize(string $slug): string
    {
        return Str::slug($slug);
    }
}
