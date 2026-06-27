<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SectionSchema extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'description',
        'category',
        'settings',
        'blocks',
        'presets',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'settings' => 'array',
        'blocks' => 'array',
        'presets' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Static Methods

    /**
     * Get schema by type
     */
    public static function getByType(string $type): ?self
    {
        return static::where('type', $type)->active()->first();
    }

    /**
     * Get all section types
     */
    public static function getAllTypes(): array
    {
        return static::active()->ordered()->pluck('name', 'type')->toArray();
    }

    /**
     * Get schemas by category
     */
    public static function getByCategory(string $category): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('category', $category)->active()->ordered()->get();
    }

    // Helper Methods

    /**
     * Get setting definition by ID
     */
    public function getSettingDefinition(string $settingId): ?array
    {
        $settings = $this->settings ?? [];
        
        foreach ($settings as $setting) {
            if (($setting['id'] ?? null) === $settingId) {
                return $setting;
            }
        }
        
        return null;
    }

    /**
     * Get default value for a setting
     */
    public function getSettingDefault(string $settingId): mixed
    {
        $setting = $this->getSettingDefinition($settingId);
        
        return $setting['default'] ?? null;
    }

    /**
     * Get all default settings as key-value pairs
     */
    public function getDefaultSettings(): array
    {
        $defaults = [];
        
        foreach ($this->settings ?? [] as $setting) {
            if (isset($setting['id']) && isset($setting['default'])) {
                $defaults[$setting['id']] = $setting['default'];
            }
        }
        
        return $defaults;
    }
}
