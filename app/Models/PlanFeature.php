<?php

namespace App\Models;

use App\Enums\Entitlement\FeatureKeyEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'feature_key',
        'value_type',
        'limit_value',
        'boolean_value',
    ];

    protected $casts = [
        'plan_id'       => 'integer',
        'feature_key'   => FeatureKeyEnum::class,
        'limit_value'   => 'integer',
        'boolean_value' => 'boolean',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the resolved value for this feature.
     * Returns integer for limits, boolean for boolean features, or null for unlimited.
     */
    public function getResolvedValue(): int|bool|null
    {
        return match ($this->value_type) {
            'limit'     => $this->limit_value,
            'boolean'   => $this->boolean_value,
            'unlimited' => null,
            default     => null,
        };
    }

    /**
     * Check if this feature is unlimited.
     */
    public function isUnlimited(): bool
    {
        return $this->value_type === 'unlimited' || $this->limit_value === null;
    }

    /**
     * Check if this is a boolean feature.
     */
    public function isBoolean(): bool
    {
        return $this->value_type === 'boolean';
    }

    /**
     * Check if this is a limit feature.
     */
    public function isLimit(): bool
    {
        return $this->value_type === 'limit';
    }
}
