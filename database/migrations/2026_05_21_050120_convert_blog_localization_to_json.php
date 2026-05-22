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
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->json('title')->after('id');
            $table->json('slug')->after('title');
            $table->json('excerpt')->nullable()->after('slug');
            $table->json('content')->after('excerpt');
            
            // SEO JSON column to match MarketingPage
            $table->json('seo')->nullable()->after('content');
        });

        Schema::table('blog_categories', function (Blueprint $table) {
            $table->json('name')->after('id');
            $table->json('slug')->after('name');
            $table->json('description')->nullable()->after('slug');
        });

        Schema::table('blog_tags', function (Blueprint $table) {
            $table->json('name')->after('id');
            $table->json('slug')->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn(['title', 'slug', 'excerpt', 'content', 'seo']);
        });

        Schema::table('blog_categories', function (Blueprint $table) {
            $table->dropColumn(['name', 'slug', 'description']);
        });

        Schema::table('blog_tags', function (Blueprint $table) {
            $table->dropColumn(['name', 'slug']);
        });
    }
};
