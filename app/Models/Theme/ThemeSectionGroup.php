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

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $group): void {
            // Normalize sections to always be an associative array keyed by id
            $sections = $group->sections;
            if (is_array($sections) && array_is_list($sections)) {
                $newSections = [];
                $newOrder = [];
                foreach ($sections as $section) {
                    if (isset($section['id'])) {
                        $id = (string) $section['id'];
                        $newSections[$id] = [
                            'type' => $section['type'],
                            'settings' => $section['settings'],
                        ];
                        $newOrder[] = $id;
                    }
                }
                $group->sections = $newSections;
                if (empty($group->order)) {
                    $group->order = $newOrder;
                }
            }
        });
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }
}
