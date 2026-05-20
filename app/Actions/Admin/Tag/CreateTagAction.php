<?php

namespace App\Actions\Admin\Tag;

use App\DTOs\Admin\Tag\CreateTagDTO;
use App\Enums\RoleEnum;
use App\Exceptions\Store\UnauthorizedStoreAccessException;
use App\Models\Tag;
use App\Repositories\Admin\Tag\AdminTagRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateTagAction
{
    public function __construct(
        private AdminTagRepository $repository,
    ) {}

    public function execute(CreateTagDTO $dto): Tag
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        if (!$authUser->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            if (!$authUser->stores()->where('store_id', $dto->storeId)->exists()) {
                throw new UnauthorizedStoreAccessException();
            }
        }

        return DB::transaction(function () use ($dto) {

            // ── 1. Create the tag record ───────────────────────
            // store_id is always set — tags created via this endpoint
            // are store-owned. Global tags (store_id = null) are
            // created via super admin tooling only.
            $tag = $this->repository->create([
                'store_id'  => $dto->storeId,
                'type'      => $dto->type,
                'color'     => $dto->color,
                'is_active' => $dto->isActive,
            ]);

            // ── 2. Create translations ─────────────────────────
            foreach ($dto->translations as $translation) {
                $this->repository->upsertTranslation(
                    $tag,
                    $translation['locale'],
                    $translation,
                );
            }

            // ── 3. Return with translations loaded ─────────────
            return $this->repository->refresh($tag);
        });
    }
}
