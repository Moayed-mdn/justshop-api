<?php

declare(strict_types=1);

namespace App\Repositories\HeroBanner;

use App\Models\HeroBanner;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class HeroBannerRepository
{
    /**
     * List hero banners for a specific store with optional filters.
     */
    public function list(
        int $storeId,
        ?string $status = null,
        ?string $search = null,
    ): Collection {
        $query = HeroBanner::where('store_id', $storeId)
            ->with('translations');

        // Status filter
        if ($status === 'active') {
            $query->where('is_active', true)->whereNull('deleted_at');
        } elseif ($status === 'inactive') {
            $query->where('is_active', false)->whereNull('deleted_at');
        } elseif ($status === 'trashed') {
            $query->onlyTrashed();
        } elseif ($status === 'all' || $status === null) {
            $query->withTrashed();
        }

        // Search filter
        if ($search) {
            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('subtitle', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('position', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find hero banner by ID for a specific store.
     */
    public function findById(int $storeId, int $id): ?HeroBanner
    {
        return HeroBanner::where('store_id', $storeId)
            ->where('id', $id)
            ->withTrashed()
            ->with('translations')
            ->first();
    }

    /**
     * Find hero banner by ID for a specific store or fail.
     */
    public function findByIdOrFail(int $storeId, int $id): HeroBanner
    {
        return HeroBanner::where('store_id', $storeId)
            ->where('id', $id)
            ->withTrashed()
            ->with('translations')
            ->firstOrFail();
    }

    /**
     * Create a new hero banner with translations.
     */
    public function create(array $data, array $translations): HeroBanner
    {
        return DB::transaction(function () use ($data, $translations) {
            $banner = HeroBanner::create($data);

            foreach ($translations as $translation) {
                $banner->translations()->create($translation);
            }

            return $banner->load('translations');
        });
    }

    /**
     * Update hero banner and its translations.
     */
    public function update(HeroBanner $banner, array $data, array $translations): HeroBanner
    {
        return DB::transaction(function () use ($banner, $data, $translations) {
            $banner->update($data);

            // Delete existing translations and recreate
            $banner->translations()->delete();

            foreach ($translations as $translation) {
                $banner->translations()->create($translation);
            }

            return $banner->load('translations');
        });
    }

    /**
     * Soft delete hero banner.
     */
    public function delete(HeroBanner $banner): bool
    {
        return $banner->delete();
    }

    /**
     * Restore soft-deleted hero banner.
     */
    public function restore(HeroBanner $banner): bool
    {
        return $banner->restore();
    }
}
