<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogTag extends Model
{
    use HasFactory;

    protected $fillable = [];

    public function translations(): HasMany
    {
        return $this->hasMany(BlogTagTranslation::class);
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_tag');
    }

    public function translation(?string $locale = null): ?BlogTagTranslation
    {
        if (!$this->relationLoaded('translations')) {
            return null;
        }

        $locale = $locale ?? app()->getLocale();

        return $this->translations->firstWhere('locale', $locale)
            ?? $this->translations->first();
    }
}
