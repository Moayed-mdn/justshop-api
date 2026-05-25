<?php

declare(strict_types=1);

namespace App\Models\Cms\Marketing\Store;

use App\Contracts\Cms\HasLocalizedContent;
use App\Contracts\Cms\HasSeoMetadata;
use App\DTOs\Cms\Seo\SeoMetaDTO;
use App\Enums\Cms\Marketing\MarketingPageStatusEnum;
use App\Enums\Cms\Marketing\MarketingPageTemplateEnum;
use App\Models\Store;
use App\Models\User;
use App\Traits\Cms\LocalizedContentTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Store Marketing Page
 * 
 * Tenant-owned marketing content (store landing pages, campaigns, etc.)
 * MUST include store_id — slug uniqueness scoped per store
 */
class StoreMarketingPage extends Model implements HasLocalizedContent, HasSeoMetadata
{
    use HasFactory, SoftDeletes, LocalizedContentTrait;

    protected $fillable = [
        'store_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'status',
        'published_at',
        'seo',
        'template',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'store_id' => 'integer',
            'title' => 'array',
            'slug' => 'array',
            'excerpt' => 'array',
            'content' => 'array',
            'seo' => 'array',
            'status' => MarketingPageStatusEnum::class,
            'template' => MarketingPageTemplateEnum::class,
            'published_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(StoreMarketingSection::class)->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopePublished(Builder $query): void
    {
        $query->where('status', MarketingPageStatusEnum::PUBLISHED)
            ->where(function (Builder $q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function scopeDraft(Builder $query): void
    {
        $query->where('status', MarketingPageStatusEnum::DRAFT);
    }

    public function scopeScheduled(Builder $query): void
    {
        $query->where('status', MarketingPageStatusEnum::SCHEDULED)
            ->where('published_at', '>', now());
    }

    // ── SEO ──────────────────────────────────────────────────

    public function getSeoMetadata(): SeoMetaDTO
    {
        $seo = $this->seo ?? [];

        return new SeoMetaDTO(
            title: (string) ($seo['title'] ?? $this->getLocalized('title')),
            description: (string) ($seo['description'] ?? $this->getLocalized('excerpt')),
            keywords: (string) ($seo['keywords'] ?? ''),
            ogTitle: (string) ($seo['og_title'] ?? $seo['title'] ?? $this->getLocalized('title')),
            ogDescription: (string) ($seo['og_description'] ?? $seo['description'] ?? $this->getLocalized('excerpt')),
            ogImage: (string) ($seo['og_image'] ?? ''),
            canonicalUrl: (string) ($seo['canonical_url'] ?? ''),
            noindex: (bool) ($seo['noindex'] ?? false),
            nofollow: (bool) ($seo['nofollow'] ?? false),
        );
    }
}
