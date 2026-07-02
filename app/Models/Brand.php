<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasStoreScoping;
use App\Support\Media\MediaUrl;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use HasFactory, SoftDeletes, HasStoreScoping;

    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'description',
        'logo_url',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────────

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => MediaUrl::resolve($value),
            set: fn (?string $value): ?string => MediaUrl::normalizeStorablePath($value),
        );
    }
}
