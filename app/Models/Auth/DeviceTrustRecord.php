<?php

declare(strict_types=1);

namespace App\Models\Auth;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceTrustRecord extends Model
{
    protected $fillable = [
        'user_id',
        'device_id',
        'device_type',
        'ip_address',
        'last_active_at',
        'is_trusted',
        'metadata',
    ];

    protected $casts = [
        'last_active_at' => 'datetime',
        'is_trusted' => 'boolean',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
