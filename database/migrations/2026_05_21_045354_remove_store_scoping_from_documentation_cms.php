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
            $table->dropForeign(['store_id']);
            $table->dropIndex(['store_id', 'is_published', 'published_at']);
            $table->dropColumn('store_id');
            $table->index(['is_published', 'published_at']);
        });

        Schema::table('cms_document_sections', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropIndex(['store_id', 'is_published', 'published_at']);
            $table->dropColumn('store_id');
            $table->index(['is_published', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cms_documents', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete();
            $table->dropIndex(['is_published', 'published_at']);
            $table->index(['store_id', 'is_published', 'published_at']);
        });

        Schema::table('cms_document_sections', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete();
            $table->dropIndex(['is_published', 'published_at']);
            $table->index(['store_id', 'is_published', 'published_at']);
        });
    }
};
