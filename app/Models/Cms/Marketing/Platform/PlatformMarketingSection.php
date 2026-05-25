<?php

declare(strict_types=1);

namespace App\Models\Cms\Marketing\Platform;

use App\Enums\Cms\Marketing\MarketingSectionTypeEnum;
use App\Traits\Cms\LocalizedContentTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Platform Marketing Section
 * 
 * Reusable content blocks for platform marketing pages
 */
class PlatformMarketingSection extends Model
{
    use HasFactory, LocalizedContentTrait;

    protected $fillable = [
        'platform_marketing_page_id',
        'section_type',
        'identifier',
        'sort_order',
        'title',
        'subtitle',
        'content',
        'settings',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'subtitle' => 'array',
            'content' => 'array',
            'settings' => 'array',
            'section_type' => MarketingSectionTypeEnum::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    public function page(): BelongsTo
    {
        return $this->belongsTo(PlatformMarketingPage::class, 'platform_marketing_page_id');
    }
}
