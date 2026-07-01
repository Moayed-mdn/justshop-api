<?php

namespace App\Models\Theme;

use App\Enums\Theme\SectionTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ThemeSection extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'theme_id',
        'name',
        'type',
        'handle',
        'description',
        'settings',
        'position',
        'is_enabled',
        'is_removable',
    ];

    protected $casts = [
        'type' => SectionTypeEnum::class,
        'settings' => 'array',
        'position' => 'integer',
        'is_enabled' => 'boolean',
        'is_removable' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the theme that owns the section
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    /**
     * Get the blocks for the section
     */
    public function blocks(): HasMany
    {
        return $this->hasMany(ThemeBlock::class, 'section_id')->orderBy('position');
    }

    /**
     * Get the templates that use this section
     */
    public function templates(): BelongsToMany
    {
        return $this->belongsToMany(ThemeTemplate::class, 'theme_template_sections', 'section_id', 'template_id')
            ->withPivot('position', 'overrides')
            ->withTimestamps()
            ->orderByPivot('position');
    }

    /**
     * Scope to get only enabled sections
     */
    public function blockInstances(): MorphMany
    {
        return $this->morphMany(ThemeBlockInstance::class, 'container')->orderBy('position');
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }
}
