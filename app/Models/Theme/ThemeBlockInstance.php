<?php

namespace App\Models\Theme;

use App\Enums\Theme\BlockTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ThemeBlockInstance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'container_type',
        'container_id',
        'type',
        'name',
        'settings',
        'content',
        'position',
        'is_enabled',
    ];

    protected $casts = [
        'type' => BlockTypeEnum::class,
        'settings' => 'array',
        'content' => 'array',
        'position' => 'integer',
        'is_enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function container(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }
}
