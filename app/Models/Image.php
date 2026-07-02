<?php

// app/Models/Image.php

namespace App\Models;

use App\Support\Media\MediaUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Image extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'imageable_id',
        'imageable_type',
        'image_url',
        'alt_text',
        'is_primary',
        'sort_order',
    ];

    protected $appends = ['full_url'];

    public function imageable()
    {
        return $this->morphTo();
    }

    public function getFullUrlAttribute(): string
    {
        return (string) MediaUrl::resolve($this->image_url);
    }

    public function setImageUrlAttribute(?string $value): void
    {
        $this->attributes['image_url'] = MediaUrl::normalizeStorablePath($value);
    }
}
