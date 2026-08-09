<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('locale',5);
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();

            // 1. Ensure a category only has one translation per language
            $table->unique(['category_id', 'locale']);
            if (DB::getDriverName() !== 'sqlite') {
                $table->fullText(['name'], 'ct_search_fulltext');
            }

            // 2. Ensure a slug is unique within a specific language
            $table->unique(['locale', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_translations');
    }
};
