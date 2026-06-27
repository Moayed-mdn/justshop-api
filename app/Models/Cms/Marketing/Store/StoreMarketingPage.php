<?php

declare(strict_types=1);

namespace App\Models\Cms\Marketing\Store;

use App\Contracts\Cms\HasLocalizedContent;
use App\Contracts\Cms\HasSeoMetadata;
use App\DTOs\Cms\Seo\SeoMetaDTO;
use App\Enums\Cms\Marketing\MarketingPageStatusEnum;
use App\Enums\Cms\Marketing\MarketingPageTemplateEnum;
use App\Models\PageTemplate;
use App\Models\PageTemplateOverride;
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
        'page_template_id',
        'sort_order',
        'is_homepage',
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
            'is_homepage' => 'boolean',
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

    public function pageTemplate(): BelongsTo
    {
        return $this->belongsTo(PageTemplate::class, 'page_template_id');
    }

    public function templateOverrides(): HasMany
    {
        return $this->hasMany(PageTemplateOverride::class, 'page_id');
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

    public function scopeHomepage(Builder $query): void
    {
        $query->where('is_homepage', true);
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
        return ''; // Store marketing pages are typically at root or handled via tenant routing
    }

    // ── Helpers ────────────────────────────────────────────────

    /**
     * Get the resolved template (template + page-specific overrides)
     */
    public function getResolvedTemplate(): ?PageTemplate
    {
        if (!$this->pageTemplate) {
            // Fallback to store's default template for this type
            return PageTemplate::forStore($this->store_id)
                ->byType('page')
                ->default()
                ->active()
                ->first();
        }

        return $this->pageTemplate;
    }

    /**
     * Get template configuration with overrides applied
     */
    public function getTemplateConfig(): ?array
    {
        $template = $this->getResolvedTemplate();
        
        if (!$template) {
            return null;
        }

        $sections = $template->sections;
        
        // Apply page-specific overrides
        foreach ($this->templateOverrides as $override) {
            if (isset($sections[$override->section_id])) {
                $sections[$override->section_id]['settings'] = $override->mergeWithDefaults(
                    $sections[$override->section_id]['settings'] ?? []
                );
            }
        }

        return [
            'id' => $template->id,
            'handle' => $template->handle,
            'sections' => $sections,
            'section_order' => $template->section_order,
        ];
    }

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
