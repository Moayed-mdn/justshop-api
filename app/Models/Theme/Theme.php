<?php

namespace App\Models\Theme;

use App\Models\Concerns\HasStoreScoping;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Theme extends Model
{
    use HasFactory, SoftDeletes, HasStoreScoping;

    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'description',
        'version',
        'author',
        'settings',
        'metadata',
        'is_active',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the store that owns the theme
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the sections for the theme
     */
    public function sections(): HasMany
    {
        return $this->hasMany(ThemeSection::class)->orderBy('position');
    }

    /**
     * Get the templates for the theme
     */
    public function templates(): HasMany
    {
        return $this->hasMany(ThemeTemplate::class);
    }

    public function sectionGroups(): HasMany
    {
        return $this->hasMany(ThemeSectionGroup::class);
    }

    /**
     * Resolve merchant theme routes by slug only. When this runs on a scoped
     * relation query, Laravel keeps the lookup tenant-safe.
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return $query->where('slug', (string) $value);
    }

    /**
     * Scope to get only active themes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only published themes
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
