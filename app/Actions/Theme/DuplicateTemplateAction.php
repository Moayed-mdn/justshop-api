<?php

declare(strict_types=1);

namespace App\Actions\Theme;

use App\Models\PageTemplate;
use Illuminate\Support\Facades\DB;

class DuplicateTemplateAction
{
    /**
     * Execute the action to duplicate a template
     */
    public function execute(PageTemplate $template): PageTemplate
    {
        return DB::transaction(function () use ($template) {
            $newHandle = $this->generateUniqueHandle($template->handle, $template->store_id);
            
            return PageTemplate::create([
                'store_id' => $template->store_id,
                'name' => $template->name . ' (Copy)',
                'handle' => $newHandle,
                'type' => $template->type,
                'description' => $template->description,
                'sections' => $template->sections,
                'section_order' => $template->section_order,
                'section_settings' => $template->section_settings,
                'is_default' => false, // Duplicates are never default
                'is_active' => true,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Generate a unique handle for the duplicated template
     */
    private function generateUniqueHandle(string $baseHandle, int $storeId): string
    {
        $counter = 1;
        $newHandle = $baseHandle . '-copy';

        while (PageTemplate::where('store_id', $storeId)->where('handle', $newHandle)->exists()) {
            $counter++;
            $newHandle = $baseHandle . '-copy-' . $counter;
        }

        return $newHandle;
    }
}
