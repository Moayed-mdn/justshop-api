<?php
// database/migrations/xxxx_create_reviews_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained('stores')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete(); // Optional: only allow reviews from verified purchases
            $table->unsignedTinyInteger('rating'); // 1–5
            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->boolean('is_verified_purchase')->default(false);
            $table->boolean('is_approved')->default(true);
            $table->integer('helpful_count')->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->index('store_id');
            $table->index(['store_id', 'id']);

            $table->unique(['user_id', 'product_id']); // one review per user per product
            $table->index(['product_id', 'rating']);    // fast avg calculation
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};