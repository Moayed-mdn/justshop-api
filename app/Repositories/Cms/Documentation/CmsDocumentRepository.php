<?php

declare(strict_types=1);

namespace App\Repositories\Cms\Documentation;

use App\Models\Cms\CmsDocument;
use Illuminate\Database\Eloquent\Collection;

class CmsDocumentRepository
{
    public function findById(int $id, int $storeId): ?CmsDocument
    {
        return CmsDocument::where('store_id', $storeId)->find($id);
    }

    public function findBySlugPath(array $slugs, int $storeId): ?CmsDocument
    {
        // This is complex because slugs are JSON.
        // For now, let's assume we search for the last slug in the path and verify parentage.
        // In a real scenario, we might need a more optimized way to resolve slug paths.
        
        $lastSlug = end($slugs);
        $locale = app()->getLocale();

        return CmsDocument::where('store_id', $storeId)
            ->where("slug->$locale", $lastSlug)
            ->published()
            ->first();
    }

    public function getPublishedDocuments(int $storeId): Collection
    {
        return CmsDocument::where('store_id', $storeId)
            ->published()
            ->orderBy('sort_order')
            ->get();
    }

    public function getSidebarTree(int $storeId): Collection
    {
        // Get all published documents and sections to build a tree
        return CmsDocument::where('store_id', $storeId)
            ->whereNull('parent_id')
            ->published()
            ->with(['children' => fn($q) => $q->published()])
            ->orderBy('sort_order')
            ->get();
    }

    public function create(array $data): CmsDocument
    {
        return CmsDocument::create($data);
    }

    public function update(CmsDocument $document, array $data): bool
    {
        return $document->update($data);
    }

    public function delete(CmsDocument $document): bool
    {
        return $document->delete();
    }

    public function reorder(int $storeId, array $orders): void
    {
        foreach ($orders as $order) {
            CmsDocument::where('store_id', $storeId)
                ->where('id', $order['id'])
                ->update(['sort_order' => $order['sort_order'], 'parent_id' => $order['parent_id'] ?? null]);
        }
    }

    public function getBreadcrumbs(CmsDocument $document): array
    {
        $breadcrumbs = [];
        $current = $document;
        $locale = app()->getLocale();

        while ($current) {
            array_unshift($breadcrumbs, [
                'title' => $current->title[$locale] ?? $current->title['en'] ?? '',
                'slug' => $current->slug[$locale] ?? $current->slug['en'] ?? '',
            ]);
            $current = $current->parent;
        }

        return $breadcrumbs;
    }

    public function getPreviousNext(CmsDocument $document): array
    {
        $storeId = $document->store_id;
        $sectionId = $document->section_id;

        $previous = CmsDocument::where('store_id', $storeId)
            ->where('section_id', $sectionId)
            ->where('sort_order', '<', $document->sort_order)
            ->published()
            ->orderBy('sort_order', 'desc')
            ->first();

        $next = CmsDocument::where('store_id', $storeId)
            ->where('section_id', $sectionId)
            ->where('sort_order', '>', $document->sort_order)
            ->published()
            ->orderBy('sort_order', 'asc')
            ->first();

        $locale = app()->getLocale();

        return [
            'previous' => $previous ? [
                'title' => $previous->title[$locale] ?? $previous->title['en'] ?? '',
                'slug' => $previous->slug[$locale] ?? $previous->slug['en'] ?? '',
            ] : null,
            'next' => $next ? [
                'title' => $next->title[$locale] ?? $next->title['en'] ?? '',
                'slug' => $next->slug[$locale] ?? $next->slug['en'] ?? '',
            ] : null,
        ];
    }
}
