<?php

declare(strict_types=1);

use App\Models\Cms\Marketing\Store\StoreMarketingPage;
use App\Models\PageTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Assign default templates to existing pages
     */
    public function up(): void
    {
        // Get all pages without template assignment
        $pages = StoreMarketingPage::whereNull('page_template_id')->get();

        foreach ($pages as $page) {
            // Get default template for this store
            $defaultTemplate = PageTemplate::where('store_id', $page->store_id)
                ->where('type', 'page')
                ->where('is_default', true)
                ->first();

            if ($defaultTemplate) {
                $page->update(['page_template_id' => $defaultTemplate->id]);
            }
        }
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        // No rollback needed - pages keep their template assignments
    }
};
