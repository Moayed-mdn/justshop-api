<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TagTranslation — locale-specific content for a Tag.
 *
 * Follows the same translation model pattern as:
 *   ProductTranslation  (product_translations table)
 *   AttributeTranslation (attribute_translations table)
 *
 * One record per tag per locale.
 * Unique constraint: [tag_id, locale] enforced at DB level.
 * Unique constraint: [locale, slug]  enforced at DB level.
 */
class TagTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'tag_id',
        'locale',
        'name',
        'slug',
    ];

    // ── Relationships ──────────────────────────────────────────

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
