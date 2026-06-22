<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Drop hero banners tables as the feature is deprecated.
     * CMS pages with is_homepage flag now handle homepage content.
     */
    public function up(): void
    {
        Schema::dropIfExists('hero_banner_translations');
        Schema::dropIfExists('hero_banners');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not reversible - hero banners feature is permanently deprecated
        // Use CMS pages with is_homepage = true instead
    }
};
