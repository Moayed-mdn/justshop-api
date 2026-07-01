<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function newSettings(): string
    {
        return json_encode([
            'show_category_filter' => true,
            'show_price_filter' => true,
            'show_manufacture_filter' => true,
            'show_expiry_filter' => true,
            'show_brand_filter' => true,
            'show_rating_filter' => false,
        ]);
    }

    public function up(): void
    {
        DB::table('theme_sections')
            ->where('type', 'search_filters')
            ->update(['settings' => $this->newSettings()]);
    }

    public function down(): void
    {
        DB::table('theme_sections')
            ->where('type', 'search_filters')
            ->update([
                'settings' => json_encode([
                    'show_price_range' => true,
                    'show_categories' => true,
                    'show_brands' => true,
                ]),
            ]);
    }
};
