<?php

declare(strict_types=1);

namespace App\Repositories\Cms\Documentation;

use App\Models\Cms\CmsDocument;
use Illuminate\Database\Eloquent\Collection;

class CmsDocumentRepository
{
    public function findById(int $id): ?CmsDocument
    {        return CmsDocument::find($id);
    }

    public function findBySlugPath(array $slugs): ?CmsDocument
    {        // This is complex because slugs are JSON.
        // For now, let's assume we search for the last slug in the path and verify parentage.
        // In a real scenario, we might need a more optimized way to resolve slug paths.
        
        $lastSlug = end($slugs);
        $locale = app()->getLocale();

        return CmsDocument::where("slug->$locale", $lastSlug)
            ->published()
            ->first();
    }

    public function getPublishedDocuments(): Collection
    {
        return CmsDocument::published()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function getSidebarTree(): Collection
    {
        // Get all published documents and sections to build a tree
        return CmsDocument::whereNull('parent_id')
            ->published()
            ->with(['children' => fn($q) => $q->published()->orderBy('sort_order')->orderBy('id')])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function create(array $data): CmsDocument
    {        return CmsDocument::create($data);
    }

    public function update(CmsDocument $document, array $data): bool
    {        return $document->update($data);
    }

    public function delete(CmsDocument $document): bool
    {        return $document->delete();
    }

    public function reorder(array $orders): void
    {        foreach ($orders as $order) {
            CmsDocument::where('id', $order['id'])
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
        $sectionId = $document->section_id;

        $previous = CmsDocument::where('section_id', $sectionId)
            ->where('sort_order', '<', $document->sort_order)
            ->published()
            ->orderByDesc('sort_order')
            ->first();

        $next = CmsDocument::where('section_id', $sectionId)
            ->where('sort_order', '>', $document->sort_order)
            ->published()
            ->orderBy('sort_order')
            ->first();

        return [
            'previous' => $previous,
            'next' => $next,
        ];
    }
}
