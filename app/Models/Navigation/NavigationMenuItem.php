<?php

namespace App\Models\Navigation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NavigationMenuItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'menu_id',
        'parent_id',
        'label',
        'type',
        'url',
        'resource_id',
        'resource_type',
        'target',
        'settings',
        'position',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'position' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the menu that owns the item
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(NavigationMenu::class, 'menu_id');
    }

    /**
     * Get the parent menu item
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(NavigationMenuItem::class, 'parent_id');
    }

    /**
     * Get the child menu items
     */
    public function children(): HasMany
    {
        return $this->hasMany(NavigationMenuItem::class, 'parent_id')->orderBy('position');
    }

    /**
     * Get the linked resource (polymorphic)
     */
    public function resource()
    {
        return $this->morphTo('resource', 'resource_type', 'resource_id');
    }

    /**
     * Scope to get only active items
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only root items (no parent)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get the resolved URL for this menu item based on type and linked resource
     */
    public function getResolvedUrl(string $locale = 'en'): string
    {
        // For group type, no URL needed
        if ($this->type === 'group') {
            return '#';
        }

        // If custom URL is set for non-resource types, use it
        if (!empty($this->url) && !$this->requiresResource()) {
            return $this->url;
        }

        // Resolve URL from linked resource
        return match($this->type) {
            'page' => $this->resolvePageUrl($locale),
            'category' => $this->resolveCategoryUrl($locale),
            'product' => $this->resolveProductUrl($locale),
            'external', 'custom', 'link' => $this->url ?? '#',
            default => $this->url ?? '#'
        };
    }

    /**
     * Check if this menu item type requires a linked resource
     */
    public function requiresResource(): bool
    {
        return in_array($this->type, ['page', 'category', 'product', 'collection']);
    }

    /**
     * Resolve URL for a linked page resource
     */
    private function resolvePageUrl(string $locale): string
    {
        if (!$this->resource) {
            return $this->url ?? '#';
        }

        // Get localized slug from page
        $slug = is_array($this->resource->slug) 
            ? ($this->resource->slug[$locale] ?? $this->resource->slug['en'] ?? '')
            : $this->resource->slug;
        
        return '/' . ltrim($slug, '/');
    }

    /**
     * Resolve URL for a linked category resource
     */
    private function resolveCategoryUrl(string $locale): string
    {
        if (!$this->resource) {
            return $this->url ?? '#';
        }

        // Get localized slug from category
        $slug = $this->resource->getSlug($locale);
        return "/shop/category/{$slug}";
    }

    /**
     * Resolve URL for a linked product resource
     */
    private function resolveProductUrl(string $locale): string
    {
        if (!$this->resource) {
            return $this->url ?? '#';
        }

        // Get localized slug from product
        $slug = $this->resource->getSlug($locale);
        return "/shop/product/{$slug}";
    }

    /**
     * Get human-readable resource type label
     */
    public function getResourceTypeLabel(): string
    {
        return match($this->type) {
            'page' => 'Page',
            'category' => 'Category',
            'product' => 'Product',
            'collection' => 'Collection',
            'group' => 'Group',
            'link' => 'Custom Link',
            'external' => 'External Link',
            'custom' => 'Custom',
            default => 'Unknown'
        };
    }
}
