<?php

declare(strict_types=1);

namespace App\Actions\Theme;

use App\Exceptions\Theme\CannotDeleteDefaultTemplateException;
use App\Models\Theme\ThemeTemplate;
use Illuminate\Support\Facades\DB;

class DeleteSystemTemplateAction
{
    public function execute(ThemeTemplate $template): void
    {
        DB::transaction(function () use ($template) {
            if ($template->is_default) {
                throw new CannotDeleteDefaultTemplateException();
            }

            $template->sections()->detach();
            $template->delete();
        });
    }
}
