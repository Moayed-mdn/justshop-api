<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Lead\LeadStatusEnum;
use App\Enums\Lead\LeadTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'status',
        'source_page',
        'locale',
        'name',
        'email',
        'company',
        'phone',
        'message',
        'metadata',
        'ip_address',
        'user_agent',
        'contacted_at',
        'archived_at',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => LeadTypeEnum::class,
            'status' => LeadStatusEnum::class,
            'metadata' => 'array',
            'contacted_at' => 'datetime',
            'archived_at' => 'datetime',
            'resolved_at' => 'datetime',
            'resolution_notes' => 'string',
        ];
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
