<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tag — store-scoped taxonomy entity.
 *
 * Architecture notes:
 * ─────────────────────────────────────────────────────────────────────────────
 * Store scope:
 *   Tags belong to a store (store_id nullable for global/system tags).
 *   All product-facing tag queries MUST scope by store_id.
 *   The product endpoints accept tag IDs only — callers are responsible for
 *   ensuring the tag belongs to the correct store before passing its ID.
 *
 * Translation ownership:
 *   name and slug live in tag_translations, NOT on the tags table.
 *   The tags table holds only store-independent metadata:
 *   type, color, is_active, store_id.
 *   getNameAttribute() and getSlugAttribute() resolve from translations.
 *   Both accessors guard against N+1 via relationLoaded() checks.
 *
 * Product association:
 *   Products reference tags by integer ID only (see CreateProductRequest,
 *   UpdateProductRequest). Tag creation and translation management are handled
 *   by a dedicated tag management API, not the product endpoints.
 * ─────────────────────────────────────────────────────────────────────────────
 */
class Tag extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'type',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────────

    /**
     * The store this tag belongs to.
     * Nullable — null means the tag is global/system-level.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * All locale translations for this tag.
     */
    public function translations(): HasMany
    {
        return $this->hasMany(TagTranslation::class);
    }

    // ── Translation Helpers ────────────────────────────────────

    /**
     * Get the translation record for the given locale.
     *
     * Falls back to the first available translation if the requested
     * locale is not found. Consistent with Product::translation() pattern.
     *
     * Guards against N+1: if translations are not loaded, returns null
     * rather than firing a query. Callers must eager-load translations.
     */
    public function translation(?string $locale = null): ?TagTranslation
    {
        if (!$this->relationLoaded('translations')) {
            return null;
        }

        $locale = $locale ?? app()->getLocale();

        return $this->translations->where('locale', $locale)->first()
            ?? $this->translations->first();
    }

    /**
     * Get the translated name for the current application locale.
     *
     * Returns null if translations are not loaded or none exist.
     * Use Tag::with('translations') before accessing this accessor
     * in any loop or collection context.
     */
    public function getNameAttribute(): ?string
    {
        return $this->translation()?->name;
    }

    /**
     * Get the translated slug for the current application locale.
     *
     * Same N+1 guard as getNameAttribute().
     */
    public function getSlugAttribute(): ?string
    {
        return $this->translation()?->slug;
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForStore($query, int $storeId)
    {
        return $query->where('store_id', $storeId);
    }
}
