<?php

namespace App\Models\Theme;

use App\Enums\Theme\TemplateTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ThemeTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'theme_id',
        'name',
        'type',
        'handle',
        'description',
        'settings',
        'is_default',
    ];

    protected $casts = [
        'type' => TemplateTypeEnum::class,
        'settings' => 'array',
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the theme that owns the template
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    /**
     * Get the sections for the template
     */
    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(ThemeSection::class, 'theme_template_sections', 'template_id', 'section_id')
            ->withPivot('position', 'overrides', 'is_enabled')
            ->withTimestamps()
            ->orderByPivot('position');
    }

    /**
     * Scope to get default templates
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
