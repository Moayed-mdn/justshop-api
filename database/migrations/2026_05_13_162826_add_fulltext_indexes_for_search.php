<?php
// database/migrations/xxxx_add_fulltext_indexes_for_search.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('product_translations', function (Blueprint $table) {
            $table->fullText(['name', 'description'], 'pt_search_fulltext');
        });

        Schema::table('category_translations', function (Blueprint $table) {
            $table->fullText(['name'], 'ct_search_fulltext');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->fullText(['name'], 'brands_search_fulltext');
        });

        Schema::table('tag_translations', function (Blueprint $table) {
            $table->fullText(['name'], 'tags_search_fulltext');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('product_translations', fn (Blueprint $table) => $table->dropFullText('pt_search_fulltext'));
        Schema::table('category_translations', fn (Blueprint $table) => $table->dropFullText('ct_search_fulltext'));
        Schema::table('brands', fn (Blueprint $table) => $table->dropFullText('brands_search_fulltext'));
        Schema::table('tag_translations', fn (Blueprint $table) => $table->dropFullText('tags_search_fulltext'));
    }
};
