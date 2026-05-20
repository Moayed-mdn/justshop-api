<?php

declare(strict_types=1);

namespace App\Models\Cms;

use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'section_id',
        'parent_id',
        'version',
        'title',
        'slug',
        'excerpt',
        'content',
        'sort_order',
        'is_published',
        'published_at',
        'meta_title',
        'meta_description',
        'canonical_url',
        'og_image',
        'robots',
        'index_controls',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'slug' => 'array',
            'excerpt' => 'array',
            'content' => 'array',
            'meta_title' => 'array',
            'meta_description' => 'array',
            'canonical_url' => 'array',
            'og_image' => 'array',
            'robots' => 'array',
            'index_controls' => 'array',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    // Relationships
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CmsDocumentSection::class, 'section_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    // Scopes
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)
            ->where(function (Builder $q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function scopeByStore(Builder $query, int $storeId): void
    {
        $query->where('store_id', $storeId);
    }
}
