<?php
// app/Models/Image.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'imageable_id',
        'imageable_type',
        'image_url',
        'alt_text',
        'is_primary',
    ];

    protected $appends = ['full_url'];

    public function imageable()
    {
        return $this->morphTo();
    }

    public function getFullUrlAttribute(): string
    {
        $path = $this->image_url;
        
        // Already absolute (external URL) → return as-is
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Strip leading "/storage/" if stored that way
        $path = preg_replace('#^/?storage/#', '', $path);

        return Storage::disk('public')->url($path);
    }
}