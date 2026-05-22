<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cms_documents', function (Blueprint $table) {
            $table->json('seo')->nullable()->after('content');
            $table->dropColumn([
                'meta_title',
                'meta_description',
                'canonical_url',
                'og_image',
                'robots',
                'index_controls',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cms_documents', function (Blueprint $table) {
            $table->dropColumn('seo');
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->json('canonical_url')->nullable();
            $table->json('og_image')->nullable();
            $table->json('robots')->nullable();
            $table->json('index_controls')->nullable();
        });
    }
};
