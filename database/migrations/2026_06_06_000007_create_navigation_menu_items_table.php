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
        Schema::create('navigation_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('navigation_menus')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('navigation_menu_items')->onDelete('cascade');
            $table->string('label');
            $table->string('type'); // page, category, product, collection, external, custom
            $table->string('url')->nullable(); // For external links or custom URLs
            $table->unsignedBigInteger('resource_id')->nullable(); // ID of linked resource (category_id, product_id, etc.)
            $table->string('resource_type')->nullable(); // Type of linked resource (Category, Product, etc.)
            $table->string('target')->default('_self'); // _self, _blank
            $table->json('settings')->nullable(); // Item-specific settings (icon, badge, etc.)
            $table->integer('position')->default(0); // Display order
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['menu_id', 'parent_id', 'position']);
            $table->index(['menu_id', 'is_active']);
            $table->index(['resource_type', 'resource_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('navigation_menu_items');
    }
};
