<?php

declare(strict_types=1);

namespace App\Models\Cms\Marketing\Store;

use App\Contracts\Cms\HasLocalizedContent;
use App\Models\Store;
use App\Traits\Cms\LocalizedContentTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreMarketingSection extends Model implements HasLocalizedContent
{
    use HasFactory, LocalizedContentTrait;

    protected $fillable = [
        'store_id',
        'store_marketing_page_id',
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
            'store_id' => 'integer',
            'store_marketing_page_id' => 'integer',
            'title' => 'array',
            'subtitle' => 'array',
            'content' => 'array',
            'settings' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(StoreMarketingPage::class, 'store_marketing_page_id');
    }
}
