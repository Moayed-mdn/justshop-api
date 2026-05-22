<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BlogTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'slug' => 'array',
        ];
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_tag');
    }

    public function translated(string $key, ?string $locale = null): mixed
    {
        $locale = $locale ?? app()->getLocale();
        return $this->{$key}[$locale] ?? $this->{$key}[config('content.default_locale', 'en')] ?? null;
    }
}
