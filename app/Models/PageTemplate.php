<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Cms\Marketing\Store\StoreMarketingPage;

class PageTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'name',
        'handle',
        'type',
        'description',
        'sections',
        'section_order',
        'section_settings',
        'is_default',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sections' => 'array',
        'section_order' => 'array',
        'section_settings' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(StoreMarketingPage::class, 'page_template_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForStore($query, int $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    // Helper Methods

    /**
     * Get section configuration by section ID
     */
    public function getSectionConfig(string $sectionId): ?array
    {
        return $this->sections[$sectionId] ?? null;
    }

    /**
     * Check if template has a specific section
     */
    public function hasSection(string $sectionId): bool
    {
        return isset($this->sections[$sectionId]);
    }

    /**
     * Get all section types used in this template
     */
    public function getSectionTypes(): array
    {
        return array_map(
            fn ($section) => $section['type'] ?? null,
            $this->sections
        );
    }
}
