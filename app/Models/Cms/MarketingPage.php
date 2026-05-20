<?php

declare(strict_types=1);

namespace App\Models\Cms;

use App\Enums\Cms\MarketingPage\MarketingPageStatusEnum;
use App\Enums\Cms\MarketingPage\MarketingPageTypeEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketingPage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'slug',
        'title',
        'sections',
        'seo',
        'status',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => MarketingPageTypeEnum::class,
            'status' => MarketingPageStatusEnum::class,
            'slug' => 'array',
            'title' => 'array',
            'sections' => 'array',
            'seo' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', MarketingPageStatusEnum::PUBLISHED->value)
            ->where(function (Builder $builder): void {
                $builder->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }
}
