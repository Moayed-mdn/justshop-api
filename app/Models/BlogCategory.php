<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'slug' => 'array',
            'description' => 'array',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }

    public function translated(string $key, ?string $locale = null): mixed
    {
        $locale = $locale ?? app()->getLocale();
        return $this->{$key}[$locale] ?? $this->{$key}[config('content.default_locale', 'en')] ?? null;
    }
}
