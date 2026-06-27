<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageTemplateOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'section_id',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    // Relationships

    public function page(): BelongsTo
    {
        return $this->belongsTo(StoreMarketingPage::class, 'page_id');
    }

    // Helper Methods

    /**
     * Get a specific setting value
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Set a specific setting value
     */
    public function setSetting(string $key, mixed $value): void
    {
        $settings = $this->settings;
        $settings[$key] = $value;
        $this->settings = $settings;
    }

    /**
     * Merge settings with template defaults
     */
    public function mergeWithDefaults(array $templateSettings): array
    {
        return array_merge($templateSettings, $this->settings);
    }
}
