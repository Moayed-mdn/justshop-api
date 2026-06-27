<?php

declare(strict_types=1);

namespace App\Actions\Theme;

use App\DTOs\Theme\CreateTemplateDTO;
use App\Models\PageTemplate;
use Illuminate\Support\Facades\DB;

class CreateTemplateAction
{
    /**
     * Execute the action to create a new template
     */
    public function execute(CreateTemplateDTO $dto): PageTemplate
    {
        return DB::transaction(function () use ($dto) {
            // If this template is set as default, unset other defaults of same type
            if ($dto->isDefault) {
                PageTemplate::where('store_id', $dto->storeId)
                    ->where('type', $dto->type)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            return PageTemplate::create([
                'store_id' => $dto->storeId,
                'name' => $dto->name,
                'handle' => $dto->handle,
                'type' => $dto->type,
                'description' => $dto->description,
                'sections' => $dto->sections,
                'section_order' => $dto->sectionOrder,
                'section_settings' => $dto->sectionSettings,
                'is_default' => $dto->isDefault,
                'is_active' => true,
                'created_by' => $dto->userId,
                'updated_by' => $dto->userId,
            ]);
        });
    }
}
