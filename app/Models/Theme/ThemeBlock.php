<?php

namespace App\Models\Theme;

use App\Enums\Theme\BlockTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ThemeBlock extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'section_id',
        'name',
        'type',
        'handle',
        'description',
        'settings',
        'content',
        'position',
        'is_enabled',
        'is_removable',
    ];

    protected $casts = [
        'type' => BlockTypeEnum::class,
        'settings' => 'array',
        'content' => 'array',
        'position' => 'integer',
        'is_enabled' => 'boolean',
        'is_removable' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the section that owns the block
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(ThemeSection::class, 'section_id');
    }

    /**
     * Scope to get only enabled blocks
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }
}
