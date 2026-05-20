<?php

namespace App\DTOs\Admin\Tag;

use App\Http\Requests\Admin\Tag\UpdateTagRequest;
use Illuminate\Support\Str;

class UpdateTagDTO
{
    /**
     * @param  int          $storeId
     * @param  int          $tagId
     * @param  string|null  $type          null = no change
     * @param  string|null  $color         null = no change
     * @param  bool|null    $isActive      null = no change
     * @param  array|null   $translations  null = no change.
     *                                     Each entry upserts the given locale.
     */
    public function __construct(
        public int     $storeId,
        public int     $tagId,
        public ?string $type         = null,
        public ?string $color        = null,
        public ?bool   $isActive     = null,
        public ?array  $translations = null,
    ) {}

    public static function fromRequest(
        UpdateTagRequest $request,
        int $storeId,
        int $tagId,
    ): self {
        $translations = null;

        if ($request->exists('translations')) {
            $translations = collect($request->input('translations', []))
                ->map(fn($t) => [
                    'locale' => $t['locale'],
                    'name'   => $t['name'],
                    'slug'   => !empty($t['slug'])
                        ? $t['slug']
                        : Str::slug($t['name']),
                ])
                ->all();
        }

        return new self(
            storeId:      $storeId,
            tagId:        $tagId,
            type:         $request->exists('type')
                ? $request->input('type')
                : null,
            color:        $request->exists('color')
                ? $request->input('color')
                : null,
            isActive:     $request->exists('is_active')
                ? $request->boolean('is_active')
                : null,
            translations: $translations,
        );
    }
}
