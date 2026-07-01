<?php

declare(strict_types=1);

namespace App\Actions\Theme;

use App\DTOs\Theme\CreateSystemTemplateDTO;
use App\Models\Theme\ThemeTemplate;
use Illuminate\Support\Facades\DB;

class CreateSystemTemplateAction
{
    public function execute(CreateSystemTemplateDTO $dto): ThemeTemplate
    {
        return DB::transaction(function () use ($dto) {
            if ($dto->isDefault) {
                ThemeTemplate::whereHas('theme', fn($q) => $q->where('id', $dto->themeId))
                    ->where('type', $dto->type->value)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $template = ThemeTemplate::create([
                'theme_id' => $dto->themeId,
                'name' => $dto->name,
                'handle' => $dto->handle,
                'type' => $dto->type->value,
                'description' => $dto->description,
                'settings' => $dto->settings,
                'is_default' => $dto->isDefault,
            ]);

            if (!empty($dto->sectionIds)) {
                $pivotData = [];
                foreach ($dto->sectionIds as $position => $sectionId) {
                    $pivotData[$sectionId] = ['position' => $position];
                }
                $template->sections()->sync($pivotData);
            }

            return $template->load('sections');
        });
    }
}
