<?php

declare(strict_types=1);

namespace App\Repositories\Brand;

use App\Models\Brand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BrandRepository
{
    // ── Queries ────────────────────────────────────────────────

    public function paginate(
        int $storeId,
        ?bool $isActive,
        int $perPage,
    ): LengthAwarePaginator {
        $query = Brand::query()
            ->where('store_id', $storeId)
            ->withCount(['products'])
            ->orderBy('sort_order');

        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }

        return $query->paginate($perPage);
    }

    public function findById(
        int $id,
        int $storeId,
    ): ?Brand {
        return Brand::query()
            ->where('store_id', $storeId)
            ->where('id', $id)
            ->withCount('products')
            ->first();
    }

    public function findByIdOrFail(
        int $id,
        int $storeId,
    ): Brand {
        $brand = $this->findById($id, $storeId);

        if ($brand === null) {
            throw new \App\Exceptions\Brand\BrandNotFoundException();
        }

        return $brand;
    }

    public function findTrashedById(
        int $id,
        int $storeId,
    ): ?Brand {
        return Brand::withTrashed()
            ->where('store_id', $storeId)
            ->where('id', $id)
            ->first();
    }

    public function slugExistsForStore(
        string $slug,
        int $storeId,
        ?int $excludeId = null,
    ): bool {
        $query = Brand::query()
            ->where('store_id', $storeId)
            ->where('slug', $slug);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function hasProducts(int $id, int $storeId): bool
    {
        return Brand::query()
            ->where('store_id', $storeId)
            ->where('id', $id)
            ->whereHas('products', fn($q) => $q
                ->where('store_id', $storeId)
            )
            ->exists();
    }

    // ── Mutations ──────────────────────────────────────────────

    public function create(
        int $storeId,
        string $name,
        string $slug,
        ?string $description,
        ?string $logoUrl,
        int $sortOrder,
        bool $isActive,
    ): Brand {
        return Brand::create([
            'store_id'    => $storeId,
            'name'        => $name,
            'slug'        => $slug,
            'description' => $description,
            'logo_url'    => $logoUrl,
            'sort_order'  => $sortOrder,
            'is_active'   => $isActive,
        ]);
    }

    public function update(
        Brand $brand,
        string $name,
        string $slug,
        ?string $description,
        ?string $logoUrl,
        int $sortOrder,
        bool $isActive,
    ): Brand {
        $brand->update([
            'name'        => $name,
            'slug'        => $slug,
            'description' => $description,
            'logo_url'    => $logoUrl,
            'sort_order'  => $sortOrder,
            'is_active'   => $isActive,
        ]);

        return $brand->fresh();
    }

    public function delete(Brand $brand): void
    {
        $brand->delete();
    }

    public function restore(Brand $brand): void
    {
        $brand->restore();
    }
}
