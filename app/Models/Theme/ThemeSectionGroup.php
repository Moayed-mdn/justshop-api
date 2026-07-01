<?php

namespace App\Models\Theme;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ThemeSectionGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'theme_id',
        'name',
        'handle',
        'sections',
        'order',
    ];

    protected $casts = [
        'sections' => 'array',
        'order' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }
}
