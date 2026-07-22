<?php

namespace App\Repositories\Admin\Tag;

use App\Exceptions\Tag\TagNotFoundException;
use App\Models\Tag;
use App\Models\TagTranslation;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AdminTagRepository extends BaseRepository
{
    protected function modelClass(): string
    {
        return Tag::class;
    }

    // ── Eager-load Definitions ─────────────────────────────────

    /**
     * Standard relations for tag editor responses.
     * name and slug live in tag_translations — always load translations.
     */
    private function editorRelations(): array
    {
        return [
            'translations',
        ];
    }

    // ── Read Operations ────────────────────────────────────────

    /**
     * Paginated tag list for a store.
     *
     * Returns tags that are either:
     *   - owned by the store (store_id = $storeId), OR
     *   - global/system tags (store_id = null)
     *
     * Super admin sees all tags regardless of store scope
     * when $includeGlobal = true (default).
     *
     * @param  bool  $includeGlobal  Include global tags (store_id = null).
     */
    public function listForStore(
        int $storeId,
        ?string $search  = null,
        ?string $type    = null,
        ?bool   $active  = null,
        bool    $includeGlobal = true,
        int     $perPage = 15,
    ): LengthAwarePaginator {
        $query = Tag::query()->with($this->editorRelations());

        $query->where(function ($q) use ($storeId, $includeGlobal) {
            $q->where('store_id', $storeId);
            if ($includeGlobal) {
                $q->orWhereNull('store_id');
            }
        });

        if ($search) {
            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($type !== null) {
            $query->where('type', $type);
        }

        if ($active !== null) {
            $query->where('is_active', $active);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Find a tag within store scope.
     *
     * A tag is accessible to a store if:
     *   - Its store_id matches the given store, OR
     *   - Its store_id is null (global tag).
     *
     * @throws TagNotFoundException
     */
    public function findInStore(int $tagId, int $storeId): Tag
    {
        $tag = Tag::query()
            ->with($this->editorRelations())
            ->where('id', $tagId)
            ->where(function ($q) use ($storeId) {
                $q->where('store_id', $storeId)
                    ->orWhereNull('store_id');
            })
            ->first();

        if ($tag === null) {
            throw new TagNotFoundException();
        }

        return $tag;
    }

    /**
     * Find a tag owned exclusively by this store (not global).
     * Used for update/delete operations where global tags must not be mutated
     * by store-level admins.
     *
     * @throws TagNotFoundException
     */
    public function findStoreOwnedTag(int $tagId, int $storeId): Tag
    {
        $tag = $this->scopedQuery()
            ->with($this->editorRelations())
            ->where('id', $tagId)
            ->first();

        if (!$tag) {
            throw new TagNotFoundException();
        }

        return $tag;
    }

    /**
     * Verify that all given tag IDs are accessible to the store.
     *
     * Accessible = store_id matches OR store_id is null (global).
     * Returns the IDs that are NOT accessible (invalid IDs).
     *
     * @param  int[]  $tagIds
     * @return int[]  Invalid tag IDs (empty = all valid)
     */
    public function findInaccessibleTagIds(array $tagIds, int $storeId): array
    {
        if (empty($tagIds)) {
            return [];
        }

        $accessibleIds = Tag::query()
            ->whereIn('id', $tagIds)
            ->where(function ($q) use ($storeId) {
                $q->where('store_id', $storeId)
                    ->orWhereNull('store_id');
            })
            ->pluck('id')
            ->toArray();

        return array_values(array_diff($tagIds, $accessibleIds));
    }

    // ── Write Operations ───────────────────────────────────────

    /**
     * Create a new store-scoped tag.
     */
    public function create(array $data): Tag
    {
        return Tag::create($data);
    }

    /**
     * Update tag metadata (type, color, is_active).
     * Translations are managed separately via upsertTranslation().
     */
    public function update(Tag $tag, array $data): Tag
    {
        $tag->update($data);
        return $tag->fresh($this->editorRelations());
    }

    /**
     * Upsert a translation for the given locale.
     * Creates if absent, updates if present.
     */
    public function upsertTranslation(
        Tag $tag,
        string $locale,
        array $translationData,
    ): void {
        $tag->translations()->updateOrCreate(
            ['locale' => $locale],
            $translationData,
        );
    }

    /**
     * Soft-delete a store-owned tag.
     * Global tags (store_id = null) should not be deleted via store API.
     */
    public function softDelete(Tag $tag): void
    {
        $tag->delete();
    }

    /**
     * Restore a soft-deleted store-owned tag.
     */
    public function restore(Tag $tag): Tag
    {
        $tag->restore();
        return $tag->fresh($this->editorRelations());
    }

    /**
     * Reload a tag with all editor relations.
     */
    public function refresh(Tag $tag): Tag
    {
        return $tag->fresh($this->editorRelations()) ?? $tag->load($this->editorRelations());
    }
}
