<?php

namespace App\Actions\Admin\Tag;

use App\DTOs\Admin\Tag\UpdateTagDTO;
use App\Enums\RoleEnum;
use App\Exceptions\Store\UnauthorizedStoreAccessException;
use App\Models\Tag;
use App\Repositories\Admin\Tag\AdminTagRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UpdateTagAction
{
    public function __construct(
        private AdminTagRepository $repository,
    ) {}

    public function execute(UpdateTagDTO $dto): Tag
    {
        // Only store-owned tags can be updated via this endpoint.
        // Global tags (store_id = null) cannot be mutated by store admins.
        $tag = $this->repository->findStoreOwnedTag($dto->tagId, $dto->storeId);

        return DB::transaction(function () use ($dto, $tag) {

            // ── 1. Update tag metadata ─────────────────────────
            $payload = array_filter([
                'type'      => $dto->type,
                'color'     => $dto->color,
                'is_active' => $dto->isActive,
            ], fn($v) => !is_null($v));

            if (!empty($payload)) {
                $this->repository->update($tag, $payload);
            }

            // ── 2. Upsert translations ─────────────────────────
            // Each provided locale is upserted independently.
            // Locales not included are not touched.
            if (!is_null($dto->translations)) {
                foreach ($dto->translations as $translation) {
                    $this->repository->upsertTranslation(
                        $tag,
                        $translation['locale'],
                        $translation,
                    );
                }
            }

            // ── 3. Return with translations loaded ─────────────
            return $this->repository->refresh($tag);
        });
    }
}
