<?php

declare(strict_types=1);

namespace App\Actions\Theme;

use App\DTOs\Theme\UpdateSystemTemplateDTO;
use App\Models\Theme\ThemeTemplate;
use App\Services\Storefront\Runtime\RuntimeCacheService;
use Illuminate\Support\Facades\DB;

class UpdateSystemTemplateAction
{
    public function __construct(
        private readonly RuntimeCacheService $runtimeCacheService,
    ) {}

    public function execute(ThemeTemplate $template, UpdateSystemTemplateDTO $dto): ThemeTemplate
    {
        $updated = DB::transaction(function () use ($template, $dto) {
            if ($dto->isDefault === true) {
                ThemeTemplate::whereHas('theme', fn($q) => $q->where('id', $template->theme_id))
                    ->where('type', $template->type->value)
                    ->where('id', '!=', $template->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $template->update($dto->toArray());

            if ($dto->sectionIds !== null) {
                $existingPivot = $template->sections()->get()->keyBy('id')->mapWithKeys(
                    fn($s) => [$s->id => [
                        'overrides' => $s->pivot->overrides ?? '[]',
                        'is_enabled' => $s->pivot->is_enabled ?? true,
                    ]]
                );

                $hasOverrideChanges = $dto->sectionOverrides !== null;
                $overrides = $dto->sectionOverrides ?? [];
                $hasVisibilityChanges = $dto->sectionVisibility !== null;
                $visibility = $dto->sectionVisibility ?? [];
                $pivotData = [];
                foreach ($dto->sectionIds as $position => $sectionId) {
                    $overrideValue = $hasOverrideChanges && isset($overrides[$sectionId])
                        ? json_encode($overrides[$sectionId])
                        : ($existingPivot[$sectionId]['overrides'] ?? json_encode([]));

                    $pivotData[$sectionId] = [
                        'position' => $position,
                        'overrides' => $overrideValue,
                        'is_enabled' => $hasVisibilityChanges && isset($visibility[$sectionId])
                            ? $visibility[$sectionId]
                            : ($existingPivot[$sectionId]['is_enabled'] ?? true),
                    ];
                }
                $template->sections()->sync($pivotData);
            }

            return $template->fresh()->load('sections');
        });

        $store = $updated->theme?->store;
        if ($store !== null) {
            $this->runtimeCacheService->invalidateTenantArtifacts($store);
        }

        return $updated;
    }
}
