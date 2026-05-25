<?php

declare(strict_types=1);

namespace App\Models\Cms\Marketing\Platform;

use App\Contracts\Cms\HasLocalizedContent;
use App\Contracts\Cms\HasSeoMetadata;
use App\DTOs\Cms\Seo\SeoMetaDTO;
use App\Enums\Cms\Marketing\MarketingPageStatusEnum;
use App\Enums\Cms\Marketing\MarketingPageTemplateEnum;
use App\Models\User;
use App\Traits\Cms\LocalizedContentTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Platform Marketing Page
 * 
 * Platform-owned marketing content (home, pricing, features, etc.)
 * NO store_id — globally unique slugs
 */
class PlatformMarketingPage extends Model implements HasLocalizedContent, HasSeoMetadata
{
    use HasFactory, SoftDeletes, LocalizedContentTrait;

    protected $fillable = [
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

    public function sections(): HasMany
    {
        return $this->hasMany(PlatformMarketingSection::class)->orderBy('sort_order');
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

    public function scopeByTemplate(Builder $query, string $template): void
    {
        $query->where('template', $template);
    }

    // ── SEO Contract ───────────────────────────────────────────

    public function getSeoMetadata(): SeoMetaDTO
    {
        return SeoMetaDTO::fromArray($this->seo ?? []);
    }

    public function getSlugMap(): array
    {
        return $this->slug ?? [];
    }

    public function getRoutePrefix(): string
    {
        return ''; // Platform marketing pages are at root
    }

    // ── Helpers ────────────────────────────────────────────────

    public function isPublished(): bool
    {
        return $this->status === MarketingPageStatusEnum::PUBLISHED
            && ($this->published_at === null || $this->published_at->isPast());
    }

    public function isDraft(): bool
    {
        return $this->status === MarketingPageStatusEnum::DRAFT;
    }

    public function isScheduled(): bool
    {
        return $this->status === MarketingPageStatusEnum::SCHEDULED
            && $this->published_at?->isFuture();
    }
}
