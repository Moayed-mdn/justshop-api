<?php

declare(strict_types=1);

namespace App\Actions\Theme;

use App\DTOs\Theme\UpdateTemplateDTO;
use App\Models\PageTemplate;
use Illuminate\Support\Facades\DB;

class UpdateTemplateAction
{
    /**
     * Execute the action to update a template
     */
    public function execute(PageTemplate $template, UpdateTemplateDTO $dto): PageTemplate
    {
        return DB::transaction(function () use ($template, $dto) {
            // If setting this template as default, unset other defaults of same type
            if ($dto->isDefault === true) {
                PageTemplate::where('store_id', $template->store_id)
                    ->where('type', $template->type)
                    ->where('id', '!=', $template->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $template->update($dto->toArray());

            return $template->fresh();
        });
    }
}
