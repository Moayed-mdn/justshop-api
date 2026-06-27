<?php

declare(strict_types=1);

namespace App\Actions\Theme;

use App\Exceptions\Theme\CannotDeleteDefaultTemplateException;
use App\Exceptions\Theme\TemplateInUseException;
use App\Models\PageTemplate;
use Illuminate\Support\Facades\DB;

class DeleteTemplateAction
{
    /**
     * Execute the action to delete a template
     */
    public function execute(PageTemplate $template): void
    {
        DB::transaction(function () use ($template) {
            // Prevent deletion if template is default
            if ($template->is_default) {
                throw new CannotDeleteDefaultTemplateException();
            }

            // Check if template is in use by pages
            $pagesCount = $template->pages()->count();
            
            if ($pagesCount > 0) {
                throw new TemplateInUseException($pagesCount);
            }

            $template->delete();
        });
    }
}
