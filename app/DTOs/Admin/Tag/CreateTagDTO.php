<?php

namespace App\DTOs\Admin\Tag;

use App\Http\Requests\Admin\Tag\CreateTagRequest;
use Illuminate\Support\Str;

class CreateTagDTO
{
    /**
     * @param  int          $storeId       Store that owns this tag.
     * @param  string       $type          Tag type (e.g. 'general', 'season').
     * @param  string|null  $color         Optional display color (hex or name).
     * @param  bool         $isActive
     * @param  array        $translations  [['locale' => 'en', 'name' => '...', 'slug' => '...'], ...]
     *                                     slug is auto-generated from name if not provided.
     */
    public function __construct(
        public int     $storeId,
        public string  $type,
        public ?string $color,
        public bool    $isActive,
        public array   $translations,
    ) {}

    public static function fromRequest(
        CreateTagRequest $request,
        int $storeId,
    ): self {
        $translations = collect($request->input('translations', []))
            ->map(fn($t) => [
                'locale' => $t['locale'],
                'name'   => $t['name'],
                // Auto-generate slug from name if not explicitly provided.
                'slug'   => !empty($t['slug'])
                    ? $t['slug']
                    : Str::slug($t['name']),
            ])
            ->all();

        return new self(
            storeId:      $storeId,
            type:         $request->input('type', 'general'),
            color:        $request->input('color'),
            isActive:     $request->boolean('is_active', true),
            translations: $translations,
        );
    }
}
