<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Cms\Blog\BlogPostPublishStateEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'author_id',
        'blog_category_id',
        'featured',
        'is_published',
        'published_at',
        'cover_image',
        'reading_time',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'reading_time' => 'integer',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(BlogPostTranslation::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tag');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function translation(?string $locale = null): ?BlogPostTranslation
    {
        if (!$this->relationLoaded('translations')) {
            return null;
        }

        $locale = $locale ?? app()->getLocale();

        return $this->translations->firstWhere('locale', $locale)
            ?? $this->translations->first();
    }

    public function translated(string $key, ?string $locale = null): mixed
    {
        return $this->translation($locale)?->{$key};
    }

    public function getPublishStateAttribute(): BlogPostPublishStateEnum
    {
        if (!$this->is_published) {
            return BlogPostPublishStateEnum::DRAFT;
        }

        if ($this->published_at && $this->published_at->isFuture()) {
            return BlogPostPublishStateEnum::SCHEDULED;
        }

        return BlogPostPublishStateEnum::PUBLISHED;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(function (Builder $builder): void {
                $builder->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('featured', true);
    }

    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderByDesc('published_at')
            ->orderByDesc('created_at');
    }
}
